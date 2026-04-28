<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur a un des rôles autorisés
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles  Liste des rôles autorisés
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // si pas connecté
        if (!$request->user()) {
            return response()->json(['message'=>'Not authenticated'], 401);
        }

        // si le rôle de l'utilisateur n'est pas dans la liste
        if (!in_array($request->user()->role, $roles)) {
            return response()->json(['message'=>'Access denied'], 403);
        }

        return $next($request);
    }
}