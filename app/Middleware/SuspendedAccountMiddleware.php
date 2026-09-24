<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Exceptions\HttpException;
use App\Support\{Request,Response};

final class SuspendedAccountMiddleware {
    public function handle(Request $request,callable $next):Response{
        $user=app('users')->find((int)($_SESSION['user_id']??0));if(!$user)throw new HttpException(403,'Account access is unavailable.');
        $status=strtolower((string)($user['account_status']??''));
        if(in_array($status,['suspended','closed','pending'],true))throw new HttpException(403,'This account is not currently permitted to use this area.');
        if($status==='restricted'&&$this->sensitive($request))throw new HttpException(403,(string)($user['account_restriction_reason']??'Financial actions are restricted on this account.'));
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
