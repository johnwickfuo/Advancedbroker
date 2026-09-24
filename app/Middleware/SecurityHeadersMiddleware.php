<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Request,Response};

final class SecurityHeadersMiddleware {
    public function handle(Request $request,callable $next):Response{
        $response=$next($request);$nonce=app('theme')->nonce();
        $headers=[
            'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'DENY','Referrer-Policy'=>'strict-origin-when-cross-origin',
            'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy'=>"default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; font-src 'self' data:; style-src 'self' 'nonce-".$nonce."'; script-src 'self'; connect-src 'self'"
        ];
        if(str_starts_with((string)config('app.url'),'https://'))$headers['Strict-Transport-Security']='max-age=31536000; includeSubDomains';
        $response->headers=array_merge($headers,$response->headers);return$response;
    }
}
