<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Container,Request,Response};

final class CountryResolutionMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(empty($_SESSION['user_id']))app('sessions')->restore($request);
        $account=!empty($_SESSION['user_id'])?app('users')->find((int)$_SESSION['user_id']):null;
        $context=app('country_resolver')->resolve($request,$account);
        $context=app('languages')->resolve($context,$request,$account);
        Container::instance()->set('country_context',$context);
        return $next($request);
    }
}
