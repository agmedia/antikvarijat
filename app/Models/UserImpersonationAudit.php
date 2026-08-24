<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImpersonationAudit extends Model
{
    protected $table = 'user_impersonation_audits';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
