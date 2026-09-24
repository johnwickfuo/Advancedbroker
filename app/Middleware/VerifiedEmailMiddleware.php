<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Request,Response};

final class VerifiedEmailMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(!app('settings')->bool('require_email_verification'))return $next($request);
        $user=app('users')->find((int)($_SESSION['user_id']??0));if($user&&!empty($user['email_verified_at']))return $next($request);
        if($request->path==='/dashboard/security'||$request->path==='/dashboard/security/resend-verification'||$request->path==='/logout')return $next($request);
        $_SESSION['_flash'][]=['type'=>'warning','message'=>'Please verify your email address before continuing.'];
        return Response::redirect(route('verify-email'));
    }
}
