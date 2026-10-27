<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaClient
{
    public function isConfigured(): bool
    {
        $config = config('tajira.payments.mpesa');

        return $config['enabled']
            && filled($config['consumer_key'])
            && filled($config['consumer_secret'])
            && filled($config['shortcode'])
            && filled($config['passkey'])
            && filled($config['callback_url']);
    }

    public function baseUrl(): string
    {
        return config('tajira.payments.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function accessToken(): string
    {
        $config = config('tajira.payments.mpesa');
        $cacheKey = 'mpesa.token.'.sha1($config['consumer_key'].'|'.$config['env']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($config) {
            $response = Http::withBasicAuth($config['consumer_key'], $config['consumer_secret'])
                ->timeout($config['timeout'])
                ->retry(2, 500)
                ->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

            if (! $response->successful() || ! $response->json('access_token')) {
                Log::warning('M-Pesa token request failed', ['status' => $response->status()]);

                throw new RuntimeException('Unable to authenticate with M-Pesa. Please try again or choose another payment method.');
            }

            return $response->json('access_token');
        });
    }

    /**
     * @return array{merchant_request_id:string,checkout_request_id:string,response_code:string,response_description:string,customer_message:string}
     */
    public function stkPush(string $phone, int $amountMinor, string $accountReference, string $description): array
    {
        $this->assertConfigured();

        if ($amountMinor <= 0 || $amountMinor % 100 !== 0) {
            throw new RuntimeException('M-Pesa payments require a whole-shilling amount. Please contact support to complete this order.');
        }

        $config = config('tajira.payments.mpesa');
        $timestamp = now()->timezone('Africa/Nairobi')->format('YmdHis');
        $password = base64_encode($config['shortcode'].$config['passkey'].$timestamp);

        $payload = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $config['transaction_type'],
            'Amount' => intdiv($amountMinor, 100),
            'PartyA' => $this->normalisePhone($phone),
            'PartyB' => $config['shortcode'],
            'PhoneNumber' => $this->normalisePhone($phone),
            'CallBackURL' => $config['callback_url'],
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($description, 0, 100),
        ];

        $response = Http::withToken($this->accessToken())
            ->timeout($config['timeout'])
            ->acceptJson()
            ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', $payload);

        $data = $response->json() ?? [];

        if (! $response->successful() || ($data['ResponseCode'] ?? null) !== '0') {
            Log::warning('M-Pesa STK push failed', [
                'status' => $response->status(),
                'response_code' => $data['ResponseCode'] ?? null,
                'error' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? null,
            ]);

            throw new RuntimeException($data['errorMessage'] ?? $data['ResponseDescription'] ?? 'M-Pesa could not start the payment. Please try again.');
        }

        return [
            'merchant_request_id' => (string) ($data['MerchantRequestID'] ?? ''),
            'checkout_request_id' => (string) ($data['CheckoutRequestID'] ?? ''),
            'response_code' => (string) ($data['ResponseCode'] ?? ''),
            'response_description' => (string) ($data['ResponseDescription'] ?? ''),
            'customer_message' => (string) ($data['CustomerMessage'] ?? 'Check your phone to approve the payment.'),
        ];
    }

    /**
     * @return array{result_code:string,result_desc:string,amount_minor:?int,receipt:?string,raw:array}
     */
    public function queryStatus(string $checkoutRequestId): array
    {
        $this->assertConfigured();
        $config = config('tajira.payments.mpesa');
        $timestamp = now()->timezone('Africa/Nairobi')->format('YmdHis');
        $password = base64_encode($config['shortcode'].$config['passkey'].$timestamp);

        $response = Http::withToken($this->accessToken())
            ->timeout($config['timeout'])
            ->acceptJson()
            ->post($this->baseUrl().'/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $config['shortcode'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        $data = $response->json() ?? [];
        $rawResult = $data['ResultCode'] ?? null;

        if (! $response->successful() || $rawResult === null) {
            throw new RuntimeException($data['errorMessage'] ?? 'Unable to confirm the M-Pesa transaction yet.');
        }

        $amount = null;
        $receipt = null;

        foreach (($data['CallbackMetadata']['Item'] ?? []) as $item) {
            if (($item['Name'] ?? null) === 'Amount') {
                $amount = (int) round(((float) $item['Value']) * 100);
            }

            if (($item['Name'] ?? null) === 'MpesaReceiptNumber') {
                $receipt = (string) $item['Value'];
            }
        }

        return [
            'result_code' => (string) $rawResult,
            'result_desc' => (string) ($data['ResultDesc'] ?? ''),
            'amount_minor' => $amount,
            'receipt' => $receipt,
            'raw' => $this->redact($data),
        ];
    }

    public function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254'.$digits;
        }

        if (! preg_match('/^254(7|1)\d{8}$/', $digits)) {
            throw new RuntimeException('Enter a valid Safaricom phone number, for example 0712 345 678.');
        }

        return $digits;
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('M-Pesa is not configured. Please choose bank transfer or contact us.');
        }
    }

    private function redact(array $payload): array
    {
        foreach (['Password', 'SecurityCredential', 'access_token'] as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
