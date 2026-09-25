<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\{Request,Response};

final class UserMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (($_SESSION['user_role'] ?? '') === 'super_admin') {
            return Response::redirect(route('admin.index'));
        }

        return $next($request);
    }
}
