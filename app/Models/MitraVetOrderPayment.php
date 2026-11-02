<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitraVetOrderPayment extends Model
{
    protected $table = 'mitra_vet_order_payments';

    protected $fillable = [
        'order_id',
        'nomor_pembayaran',
        'cicilan_ke',
        'jumlah_bayar',
        'tanggal_bayar',
        'metode_pembayaran',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'cicilan_ke'   => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(MitraVetOrder::class, 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
