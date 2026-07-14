<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CheckUpFollowUp;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFollowUp extends Command
{
    protected $signature   = 'followup:send';
    protected $description = 'Kirim WhatsApp pengabaran untuk follow-up yang jatuh tempo hari ini';

    public function handle(): int
    {
        // Cache token per cabang agar tidak query berulang untuk cabang yang sama
        $branchTokenCache = [];

        $today = Carbon::today()->toDateString();

        $followUps = CheckUpFollowUp::where('status', 'pending')
            ->where('scheduled_date', '<=', $today)
            ->get();

        if ($followUps->isEmpty()) {
            $this->info('Tidak ada follow-up yang perlu dikirim hari ini.');
            return self::SUCCESS;
        }

        $this->info("Mengirim {$followUps->count()} follow-up...");

        foreach ($followUps as $followUp) {
            // Pilih token: cabang → global
            $branchId = (int) ($followUp->branch_id ?? 0);
            if (!array_key_exists($branchId, $branchTokenCache)) {
                $branch = $branchId > 0 ? Branch::find($branchId) : null;
                $branchTokenCache[$branchId] = ($branch && !empty($branch->fonnte_token))
                    ? $branch->fonnte_token
                    : null;
            }
            $token = $branchTokenCache[$branchId];

            if (empty($token)) {
                $followUp->update([
                    'status'        => 'failed',
                    'error_message' => 'Token WA belum dikonfigurasi untuk cabang ini maupun global.',
                ]);
                $this->warn("  ✗ Skip #{$followUp->id}: token tidak tersedia.");
                Log::warning("SendFollowUp skip #{$followUp->id}: token tidak tersedia.");
                continue;
            }

            $phone = $this->normalizePhone($followUp->owner_phone ?? '');

            if (empty($phone)) {
                $followUp->update([
                    'status'        => 'failed',
                    'error_message' => 'Nomor telepon tidak tersedia.',
                ]);
                $this->warn("  ✗ Skip #{$followUp->id} ({$followUp->owner_name}): nomor telepon kosong.");
                Log::warning("SendFollowUp skip #{$followUp->id}: nomor telepon kosong.");
                continue;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Authorization' => $token])
                    ->post('https://api.fonnte.com/send', [
                        'target'  => $phone,
                        'message' => $followUp->message,
                    ]);

                $body = $response->json();

                if ($response->successful() && ($body['status'] ?? false) === true) {
                    $followUp->update([
                        'status'  => 'sent',
                        'sent_at' => Carbon::now(),
                    ]);
                    $this->line("  ✓ Terkirim ke {$phone} ({$followUp->owner_name} — {$followUp->pet_name})");
                } else {
                    $errMsg = $body['reason'] ?? $response->body();
                    $followUp->update([
                        'status'        => 'failed',
                        'error_message' => $errMsg,
                    ]);
                    $this->warn("  ✗ Gagal ke {$phone}: {$errMsg}");
                    Log::warning("SendFollowUp gagal #{$followUp->id}: {$errMsg}");
                }
            } catch (\Throwable $e) {
                $followUp->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $this->error("  ✗ Error #{$followUp->id}: {$e->getMessage()}");
                Log::error("SendFollowUp exception #{$followUp->id}: {$e->getMessage()}");
            }
        }

        $this->info('Selesai.');
        return self::SUCCESS;
    }

    private function normalizePhone(string $phone): string  // returns '' if input has no digits
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
}
