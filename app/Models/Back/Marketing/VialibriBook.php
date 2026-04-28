<?php

namespace App\Models\Back\Marketing;

use App\Models\Back\Catalog\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VialibriBook extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'vialibri_books';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $casts = [
        'first_edition' => 'boolean',
        'signed' => 'boolean',
        'dust_jacket' => 'boolean',
        'translated_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
