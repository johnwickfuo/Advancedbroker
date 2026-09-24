<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Exceptions\HttpException;
use App\Support\{Request,Response};

final class RateLimitMiddleware {
    public function handle(Request $request,callable $next):Response{
        if($request->method==='GET')return$next($request);
        $limit=max(1,(int)config('app.rate_limit_per_minute',60));$bucket=(int)floor(time()/60);
        $key='rate:'.$request->ip().':'.$request->path.':'.$bucket;$count=(int)app('cache')->get($key,0);
        if($count>=$limit)throw new HttpException(429,'Too many requests. Please try again shortly.');
        app('cache')->put($key,$count+1,90);return$next($request);
    }
}
