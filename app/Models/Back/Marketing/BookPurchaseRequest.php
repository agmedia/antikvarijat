<?php

namespace App\Models\Back\Marketing;

use Illuminate\Database\Eloquent\Model;

class BookPurchaseRequest extends Model
{
    protected $table = 'book_purchase_requests';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'photos' => 'array',
        'submitted_at' => 'datetime',
    ];
}
