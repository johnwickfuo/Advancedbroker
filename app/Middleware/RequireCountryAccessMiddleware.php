<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Request,Response};

final class RequireCountryAccessMiddleware {
    public function handle(Request $request,callable $next):Response{
        $resourceCountry=(int)$request->input('country_id',0);
        if($resourceCountry>0)app('country_access')->assertAccess(country(),$resourceCountry);
        return$next($request);
    }
}
