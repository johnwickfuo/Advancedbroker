<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Exceptions\HttpException;
use App\Support\{Request,Response};

final class AdminMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(($_SESSION['user_role']??'')!=='super_admin')throw new HttpException(403,'Administrator access is required.');
        return $next($request);
    }
}
