<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController
{
    public function edit(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $emailChanged = $user->email !== $data['email'];
        $user->fill($data);
        if ($emailChanged && array_key_exists('email_verified_at', $user->getAttributes())) {
            $user->email_verified_at = null;
        }
        $user->save();
        return back()->with('status', 'Данные профиля обновлены.');
    }

    public function password(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();
        return back()->with('status', 'Пароль изменён.');
    }
}
