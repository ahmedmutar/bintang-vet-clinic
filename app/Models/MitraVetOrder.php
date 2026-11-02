<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitraVetOrder extends Model
{
    protected $table = 'mitra_vet_orders';

    protected $fillable = [
        'nomor_order',
        'customer_id',
        'nama_pembeli',
        'alamat',
        'no_hp',
        'biaya_ongkir',
        'total_harga',
        'grand_total',
        'tipe_pembayaran',
        'status_pembayaran',
        'jumlah_termin',
        'uang_muka',
        'total_terbayar',
        'sisa_tagihan',
        'tanggal_jatuh_tempo',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'biaya_ongkir'   => 'decimal:2',
        'total_harga'    => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'uang_muka'      => 'decimal:2',
        'total_terbayar' => 'decimal:2',
        'sisa_tagihan'   => 'decimal:2',
        'jumlah_termin'  => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(MitraVetCustomer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(MitraVetOrderItem::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(MitraVetOrderPayment::class, 'order_id')->orderBy('id', 'asc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
