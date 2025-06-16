<?php 

// app/Services/StripeCheckoutService.php

namespace App\Services;

use Illuminate\Container\Attributes\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeCheckoutService
{    public function createSession($plan)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        return Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Plan: ' . $plan->name,
                    ],
                    'unit_amount' => $plan->price * 100, // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
           'success_url' => url("http://localhost:3000/payment-success?session_id={CHECKOUT_SESSION_ID}&plan_id={$plan->id}"),
            'cancel_url' => url('http://localhost:3000/payment-cancel'),
        ]);
    }
}

    
