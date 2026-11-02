<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MitraVetItem extends Model
{
    protected $table = 'mitra_vet_items';

    protected $fillable = [
        'nama_barang',
        'satuan',
        'harga_modal',
        'harga_jual',
        'profit',
        'harga_satuan',
        'stok',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    protected $casts = [
        'harga_modal'  => 'decimal:2',
        'harga_jual'   => 'decimal:2',
        'profit'       => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'stok'         => 'integer',
        'is_deleted'   => 'boolean',
    ];

    public function stockHistories()
    {
        return $this->hasMany(MitraVetStockHistory::class, 'item_id');
    }

    public function orderItems()
    {
        return $this->hasMany(MitraVetOrderItem::class, 'item_id');
    }
}
