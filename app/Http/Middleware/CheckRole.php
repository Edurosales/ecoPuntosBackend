<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // <-- Importante

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  // <-- Aceptamos el rol que queremos chequear
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Verificamos si el usuario está logueado Y si su 'rol'
        //    (de la base de datos) coincide con el $role que pedimos.
        if (!Auth::check() || Auth::user()->rol !== $role) {
            
            // 2. Si no coincide, le negamos el acceso.
            return response()->json([
                'message' => 'No autorizado. No tienes los permisos necesarios.'
            ], 403); // 403 = Prohibido (Forbidden)
        }

        // 3. Si coincide, dejamos que la petición continúe.
        return $next($request);
    }
}