<?php

namespace App\Http\Controllers;

use App\Models\DriverRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\DriverVehicleRegister;
use Illuminate\Support\Facades\Validator;

class registerVehicle extends Controller
{
   public function registerVehicle(Request $request, $driverId)
{
    // Validate incoming request data
    $validator = Validator::make($request->all(), [
        'DL_number' => 'required|string',
        'DL_image' => 'required|string', // base64 string
        'aadhaar_number' => 'nullable|string',
        'aadhaar_front_image' => 'nullable|string', // base64 string
        'aadhaar_back_image' => 'nullable|string', // base64 string
        'PAN_number' => 'nullable|string',
        'PAN_image' => 'nullable|string', // base64 string
        'RC_image' => 'nullable|string', // base64 string
        'insurance_image' => 'nullable|string', // base64 string
        'vehicle_permit_image' => 'nullable|string', // base64 string
        'vehicle_no' => 'required|string', // new field
        'vehicle_image_1' => 'nullable|string', // new base64 image
        'vehicle_image_2' => 'nullable|string', // new base64 image
        'vehicle_image_3' => 'nullable|string', // new base64 image
        'vehicle_image_4' => 'nullable|string', // new base64 image
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Check if driver exists
    $driver = DriverRegister::find($driverId);
    if (!$driver) {
        return response()->json(['message' => 'Driver not found.'], 404);
    }

    // Verify token
    $token = str_replace('Bearer ', '', $request->header('Authorization'));
    if ($driver->bearer_token !== $token) {
        return response()->json(['message' => 'Unauthorized: token does not match driver.'], 401);
    }

    // Decode and save images
    $DLImage = $this->saveBase64Image($request->input('DL_image'), "driver/$driverId/DL", 'DL_image');
    $aadhaarFrontImage = $this->saveBase64Image($request->input('aadhaar_front_image'), "driver/$driverId/aadhaar", 'aadhaar_front');
    $aadhaarBackImage = $this->saveBase64Image($request->input('aadhaar_back_image'), "driver/$driverId/aadhaar", 'aadhaar_back');
    $PANImage = $this->saveBase64Image($request->input('PAN_image'), "driver/$driverId/PAN", 'PAN_image');
    $RCImage = $this->saveBase64Image($request->input('RC_image'), "driver/$driverId/RC", 'RC_image');
    $insuranceImage = $this->saveBase64Image($request->input('insurance_image'), "driver/$driverId/insurance", 'insurance_image');
    $vehiclePermitImage = $this->saveBase64Image($request->input('vehicle_permit_image'), "driver/$driverId/vehicle_permit", 'vehicle_permit_image');
    
    // Save the new vehicle images
    $vehicleImage1 = $this->saveBase64Image($request->input('vehicle_image_1'), "driver/$driverId/vehicle", 'vehicle_image_1');
    $vehicleImage2 = $this->saveBase64Image($request->input('vehicle_image_2'), "driver/$driverId/vehicle", 'vehicle_image_2');
    $vehicleImage3 = $this->saveBase64Image($request->input('vehicle_image_3'), "driver/$driverId/vehicle", 'vehicle_image_3');
    $vehicleImage4 = $this->saveBase64Image($request->input('vehicle_image_4'), "driver/$driverId/vehicle", 'vehicle_image_4');

    // Check if a vehicle registration record already exists for the driver
    $vehicleRegister = DriverVehicleRegister::where('driver_id', $driverId)->first();

    if ($vehicleRegister) {
        // Update existing record
        $vehicleRegister->update([
            'DL_number' => $request->input('DL_number'),
            'DL_image' => $DLImage,
            'aadhaar_number' => $request->input('aadhaar_number'),
            'aadhaar_front_image' => $aadhaarFrontImage,
            'aadhaar_back_image' => $aadhaarBackImage,
            'PAN_number' => $request->input('PAN_number'),
            'PAN_image' => $PANImage,
            'RC_image' => $RCImage,
            'insurance_image' => $insuranceImage,
            'vehicle_permit_image' => $vehiclePermitImage,
            'vehicle_no' => $request->input('vehicle_no'), // New field
            'vehicle_image_1' => $vehicleImage1, // New image
            'vehicle_image_2' => $vehicleImage2, // New image
            'vehicle_image_3' => $vehicleImage3, // New image
            'vehicle_image_4' => $vehicleImage4, // New image
            'status' => 'pending', // Set status to pending
        ]);
    } else {
        // Create new record
        $vehicleRegister = DriverVehicleRegister::create([
            'DL_number' => $request->input('DL_number'),
            'DL_image' => $DLImage,
            'aadhaar_number' => $request->input('aadhaar_number'),
            'aadhaar_front_image' => $aadhaarFrontImage,
            'aadhaar_back_image' => $aadhaarBackImage,
            'PAN_number' => $request->input('PAN_number'),
            'PAN_image' => $PANImage,
            'RC_image' => $RCImage,
            'insurance_image' => $insuranceImage,
            'vehicle_permit_image' => $vehiclePermitImage,
            'vehicle_no' => $request->input('vehicle_no'), // New field
            'vehicle_image_1' => $vehicleImage1, // New image
            'vehicle_image_2' => $vehicleImage2, // New image
            'vehicle_image_3' => $vehicleImage3, // New image
            'vehicle_image_4' => $vehicleImage4, // New image
            'driver_id' => $driverId,
            'status' => 'pending', // Set status to pending
        ]);
    }

    return response()->json(['message' => 'Vehicle registration processed successfully.', 'Vehicle Details' => $vehicleRegister]);
}



    private function saveBase64Image($base64String, $directory, $filename)
    {
        // Decode the base64 string
        $imageData = base64_decode($base64String);

        // Determine the file extension
        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
        $extension = explode('/', $mimeType)[1];

        // Construct the full path
        $fullPath = "$directory/$filename.$extension";

        // Save the file
        Storage::put($fullPath, $imageData);

        return $fullPath;
    }

    public function getVehicleDetails(Request $request, $driverId)
{
    // Validate Bearer Token
    $token = $request->header('Authorization');
    $token = str_replace('Bearer ', '', $token);

    // You should verify the token against the stored token in the driver's record or session
    $driver = DriverRegister::where('bearer_token', $token)->first();

    if (!$driver) {
        return response()->json(['message' => 'Unauthorized: Invalid token.'], 401);
    }

    // Fetch vehicle details for the driver
    $vehicleDetails = DriverVehicleRegister::where('driver_id', $driverId)->first();

    if (!$vehicleDetails) {
        return response()->json(['message' => 'Vehicle details not found for this driver.'], 404);
    }

    // Return vehicle details
    return response()->json(['Vehicle Details' => $vehicleDetails]);
}
}
