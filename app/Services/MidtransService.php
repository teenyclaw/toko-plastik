<?php

namespace App\Services;

use App\Models\OrderPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function isConfigured(): bool
    {
        return filled(config('midtrans.server_key'));
    }

    /** @return array{transaction_id:?string,qris_url:?string,raw:array} */
    public function createQrisCharge(OrderPayment $payment): array
    {
        $this->ensureConfigured();

        $orderId = $payment->midtrans_order_id ?? ('QR-' . $payment->id . '-' . now()->timestamp);

        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()
            ->post(config('midtrans.api_url') . '/v2/charge', [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $payment->amount,
                ],
                'custom_expiry' => [
                    'expiry_duration' => 15,
                    'unit' => 'minute',
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Midtrans QRIS charge failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat QRIS: ' . ($response->json('status_message') ?? 'error Midtrans'));
        }

        $data = $response->json();
        $qrisUrl = collect($data['actions'] ?? [])
            ->firstWhere('name', 'generate-qr-code')['url']
            ?? collect($data['actions'] ?? [])->first()['url']
            ?? null;

        return [
            'midtrans_order_id' => $orderId,
            'transaction_id' => $data['transaction_id'] ?? null,
            'qris_url' => $qrisUrl,
            'raw' => $data,
        ];
    }

    public function fetchStatus(string $midtransOrderId): array
    {
        $this->ensureConfigured();

        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->acceptJson()
            ->get(config('midtrans.api_url') . '/v2/' . $midtransOrderId . '/status');

        if (! $response->successful()) {
            throw new \RuntimeException('Gagal cek status pembayaran.');
        }

        return $response->json();
    }

    public function isPaidStatus(?string $status): bool
    {
        return in_array($status, ['capture', 'settlement'], true);
    }

    public function isFailedStatus(?string $status): bool
    {
        return in_array($status, ['deny', 'cancel', 'expire', 'failure'], true);
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        $serverKey = config('midtrans.server_key');
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        if (! $orderId || ! $signatureKey) {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $signatureKey);
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Midtrans belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY di .env');
        }
    }
}
