<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRegister extends Model
{
    use HasFactory;

    protected $table = 'user_register';

    protected $fillable = [
        'full_name',
        'mobile_number',
        'otp',
        'otp_expires_at',
        'full_name',
        'email',
        'state',
        'city',
        'pin_code',
        'street_address',
        'image',
        'bearer_token',
        'device_id'
    ];
}
