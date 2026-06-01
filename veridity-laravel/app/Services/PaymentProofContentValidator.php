<?php

namespace App\Services;

use Illuminate\Support\Str;

class PaymentProofContentValidator
{
    public function validate(string $imagePath, array $expected): array
    {
        $ocr = $this->extractText($imagePath);
        $normalizedText = $this->normalizeText($ocr['text']);
        $expectedAmount = $this->digits((string) ($expected['amount'] ?? ''));
        $recipientAccount = $this->digits((string) ($expected['recipient_account'] ?? ''));
        $recipientName = $this->normalizeText((string) ($expected['recipient_name'] ?? ''));
        $paymentChannel = $this->normalizeText((string) ($expected['payment_channel'] ?? ''));

        $checks = [
            'recipient_account' => $this->checkContainsDigits($normalizedText, $recipientAccount, 'Nomor tujuan/rekening tidak terbaca atau tidak cocok.'),
            'amount' => $this->checkContainsDigits($normalizedText, $expectedAmount, 'Nominal transfer tidak terbaca atau tidak cocok dengan total order.'),
            'transfer_date' => $this->checkDate($normalizedText),
            'recipient_name' => $this->checkContainsWords($normalizedText, $recipientName, 'Nama penerima tidak terbaca jelas.'),
            'payment_channel' => $this->checkContainsWords($normalizedText, $paymentChannel, 'Channel pembayaran tidak terbaca jelas.'),
        ];

        if (! $ocr['available']) {
            return [
                'status' => 'review_required',
                'summary' => 'OCR tidak tersedia, detail isi nota perlu dicek manual oleh admin.',
                'ocr_available' => false,
                'ocr_text' => '',
                'checks' => $checks,
            ];
        }

        $failedCritical = collect($checks)
            ->filter(fn ($check, $key) => in_array($key, ['recipient_account', 'amount'], true) && $check['status'] === 'failed')
            ->isNotEmpty();
        $uncertain = collect($checks)->contains(fn ($check) => $check['status'] === 'review_required');

        if ($failedCritical) {
            $status = 'failed';
            $summary = 'Isi nota tidak cocok dengan data checkout, terutama rekening tujuan atau nominal transfer.';
        } elseif ($uncertain) {
            $status = 'review_required';
            $summary = 'Isi utama nota cocok sebagian, tetapi beberapa detail perlu dicek manual.';
        } else {
            $status = 'passed';
            $summary = 'Rekening tujuan, nominal, tanggal, dan channel pembayaran sesuai dengan data checkout.';
        }

        return [
            'status' => $status,
            'summary' => $summary,
            'ocr_available' => true,
            'ocr_text' => Str::limit($ocr['text'], 1200, ''),
            'checks' => $checks,
        ];
    }

    private function extractText(string $imagePath): array
    {
        $tesseract = config('services.veridity.tesseract_path', 'tesseract');
        $command = [$tesseract, $imagePath, 'stdout', '-l', 'eng+ind'];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);

        if (! is_resource($process)) {
            return ['available' => false, 'text' => ''];
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'available' => $exitCode === 0 && trim((string) $output) !== '',
            'text' => (string) $output,
        ];
    }

    private function checkContainsDigits(string $text, string $expectedDigits, string $failedMessage): array
    {
        if ($expectedDigits === '') {
            return ['status' => 'review_required', 'message' => 'Data pembanding tidak tersedia.'];
        }

        $haystack = $this->digits($text);
        $matched = str_contains($haystack, $expectedDigits);

        return [
            'status' => $matched ? 'passed' : 'failed',
            'expected' => $expectedDigits,
            'message' => $matched ? 'Cocok dengan data checkout.' : $failedMessage,
        ];
    }

    private function checkContainsWords(string $text, string $expectedWords, string $failedMessage): array
    {
        $tokens = collect(preg_split('/\s+/', $expectedWords))
            ->filter(fn ($token) => strlen($token) >= 3)
            ->values();

        if ($tokens->isEmpty()) {
            return ['status' => 'review_required', 'message' => 'Data pembanding tidak tersedia.'];
        }

        $matched = $tokens->contains(fn ($token) => str_contains($text, $token));

        return [
            'status' => $matched ? 'passed' : 'review_required',
            'expected' => $expectedWords,
            'message' => $matched ? 'Teks terkait terbaca pada nota.' : $failedMessage,
        ];
    }

    private function checkDate(string $text): array
    {
        $hasDate = preg_match('/\b(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2})\b/', $text) === 1
            || preg_match('/\b(jan|feb|mar|apr|mei|jun|jul|agu|sep|okt|nov|des|january|february|march|april|may|june|july|august|september|october|november|december)\b/', $text) === 1;

        return [
            'status' => $hasDate ? 'passed' : 'review_required',
            'message' => $hasDate ? 'Tanggal transfer terdeteksi pada nota.' : 'Tanggal transfer tidak terbaca jelas.',
        ];
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($text)));
    }

    private function digits(string $text): string
    {
        return preg_replace('/\D+/', '', $text) ?? '';
    }
}
