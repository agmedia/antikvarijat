<?php

namespace App\Models\Front\Catalog;

use Illuminate\Database\Eloquent\Model;

class Translator extends Model
{
    protected $table = 'translators';

    protected $guarded = ['id', 'normalized_title', 'created_at', 'updated_at'];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_translator',
            'translator_id',
            'product_id'
        )
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
