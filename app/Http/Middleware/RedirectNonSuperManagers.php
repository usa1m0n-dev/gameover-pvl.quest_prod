<?php

namespace App\Http\Middleware;

use App\Filament\Resources\RecordResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectNonSuperManagers
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (
            $user &&
            ! $user->hasRole('supermanager') &&
            $request->routeIs('filament.admin.pages.dashboard') // 'admin' - это ID вашей панели
        ) {
            return redirect()->to(RecordResource::getUrl());
        }

        return $next($request);
    }
}