<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos básicos
        $permisos = [
            'ver', 'crear', 'editar', 'eliminar'
        ];

        foreach ($permisos as $permiso) {
            Permission::create(['name' => $permiso, 'guard_name' => 'api']);
        }

        // Crear roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);

        // Asignar todos los permisos al rol super_admin
        $superAdminRole->givePermissionTo(Permission::all());

        // Asignar todos los permisos excepto 'eliminar' al rol admin
        $adminPermissions = Permission::where('name', '!=', 'eliminar')->get();
        $adminRole->givePermissionTo($adminPermissions);

        // Asignar solo permiso de 'ver' al rol user
        $userRole->givePermissionTo('ver');

        // Crear usuarios
        $usuarios = [
            [
                'name' => 'Super Admin User',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Common User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
        ];

        foreach ($usuarios as $usuario) {
            $user = User::create([
                'name' => $usuario['name'],
                'email' => $usuario['email'],
                'password' => $usuario['password'],
            ]);

            // Asignar rol al usuario
            $user->assignRole($usuario['role']);
        }
    }
}
