<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@cargoexpress.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('administrador');

        $cliente = User::create([
            'name'     => 'Cliente Demo',
            'email'    => 'cliente@cargoexpress.com',
            'password' => Hash::make('password'),
        ]);
        $cliente->assignRole('cliente');
    }
}