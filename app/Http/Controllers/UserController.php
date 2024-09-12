<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Método para obtener usuarios por rol
    public function getUsersByRole($role)
    {
        try {
            // Obtener todos los usuarios que tengan el rol especificado
            $users = User::whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })->get();

            // Verificar si hay usuarios con el rol especificado
            if ($users->isEmpty()) {
                return response()->json(['error' => 'No users found with the specified role'], 404);
            }

            // Devolver los usuarios con el rol especificado
            return response()->json($users, 200);
        } catch (\Exception $e) {
            // Manejar cualquier error inesperado
            return response()->json(['error' => 'Error fetching users data'], 500);
        }
    }

    /**
     * Método para obtener usuarios por rol e id.
     *
     * @param string $role Rol de los usuarios a buscar.
     * @param int $id ID del usuario a buscar.
     * @return \Illuminate\Http\Response
     */
    public function getUsersByRoleAndId($role, $id)
    {
        try {
            // Obtener usuarios que coincidan con el rol y el id especificados
            $user = User::whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })->where('id', $id)->first();

            // Verificar si se encontró algún usuario
            if (!$user) {
                return response()->json(['error' => 'No user found with the specified role and ID'], 404);
            }

            // Devolver el usuario encontrado
            return response()->json($user, 200);
        } catch (\Exception $e) {
            // Manejar cualquier error inesperado
            return response()->json(['error' => 'Error fetching user data'], 500);
        }
    }
}
