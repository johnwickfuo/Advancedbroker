<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Request,Response};

final class AuthMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(!app('sessions')->restore($request)){
            if($request->method==='GET')$_SESSION['intended_url']=$request->path;
            foreach(['user_id','user_role','auth_session_hash','assigned_country_id','country_id'] as $key)unset($_SESSION[$key]);
            return Response::redirect(route('login'));
        }
        return $next($request);
    }
}
