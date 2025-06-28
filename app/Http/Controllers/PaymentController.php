<?php

namespace App\Http\Controllers;

use App\Services\PaymobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeCheckoutService;

class PaymentController extends Controller
{

    public function createSession(Request $request, StripeCheckoutService $stripeService)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);
    
        $plan = Plan::findOrFail($request->plan_id);
    
        $session = $stripeService->createSession($plan);
    
        return response()->json(['sessionId' => $session->id]);
    }

    public function addToPayment(Request $request)
    {
    
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'session_id' => 'required|string',
        ]);
        $plan = Plan::findOrFail($request->plan_id);
    
        $payment = Payment::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'status' => 'completed',
            'payment_method' => 'stripe',
            'transaction_id' => $request->session_id,
            'total' => $plan->price,
        ]);
    
        return response()->json(['message' => 'Payment initiated successfully', 'payment_id' => $payment->id]);
    }
    
    public function showPaymentForm(Request $request)
    {
        $payments = Payment::with(['user', 'plan'])
            ->orderByDesc('id')
            ->get();
    
        if ($payments->isEmpty()) {
            return response()->json(['message' => 'No payments found'], 404);
        }
    
        return response()->json($payments);
    }
    
}
