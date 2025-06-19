<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymobService
{
    protected $apiKey;
    protected $integrationId;
    protected $iframeId;

    public function __construct()
    {
        $this->apiKey = config('paymob.api_key');
        $this->integrationId = config('paymob.integration_id');
        $this->iframeId = config('paymob.iframe_id');
    }

    public function authenticate()
    {
        $response = Http::post('https://accept.paymobsolutions.com/api/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);
        
    
        $data = $response->json();    
        return $data['token'];
    }

    public function createOrder($token, $amountCents, $user)
    {
        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
            'auth_token' => $token,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'items' => [],
        ]);

        return $response['id'];
    }

    public function getPaymentKey($token, $amountCents, $orderId, $user)
    {
        $billingData = [
            "apartment" => "NA", "email" => $user->email, "floor" => "NA",
            "first_name" => $user->name, "street" => "NA", "building" => "NA",
            "phone_number" => $user->phone, "shipping_method" => "NA",
            "postal_code" => "NA", "city" => "NA", "country" => "NA", "last_name" => "NA", "state" => "NA"
        ];

        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            'auth_token' => $token,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => $billingData,
            'currency' => 'EGP',
            'integration_id' => $this->integrationId,
        ]);

        return $response['token'];
    }

    public function getIframeUrl($paymentToken)
    {
        return "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";
    }
}
