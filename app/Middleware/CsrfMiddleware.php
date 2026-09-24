<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Exceptions\HttpException;
use App\Support\{Request,Response};

final class CsrfMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(!in_array($request->method,['GET','HEAD','OPTIONS'],true)){
            $token=(string)$request->input('_token',$request->header('X-CSRF-Token',''));
            if(!app('csrf')->valid($token))throw new HttpException(419,'Your session token has expired. Please try again.');
        }
        return $next($request);
    }
}
