<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'ops_id',
        'full_name',
        'nik',
        'phone_number',
        'ktp_photo',
        'bank_id',
        'account_number',
        'domicile_address',
        'emergency_contact_number',
        'emergency_contact_name',
        'emergency_contact_relation',
        'self_photo',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
