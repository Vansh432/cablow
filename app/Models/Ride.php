<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'rides';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'id';

    // Specify if the IDs are auto-incrementing
    public $incrementing = true;

    // Specify the data type of the auto-incrementing ID
    protected $keyType = 'int';

    // Define the attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'driver_id',
        'source_address',
        'source_latitude',
        'source_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'status',
        'otp',
        'ride_request_id'
    ];

    // Define the attributes that should be cast to native types
    protected $casts = [
        'source_latitude' => 'float',
        'source_longitude' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'status' => 'string',
    ];

    // Define relationships if needed
    public function user()
    {
        return $this->belongsTo(UserRegister::class);
    }

    public function driver()
    {
        return $this->belongsTo(DriverRegister::class);
    }
}
