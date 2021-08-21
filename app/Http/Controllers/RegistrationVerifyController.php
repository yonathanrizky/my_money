<?php

namespace App\Http\Controllers;

use App\User;

class RegistrationVerifyController extends Controller
{
    public function verify($confirmation_code)
    {
        $user = User::where('confirmation_code', $confirmation_code)->first();

        if (!$user) {
            return view('verify.failed');
        }

        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        return view('verify.success');
    }
}
