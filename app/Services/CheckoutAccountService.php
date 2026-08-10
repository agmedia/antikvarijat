<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Bouncer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CheckoutAccountService
{
    public function create(array $address, string $password): User
    {
        return DB::transaction(function () use ($address, $password) {
            $user = User::create([
                'name' => trim(($address['fname'] ?? '').' '.($address['lname'] ?? '')),
                'email' => Str::lower(trim((string) ($address['email'] ?? ''))),
                'password' => Hash::make($password),
            ]);

            Bouncer::assign('customer')->to($user);

            UserDetail::create([
                'user_id' => $user->id,
                'fname' => $address['fname'] ?? '',
                'lname' => $address['lname'] ?? '',
                'address' => $address['address'] ?? '',
                'zip' => $address['zip'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'phone' => $address['phone'] ?? '',
                'company' => $address['company'] ?? '',
                'oib' => $address['oib'] ?? '',
                'avatar' => 'media/avatars/avatar1.jpg',
                'bio' => '',
                'social' => '',
                'role' => 'customer',
                'status' => 1,
            ]);

            return $user;
        });
    }
}
