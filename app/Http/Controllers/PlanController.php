<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;

use App\Services\PaymobService;
use App\Models\Payment;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        return response()->json($plans);
    }


    public function mySubscription()
    {
        $user = Auth::user();
    
        $subscription = Subscription::where('user_id', $user->id)
            ->where('active', true)
            ->with('plan')
            ->orderByDesc('id')
            ->first();
    
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found']);
        }
    
        return response()->json($subscription);
    }

    public function cancelSubscription()
    {
        $user = Auth::user();
    
        $subscription = Subscription::where('user_id', $user->id)
            ->where('active', true)
            ->first();
    
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }
    
        // Deactivate the subscription
        $subscription->update(['active' => false]);
    
        return response()->json(['message' => 'Subscription cancelled successfully']);
    }
    

    public function subscribeToPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);

        // Deactivate previous subscriptions
        // Subscription::where('user_id', $user->id)->update(['active' => false]);

        // Check if the user already has an active subscription to the same plan
        if ($user->isSubscribedToPlan($plan->id)) {
            return response()->json(['message' => 'You are already subscribed to this plan.'], 400);
        }

        // Check if the user already has an active subscription to the any plan
        if ($user->hasActiveSubscription()) {
            return response()->json(['message' => 'You already have an active subscription. Please cancel it before subscribing to a new plan.'], 400);
        }

        $subscription = Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(), 
            'active' => true,
        ]);

        return response()->json([
            'message' => 'Subscription created successfully.',
            'subscription' => [
                'plan' => $plan->name,
                'price' => $plan->price,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
            ]
        ]);
    }


    public function upgradeSubscription($id)
    {

    
        $user = Auth::user();
        $newPlan = Plan::findOrFail($id);
    
        // Check if the new plan is the same as current active plan
        $currentSubscription = Subscription::where('user_id', $user->id)
                                           ->where('active', true)
                                           ->first();
    
        if ($currentSubscription && $currentSubscription->plan_id == $newPlan->id) {
            return response()->json([
                'message' => 'You are already subscribed to this plan.'
            ], 400);
        }
    
        // Deactivate current active subscription
        if ($currentSubscription) {
            $currentSubscription->update(['active' => false]);
        }
    
        // Create new subscription
        $newSubscription = Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $newPlan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(), // or custom logic
            'active' => true,
        ]);
    
        return response()->json([
            'message' => 'Subscription upgraded successfully.',
            'subscription' => [
                'plan' => $newPlan->name,
                'price' => $newPlan->price,
                'starts_at' => $newSubscription->starts_at,
                'ends_at' => $newSubscription->ends_at,
            ]
        ]);
    }
    public function reSubscribeToPlan($id)
    {
        $user = Auth::user();
        $plan = Plan::findOrFail($id);
    
        // Check for an existing inactive subscription for this plan that is expired
        $oldSubscription = Subscription::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('active', false)
            ->where('ends_at', '<', now()) // ✅ Only expired subscriptions
            ->latest('ends_at')
            ->first();
    
        if ($oldSubscription) {
            // Reactivate and extend the subscription
            $oldSubscription->update([
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'active' => true,
            ]);
    
            return response()->json([
                'message' => 'Expired subscription reactivated successfully.',
                'subscription' => [
                    'plan' => $plan->name,
                    'price' => $plan->price,
                    'starts_at' => $oldSubscription->starts_at,
                    'ends_at' => $oldSubscription->ends_at,
                ]
            ]);
        }
    
        // Check if there's already an active or unexpired subscription
        $existing = Subscription::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where(function ($query) {
                $query->where('active', true)
                      ->orWhere('ends_at', '>', now());
            })
            ->first();
    
        if ($existing) {
            return response()->json([
                'message' => 'You already have an active or unexpired subscription for this plan.'
            ], 400);
        }
    
        // Otherwise, create a new subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'active' => true,
        ]);
    
        return response()->json([
            'message' => 'Subscribed successfully.',
            'subscription' => [
                'plan' => $plan->name,
                'price' => $plan->price,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
            ]
        ]);
    }

    public function addToCart(Request $request)
    {
        $plan = Plan::findOrFail($request->plan_id);
        $cartItem = Cart::where('user_id', Auth::id())
                        ->where('plan_id', $plan->id)
                        ->first();
    
        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            Cart::create([
                'user_id' => Auth::id(),
                'plan_id' => $plan->id,
                'quantity' => 1
            ]);
        }
    
        return response()->json(['message' => 'Plan added to cart successfully']);
    }

    public function removeFromCart(Request $request){
        $user = Auth::user();
        Cart::where('user_id', $user->id)
            ->where('plan_id', $request->plan_id)
            ->delete();
    
        return response()->json(['message' => 'Plan removed from cart successfully']);
    }

    public function viewMYCart(){
        $cartItems = Cart::with('plan')->where('user_id', Auth::id())->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty']);
        }  
        return response()->json($cartItems);
    }
    
    

    public function canSubscribeToFreePlan(Request $request)
    {
        $user = $request->user();
    
        // Check if user has EVER subscribed to Free Plan (plan_id = 1)
        $hasUsedFreePlan = $user->subscription()
            ->where('plan_id', 1)
            ->exists();
    
        if ($hasUsedFreePlan) {
            return response()->json([
                'allowed' => false,
                'message' => 'You have already used the Free plan. This option is no longer available.',
            ], 403);
        }
    
        return response()->json([
            'allowed' => true,
            'message' => 'You are allowed to subscribe to the Free plan.',
        ]);
    }
    
}
