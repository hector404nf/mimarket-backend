<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@mimarket.com'],
            [
                'name' => 'Administrador',
                'apellido' => 'Sistema',
                'email' => 'admin@mimarket.com',
                'password' => Hash::make('admin123'),
                'telefono' => '+595 21 123 456',
                'tipo_usuario' => 'administrador',
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        // Crear algunos usuarios adicionales para pruebas
        $testUsers = [
            [
                'name' => 'Juan',
                'apellido' => 'Pérez',
                'email' => 'juan@test.com',
                'password' => Hash::make('password123'),
                'telefono' => '+595 21 111 222',
                'tipo_usuario' => 'cliente',
                'activo' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'María',
                'apellido' => 'González',
                'email' => 'maria@test.com',
                'password' => Hash::make('password123'),
                'telefono' => '+595 21 333 444',
                'tipo_usuario' => 'vendedor',
                'activo' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($testUsers as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }

        $this->command->info('Usuario administrador creado: admin@mimarket.com / admin123');
    }
}