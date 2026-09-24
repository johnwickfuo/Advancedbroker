<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Support\{Money,Request,Response};

final class WithdrawalController extends Controller {
    private function user():array{return app('users')->find((int)($_SESSION['user_id']??0))??throw new \RuntimeException('User not found.');}
    private function amount(Request $r):int{$currency=country()->currencyCode();$scale=in_array($currency,['JPY','KRW'],true)?0:2;return Money::parse((string)$r->input('amount',''),$currency,$scale)->minor;}
    public function index(Request $r):Response{
        $user=$this->user();$eligible=app('kyc')->canWithdraw($user,country()->country);$methods=$eligible?app('withdrawals')->methods(country()->id()):[];
        $selected=(int)$r->input('method',0);$method=$selected&&$eligible?app('withdrawals')->method(country()->id(),$selected):null;
        return $this->view('withdrawals.index',['title'=>'Withdraw funds','eligible'=>$eligible,'methods'=>$methods,'method'=>$method,'fields'=>$method?app('forms')->definition($method['form_id']?(int)$method['form_id']:null):[],'wallet'=>app('wallets')->walletFor($user,country()->country)],'layouts.dashboard');
    }
    public function submit(Request $r):Response{
        try{$result=app('withdrawals')->submit($this->user(),country()->country,(int)$r->input('method_id'),$this->amount($r),$r->all());$this->flash('success','Withdrawal request submitted. Reference: '.$result['reference']);return $this->redirect('dashboard.withdraw.detail',['withdrawal'=>$result['reference']]);}
        catch(\Throwable $e){$this->flash('error',$e->getMessage());return $this->redirect('dashboard.withdraw');}
    }
    public function history(Request $r):Response{$data=app('withdrawals')->forUser((int)$this->user()['id'],max(1,(int)$r->input('page',1)));return $this->view('withdrawals.history',['title'=>'Withdrawal history','withdrawals'=>$data['items'],'total'=>$data['total']],'layouts.dashboard');}
    public function detail(Request $r):Response{$w=app('withdrawals')->findForUser((int)$this->user()['id'],(string)$r->route('withdrawal'));if(!$w)throw new NotFoundException('Withdrawal request not found.');return $this->view('withdrawals.detail',['title'=>'Withdrawal '.$w['reference'],'withdrawal'=>$w,'values'=>app('withdrawals')->values((int)$w['id'])],'layouts.dashboard');}
    public function cancel(Request $r):Response{try{app('withdrawals')->cancel((int)$this->user()['id'],(string)$r->route('withdrawal'));$this->flash('success','Withdrawal request cancelled and reserved funds released.');}catch(\Throwable $e){$this->flash('error',$e->getMessage());}return $this->redirect('dashboard.withdrawals');}
}
