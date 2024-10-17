<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverVehicleRegister extends Model
{
    use HasFactory;

    protected $table = 'driver_vehicle_register';

    protected $fillable = [
        'DL_number',
        'DL_image',
        'aadhaar_number',
        'aadhaar_front_image',
        'aadhaar_back_image',
        'PAN_number',
        'PAN_image',
        'RC_image',
        'insurance_image',
        'vehicle_permit_image',
        'driver_id',
        'status',
        'vehicle_no',
        'vehicle_image_1',
        'vehicle_image_2',
        'vehicle_image_3',
        'vehicle_image_4',
    ];

    public function driver()
    {
        return $this->belongsTo(DriverRegister::class, 'driver_id');
    }
}

