<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // <-- ¡Importa tu modelo!
use Illuminate\Support\Facades\Hash; // <-- ¡Importa el Hasher!
use Illuminate\Support\Facades\Validator; // <-- ¡Importa el Validador!

class AuthController extends Controller
{

    /**
     * Registra un nuevo usuario en el sistema.
     */
    public function register(Request $request)
    {
        // 1. Validar los datos recibidos
        $validator = Validator::make($request->all(), [
            'nombre'    => 'required|string|max:255',
            'apellido'  => 'required|string|max:255',
            'dni'       => 'required|string|max:20|unique:users', // Único en la tabla 'users'
            'email'     => 'required|string|email|max:255|unique:users', // Único y formato email
            'password'  => 'required|string|min:8|confirmed', // Mínimo 8 caracteres y debe coincidir con 'password_confirmation'
            'rol'       => 'sometimes|string|in:cliente,recolector,admin', // Opcional, validar roles válidos
            'preferencia_tema' => 'sometimes|string', // 'sometimes' significa que es opcional
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Devuelve errores si la validación falla
        }

        // 2. Crear el usuario si la validación pasa
        $user = User::create([
            'nombre'    => $request->nombre,
            'apellido'  => $request->apellido,
            'dni'       => $request->dni,
            'email'     => $request->email,
            'password'  => Hash::make($request->password), // ¡Contraseña encriptada!
            'rol'       => $request->rol ?? 'cliente', // Por defecto: cliente
            'preferencia_tema' => $request->preferencia_tema ?? 'light', // Valor por defecto si no se envía
            'puntos'    => 0, // Iniciar con 0 puntos
        ]);

        // 3. Crear un token de API para el nuevo usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. Devolver la respuesta
        return response()->json([
            'message' => '¡Usuario registrado exitosamente!',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ], 201); // 201 = Created
    }


    /**
     * Inicia sesión de un usuario existente.
     */
    public function login(Request $request)
    {
        // 1. Validar los datos
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Buscar al usuario
        $user = User::where('email', $request->email)->first();

        // 3. Verificar al usuario y la contraseña
        //    Usamos Hash::check() para comparar la contraseña en texto plano
        //    con la contraseña encriptada en la base de datos.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401); // 401 = No autorizado
        }

        // 4. Si todo está bien, crear el token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Devolver la respuesta
        return response()->json([
            'message' => '¡Inicio de sesión exitoso!',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user // Devolvemos los datos del usuario
        ], 200); // 200 = OK
    }


    /**
     * Cierra la sesión del usuario eliminando su token actual.
     */
    public function logout(Request $request)
    {
        // 1. Eliminar el token actual del usuario
        $request->user()->currentAccessToken()->delete();

        // 2. Devolver respuesta
        return response()->json([
            'message' => '¡Sesión cerrada exitosamente!'
        ], 200);
    }

}