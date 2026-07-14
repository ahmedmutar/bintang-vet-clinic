<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Complaint;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class PengabaranController extends Controller
{
    private function adminOnly(): void
    {
        if (request()->user()->role !== 'admin') {
            abort(response()->json([
                'message' => 'Akses ditolak.',
                'errors'  => ['Hanya admin yang dapat mengakses fitur ini.'],
            ], 403));
        }
    }

    // ─── Nomor WA per Cabang ─────────────────────────────────────────────────

    public function getNomorWa()
    {
        $this->adminOnly();
        $branches = Branch::whereNull('deleted_at')
            ->select('id', 'branch_name', 'whatsapp_number', 'fonnte_token')
            ->orderBy('branch_name')
            ->get()
            ->map(function ($b) {
                return [
                    'id'              => $b->id,
                    'branch_name'     => $b->branch_name,
                    'whatsapp_number' => $b->whatsapp_number,
                    // Kirim token penuh supaya bisa diedit; masking dilakukan di frontend
                    'fonnte_token'    => $b->fonnte_token,
                    'has_token'       => !empty($b->fonnte_token),
                ];
            });

        return response()->json($branches);
    }

    public function saveNomorWa(Request $request)
    {
        $this->adminOnly();
        $request->validate([
            'id'              => 'required|integer|exists:branches,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'fonnte_token'    => 'nullable|string|max:255',
        ]);

        $update = [
            'whatsapp_number' => $request->whatsapp_number ? trim($request->whatsapp_number) : null,
        ];

        // Jika fonnte_token dikirim (termasuk string kosong = hapus token)
        if ($request->has('fonnte_token')) {
            $update['fonnte_token'] = $request->fonnte_token ? trim($request->fonnte_token) : null;
        }

        Branch::where('id', $request->id)->update($update);

        return response()->json(['message' => 'Data WhatsApp cabang berhasil diperbarui.']);
    }

    // ─── Template Pesan ──────────────────────────────────────────────────────

    public function getTemplates(Request $request)
    {
        $this->adminOnly();
        // branch_id = 0 berarti Global/Default
        $branchId = (int) $request->get('branch_id', 0);

        $complaints = Complaint::where('isDeleted', false)
            ->orderBy('id')
            ->get(['id', 'name']);

        $templates = NotificationTemplate::where('branch_id', $branchId)
            ->get()
            ->keyBy('complaint_id');

        // Untuk referensi: ambil juga template global agar bisa ditampilkan sebagai placeholder
        $globals = $branchId > 0
            ? NotificationTemplate::where('branch_id', 0)->get()->keyBy('complaint_id')
            : collect();

        $result = $complaints->map(function ($complaint) use ($templates, $globals, $branchId) {
            $tpl    = $templates->get($complaint->id);
            $global = $globals->get($complaint->id);

            return [
                'complaint_id'      => $complaint->id,
                'complaint_name'    => $complaint->name,
                'message'           => $tpl ? $tpl->message : '',
                'followup_days'     => $tpl ? (int) $tpl->followup_days : ($global ? (int) $global->followup_days : 3),
                'has_custom'        => $tpl !== null,
                'global_message'    => $branchId > 0 ? ($global ? $global->message : '') : null,
                'global_days'       => $branchId > 0 ? ($global ? (int) $global->followup_days : 3) : null,
            ];
        });

        return response()->json($result);
    }

    public function saveTemplate(Request $request)
    {
        $this->adminOnly();
        $request->validate([
            'branch_id'    => 'required|integer|min:0',
            'complaint_id' => 'required|integer|exists:complaints,id',
            'message'      => 'required|string',
            'followup_days' => 'required|integer|min:1|max:365',
        ]);

        NotificationTemplate::updateOrCreate(
            ['branch_id' => $request->branch_id, 'complaint_id' => $request->complaint_id],
            ['message' => $request->message, 'followup_days' => $request->followup_days]
        );

        return response()->json(['message' => 'Template pesan berhasil disimpan.']);
    }

    public function deleteTemplate(Request $request)
    {
        $this->adminOnly();
        $request->validate([
            'branch_id'    => 'required|integer|min:1',
            'complaint_id' => 'required|integer|exists:complaints,id',
        ]);

        NotificationTemplate::where('branch_id', $request->branch_id)
            ->where('complaint_id', $request->complaint_id)
            ->delete();

        return response()->json(['message' => 'Template cabang dihapus. Akan menggunakan template global.']);
    }
}
