<?php

namespace App\Services;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    private string $token;
    private string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        // Prioritaskan token dari DB; fallback ke .env untuk kompatibilitas
        $dbToken = NotificationSetting::get('fonnte_token');
        $this->token = !empty($dbToken) ? $dbToken : config('services.fonnte.token', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Kirim pesan WhatsApp via Fonnte.
     * Returns ['success' => bool, 'reason' => string]
     */
    public function send(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);

        if (empty($phone)) {
            return ['success' => false, 'reason' => 'Nomor telepon tidak valid.'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $this->token])
                ->post($this->apiUrl, [
                    'target'  => $phone,
                    'message' => $message,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false) === true) {
                return ['success' => true, 'reason' => ''];
            }

            $reason = $body['reason'] ?? $response->body();
            Log::warning("FonnteService gagal ke {$phone}: {$reason}");
            return ['success' => false, 'reason' => $reason];

        } catch (\Throwable $e) {
            Log::error("FonnteService exception ke {$phone}: {$e->getMessage()}");
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
}
