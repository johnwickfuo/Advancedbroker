<?php
declare(strict_types=1);
namespace App\Services;

final class AccountAccessService {
    public function canLogin(array $user):bool{return in_array(strtolower((string)($user['account_status']??'')),['active','restricted'],true);}
    public function mayPerformFinancialAction(array $user):bool{return strtolower((string)($user['account_status']??''))==='active';}
    public function canUseFinancialFeatures(array $user):bool{return $this->mayPerformFinancialAction($user);}
    public function notice(array $user):?string{return match(strtolower((string)($user['account_status']??''))){'restricted'=>(string)($user['account_restriction_reason']??'Your account currently has restrictions.'),'suspended'=>(string)($user['account_restriction_reason']??'Your account is suspended.'),default=>null};}
}
