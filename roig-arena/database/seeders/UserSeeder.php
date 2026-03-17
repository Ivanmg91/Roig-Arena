<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador
        User::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'email' => 'admin@roigarena.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
        ]);

        // Usuarios de prueba
        User::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        User::create([
            'nombre' => 'María',
            'apellido' => 'García',
            'email' => 'maria@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        User::create([
            'nombre' => 'Carlos',
            'apellido' => 'López',
            'email' => 'carlos@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->command->info('✅ Usuarios creados: 4 (1 admin + 3 normales)');
    }
}