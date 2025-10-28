<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{
    /**
     * Obtener información del perfil del usuario logueado
     */
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();
        
        return response()->json([
            'id_usuario' => $user->id_usuario,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'dni' => $user->dni,
            'email' => $user->email,
            'rol' => $user->rol,
            'puntos' => $user->puntos,
            'preferencia_tema' => $user->preferencia_tema,
            'created_at' => $user->created_at,
        ], 200);
    }

    /**
     * Actualizar información del perfil (nombre, apellido, email)
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id_usuario . ',id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Solo actualizar los campos que se enviaron
        if ($request->has('nombre')) {
            $user->nombre = $request->nombre;
        }
        if ($request->has('apellido')) {
            $user->apellido = $request->apellido;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
            'user' => $user
        ], 200);
    }

    /**
     * Cambiar preferencia de tema (modo oscuro/claro)
     */
    public function cambiarTema(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'preferencia_tema' => 'required|in:light,dark',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->preferencia_tema = $request->preferencia_tema;
        $user->save();

        return response()->json([
            'message' => 'Tema actualizado exitosamente.',
            'preferencia_tema' => $user->preferencia_tema
        ], 200);
    }

    /**
     * Cambiar contraseña del usuario
     */
    public function cambiarPassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'password_actual' => 'required|string',
            'password_nueva' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.'
            ], 401);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($request->password_nueva);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente.'
        ], 200);
    }
}
