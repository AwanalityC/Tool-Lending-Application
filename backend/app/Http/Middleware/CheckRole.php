<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route berdasarkan peran. Contoh pemakaian: ->middleware('role:admin,petugas')
 * Pemeriksaan ini dilakukan di server, jadi tidak bisa dilewati dari aplikasi Flutter.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Anda tidak memiliki izin untuk mengakses fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}