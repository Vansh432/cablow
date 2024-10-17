<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    use HasFactory;

    protected $table = 'driver_locations';

    protected $fillable = [
        'driver_id',
        'latitude',
        'longitude'
    ];

    // Define the relationship with the DriverRegister model
    public function driver()
    {
        return $this->belongsTo(DriverRegister::class, 'driver_id', 'id');
    }
}
