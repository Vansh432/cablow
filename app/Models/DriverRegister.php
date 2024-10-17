<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverRegister extends Model
{
    use HasFactory;

    protected $table = 'driver_register';

    protected $primaryKey = 'id';

    protected $fillable = [
        'mobile_number',
        'otp',
        'otp_expires_at',
        'image',
        'email',
        'state',
        'city',
        'street_address',
        'pin_code',
        'dob',
        'vehicle_type',
        'status',
        'bearer_token',
        'device_id',
        'latitude',
        'longitude',
        'full_name'
    ];

    public function vehicle()
    {
        return $this->hasOne(DriverVehicleRegister::class, 'driver_id', 'id');
    }

    public function locations()
    {
        return $this->hasMany(DriverLocation::class, 'driver_id', 'id');
    }
}
