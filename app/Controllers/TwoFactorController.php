<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};

final class TwoFactorController extends Controller {
    private function user(): array { return app('users')->find((int)($_SESSION['user_id']??0)) ?? throw new \RuntimeException('User not found.'); }
    public function challenge(Request $request):Response{if(empty($_SESSION['two_factor_challenge']))return Response::redirect(route('login'));return $this->view('auth.two-factor-challenge',['title'=>'Security check'],'layouts.auth');}
    public function verifyChallenge(Request $request):Response{
        if(!app('auth')->completeTwoFactor((string)$request->input('code',''),$request)){ $this->flash('error','That authentication code is invalid or expired.');return Response::redirect(route('two-factor.challenge')); }
        return Response::redirect(($_SESSION['user_role']??'')==='super_admin'?route('admin.index'):route('dashboard.index'));
    }
    public function setup(Request $request):Response{
        $user=$this->user();if(!empty($user['two_factor_enabled_at'])){ $this->flash('success','Two-factor authentication is already enabled.');return Response::redirect(route('dashboard.security')); }
        $setup=app('two_factor')->beginSetup($user);$setup['qr']=app('qr')->dataUri($setup['uri']);
        return $this->view('dashboard.two-factor-setup',['title'=>'Set up two-factor authentication','setup'=>$setup],'layouts.dashboard');
    }
    public function confirm(Request $request):Response{
        $codes=app('two_factor')->confirmSetup($this->user(),(string)$request->input('code',''));
        if(!$codes){$this->flash('error','The confirmation code is invalid or expired.');return Response::redirect(route('dashboard.two-factor.setup'));}
        $_SESSION['recovery_codes_once']=$codes;$this->flash('success','Two-factor authentication is now enabled. Save your recovery codes.');return Response::redirect(route('dashboard.security'));
    }
    public function disable(Request $request):Response{
        if(!app('two_factor')->disable($this->user(),(string)$request->input('code',''))){$this->flash('error','The authentication code is invalid.');return Response::redirect(route('dashboard.security'));}
        $this->flash('success','Two-factor authentication has been disabled.');return Response::redirect(route('dashboard.security'));
    }
    public function regenerate(Request $request):Response{
        $codes=app('two_factor')->regenerate($this->user(),(string)$request->input('code',''));if(!$codes){$this->flash('error','The authentication code is invalid.');return Response::redirect(route('dashboard.security'));}
        $_SESSION['recovery_codes_once']=$codes;$this->flash('success','New recovery codes generated.');return Response::redirect(route('dashboard.security'));
    }
}
