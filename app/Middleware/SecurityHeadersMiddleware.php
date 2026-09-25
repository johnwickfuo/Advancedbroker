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
            'Content-Security-Policy'=>"default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: blob: https://unsplash.com https://images.unsplash.com https://*.smartsupp.com https://*.smartsuppchat.com https://*.smartsuppcdn.com; font-src 'self' data: https://*.smartsuppcdn.com; style-src 'self' 'unsafe-inline' 'nonce-".$nonce."' https://*.smartsupp.com https://*.smartsuppchat.com https://*.smartsuppcdn.com; script-src 'self' 'nonce-".$nonce."' https://*.smartsuppchat.com https://*.smartsuppcdn.com https://*.smartsupp.com; connect-src 'self' https://*.smartsupp.com https://*.smartsuppchat.com https://*.smartsuppcdn.com wss://*.smartsupp.com; frame-src https://*.smartsupp.com https://*.smartsuppchat.com https://*.smartsuppcdn.com; media-src 'self' https://*.smartsupp.com https://*.smartsuppcdn.com"
        ];
        if(str_starts_with((string)config('app.url'),'https://'))$headers['Strict-Transport-Security']='max-age=31536000; includeSubDomains';
        $response->headers=array_merge($headers,$response->headers);return$response;
    }
}
