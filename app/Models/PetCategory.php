<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetCategory extends Model
{
    protected $table = 'pet_categories';

    protected $fillable = ['name'];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'pet_category_id');
    }
}
