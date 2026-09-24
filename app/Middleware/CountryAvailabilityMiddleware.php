<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Exceptions\HttpException;
use App\Support\{Request,Response};

final class CountryAvailabilityMiddleware {
    public function handle(Request $request,callable $next):Response{
        $country=country()->country;
        $enabled=!empty($country['is_global'])||(!empty($country['is_active'])&&!empty($country['is_enabled']));
        if(!$enabled&&$this->sensitive($request))throw new HttpException(403,'Financial actions are temporarily unavailable for your assigned Country Pack. Please contact support or an administrator.');
        return $next($request);
    }
    private function sensitive(Request $r):bool{
        if(preg_match('#^/companies/[^/]+/buy#',$r->path))return true;
        if(str_starts_with($r->path,'/dashboard/deposit')||str_starts_with($r->path,'/dashboard/withdraw'))return true;
        return $r->method!=='GET'&&str_starts_with($r->path,'/dashboard/investments/');
    }
}
