<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Request,Response};

final class GuestMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(app('sessions')->restore($request))return Response::redirect(route('dashboard.index'));
        return $next($request);
    }
}
