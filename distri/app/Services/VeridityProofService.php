<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class VeridityProofService
{
    public function analyze(string $proofFileName, string $orderId, string $paymentMethod, string $paymentChannel): array
    {
        $baseUrl = rtrim((string) config('services.veridity.base_url'), '/');
        $integrationKey = (string) config('services.veridity.integration_key');
        $proofPath = public_path('proofs/'.$proofFileName);

        if ($integrationKey === '') {
            return $this->error('Integration key VERIDITY belum dikonfigurasi.');
        }

        if (! file_exists($proofPath)) {
            return $this->error('File bukti pembayaran tidak ditemukan.');
        }

        try {
            $fileStream = fopen($proofPath, 'r');
            $response = Http::timeout(300)
                ->withHeaders(['X-Veridity-Integration-Key' => $integrationKey])
                ->attach('proof', $fileStream, $proofFileName)
                ->post($baseUrl.'/api/integrations/distri/analyze-proof', [
                    'order_id' => $orderId,
                    'payment_method' => $paymentMethod,
                    'payment_channel' => $paymentChannel,
                    'source' => 'distri',
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        } catch (Throwable $exception) {
            if (isset($fileStream) && is_resource($fileStream)) {
                fclose($fileStream);
            }

            return $this->error('VERIDITY tidak merespons: '.$exception->getMessage());
        }

        if ($response->failed()) {
            return $this->error($response->json('message') ?? 'Analisis VERIDITY gagal diproses.');
        }

        $data = $response->json('data') ?? [];
        $summaryColor = $data['summary_color'] ?? 'warning';

        return [
            'veridity_status' => $summaryColor === 'success' ? 'verified' : 'rejected',
            'payment_status' => $summaryColor === 'success' ? 'paid' : 'review_required',
            'veridity_audit_id' => $data['audit_id'] ?? null,
            'veridity_score' => $data['final_score'] ?? null,
            'veridity_message' => $data['summary_label'] ?? ($response->json('message') ?? 'Analisis selesai.'),
            'veridity_checked_at' => now(),
        ];
    }

    private function error(string $message): array
    {
        return [
            'veridity_status' => 'error',
            'payment_status' => 'checking',
            'veridity_message' => $message,
            'veridity_checked_at' => now(),
        ];
    }
}
