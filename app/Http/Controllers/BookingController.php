<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Notifications\BookingStatusUpdate;

class BookingController extends Controller
{

//  public function __construct()
//     {
//         $this->middleware('auth:sanctum');
//     }

    public function store(Request $request)
    {
        $request->validate([
            'ad_id' => 'required|exists:ads,id',
            'book_content' => 'nullable|string',
        ]);

        $booking = Booking::create([
            'ad_id' => $request->ad_id,
            'student_id' => Auth::id(), 
            'book_content' => $request->book_content,
            // 'payment_status' => Booking::PAYMENT_PENDING,
            // 'status' => Booking::STATUS_WAITING,
        ]);

        return response()->json(['message' => 'Your reservation request has been sent successfully', 'data' => $booking]);
    }

    
    public function myBookings()
    {
        $bookings = Booking::where('student_id', Auth::id())->with('ad')->latest()->get();
        return response()->json($bookings);
    }




    public function allBookings()
    {
        $bookings = Booking::with('ad', 'student')->latest()->get();
        return response()->json($bookings);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:waiting,accepted,rejected',
            'verification_notes' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => $request->status,
            'verification_notes' => $request->verification_notes,
        ]);

        
       
        if ($booking->user) {
            $booking->user->notify(new BookingStatusUpdate($booking));
            return response()->json(['message' => 'Booking status updated', 'data' => $booking]);
        }else{
            return response()->json(['message' => 'User not found'], 404);
        }


        
    }


      
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['payment_status' => $request->payment_status]);

        return response()->json(['message' => 'Batch status updated', 'data' => $booking]);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }

    public function getMyNotifications()
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        // Get the user's notifications
        // if no unread notifications, return a
        if ($user->unreadNotifications->isEmpty()) {
            return response()->json([
                'message' => 'No unread notifications'
            ]);
        }
        else {
            return response()->json([
                'notifications' => $user->unreadNotifications
            ]);
        }

    }
}
