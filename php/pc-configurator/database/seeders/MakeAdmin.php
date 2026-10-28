<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MakeAdmin extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        if ($email) User::where('email', $email)->update(['is_admin' => true]);
    }
}
