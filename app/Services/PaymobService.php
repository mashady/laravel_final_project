<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Pail\ValueObjects\Origin\Console;

class PaymobService
{
    protected $apiKey;
    protected $integrationId;
    protected $iframeId;

    public function __construct()
    {
        $this->apiKey = env('PAYMOB_API_KEY');
        $this->integrationId = env('PAYMOB_INTEGRATION_ID');
        $this->iframeId = env('PAYMOB_IFRAME_ID');
    }

    public function authenticate()
    {
        $response = Http::post('https://accept.paymobsolutions.com/api/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);

        return $response['token'];
    }

    public function createOrder($authToken, $amountInCents)
    {
        $response = Http::post('https://accept.paymobsolutions.com/api/ecommerce/orders', [
            'auth_token' => $authToken,
            'delivery_needed' => false,
            'amount_cents' => $amountInCents,
            'currency' => 'EGP',
            'items' => [],
        ]);

        return $response['id'];
    }

    public function generatePaymentKey($authToken, $orderId, $amountInCents, $user)
    {
        $billingData = [
            'apartment' => 'NA', 'email' => $user->email, 'floor' => 'NA',
            'first_name' => $user->name, 'street' => 'NA', 'building' => 'NA',
            'phone_number' => $user->phone ?? '01000000000', 'shipping_method' => 'NA',
            'postal_code' => 'NA', 'city' => 'NA', 'country' => 'EG', 'last_name' => $user->name,
            'state' => 'NA',
        ];

        $response = Http::post('https://accept.paymobsolutions.com/api/acceptance/payment_keys', [
            'auth_token' => $authToken,
            'amount_cents' => $amountInCents,
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
        if (!$this->iframeId) {
            throw new \Exception('Paymob iframe ID is not set in the environment variables.');
        }
        if (!$paymentToken) {
            throw new \Exception('Payment token is required to generate the iframe URL.');
        }
        if (!$this->integrationId) {
            throw new \Exception('Paymob integration ID is not set in the environment variables.');
        }
        if (!$this->apiKey) {
            throw new \Exception('Paymob API key is not set in the environment variables.');
        }
        if (!$paymentToken) {
            throw new \Exception('Paymob authentication token is not set.');
        }
        return "https://accept.paymobsolutions.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";
    }
}
