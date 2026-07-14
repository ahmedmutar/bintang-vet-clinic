<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = ['branch_id', 'complaint_id', 'message', 'followup_days'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Ambil template (message + followup_days) untuk keluhan tertentu.
     * Prioritas: template cabang spesifik → template global (branch_id=0) → default.
     * Returns: ['message' => string, 'days' => int]
     */
    public static function getByComplaint(int $complaintId, int $branchId = 0): array
    {
        $defaultMessage = "Halo Kak, Terima kasih sudah menjadi pelanggan setia kami ya..\n\nBila ada pertanyaan apapun terkait anabul, jangan ragu untuk konsultasi ke whatsapp kami.\n\nSemoga kaka sekeluarga dan semua anabul, selalu diberikan kesehatan.";

        if ($branchId > 0) {
            $branch = static::where('branch_id', $branchId)->where('complaint_id', $complaintId)->first();
            if ($branch && !empty($branch->message)) {
                return ['message' => $branch->message, 'days' => (int) $branch->followup_days];
            }
        }

        $global = static::where('branch_id', 0)->where('complaint_id', $complaintId)->first();
        if ($global && !empty($global->message)) {
            return ['message' => $global->message, 'days' => (int) $global->followup_days];
        }

        return ['message' => $defaultMessage, 'days' => 3];
    }
}
