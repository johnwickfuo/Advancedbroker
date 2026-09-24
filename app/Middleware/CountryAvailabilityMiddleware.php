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
        if($r->method==='GET'){
            return $r->path==='/dashboard/deposit' || $r->path==='/dashboard/withdraw' || preg_match('#^/companies/[^/]+/buy$#',$r->path)===1;
        }
        if(str_starts_with($r->path,'/dashboard/deposit/')) return str_ends_with($r->path,'/cancel');
        if(str_starts_with($r->path,'/dashboard/withdraw/')) return str_ends_with($r->path,'/cancel');
        if(str_starts_with($r->path,'/dashboard/investments/')) return true;
        return $r->path==='/dashboard/deposit' || $r->path==='/dashboard/withdraw' || preg_match('#^/companies/[^/]+/buy(?:/review|/confirm)?$#',$r->path)===1;
    }
}
