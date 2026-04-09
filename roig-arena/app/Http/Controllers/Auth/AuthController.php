<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     */
    public function register(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            /*
                En Laravel, confirmed significa que además de password debe venir un campo llamado password_confirmation con el mismo valor.
                Si no existe ese campo, o no coincide, falla la validación 422.
            */
        ]);

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Iniciar sesión
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales son incorrectas.',
            ], 401);
        }

        // Eliminar tokens anteriores (opcional)
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        // Eliminar todos los tokens del usuario
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request)
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }
}
