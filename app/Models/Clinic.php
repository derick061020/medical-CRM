<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class Clinic extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'logo',
        'favicon',
        'registration_certificate',
        'tax_certificate',
        'sanitary_license',
        'insurance_certificate',
        'website',
        'facebook',
        'instagram',
        'twitter',
        'clinic_type'
    ];

    public function createUser($data)
    {
        $user = User::create([
            'name' => $data['user_name'],
            'email' => $data['user_email'],
            'password' => Hash::make($data['user_password']),
            'type' => 'clinic',
            'clinic_id' => $this->id
        ]);

        return $user;
    }
}
