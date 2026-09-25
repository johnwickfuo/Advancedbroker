<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Support\{Money,Request,Response};

final class AiTradingController extends Controller
{
    private function user(): array
    {
        $user=app('users')->find((int)($_SESSION['user_id']??0));
        if(!$user) throw new \RuntimeException('User account unavailable.');
        return $user;
    }

    public function catalogue(Request $request): Response
    {
        $currency=country()->currencyCode();
        $categories=app('ai_trading')->publicCategories($currency);
        $wallet=null;
        if(!empty($_SESSION['user_id'])){
            $wallet=app('wallets')->walletFor($this->user(),country()->country);
        }
        return $this->view('ai-trading.catalogue',[
            'title'=>'AI Trading Codes',
            'description'=>'Choose an AI trading code category with fixed profit terms and a defined activation duration.',
            'categories'=>$categories,
            'wallet'=>$wallet,
            'currency'=>$currency,
        ]);
    }

    public function buy(Request $request): Response
    {
        try{
            $purchase=app('ai_trading')->buy($this->user(),country()->country,(string)$request->route('category'));
            $this->flash('success','Your AI trading code has been purchased.');
            return Response::redirect(route('dashboard.ai-trading.show',['purchase'=>$purchase['public_id']]));
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
            return Response::redirect(route('ai-trading.catalogue'));
        }
    }

    public function dashboard(Request $request): Response
    {
        $user=$this->user();
        return $this->view('ai-trading.dashboard',[
            'title'=>'AI Trading',
            'trades'=>app('ai_trading')->forUser((int)$user['id']),
            'categories'=>app('ai_trading')->publicCategories(country()->currencyCode()),
            'wallet'=>app('wallets')->walletFor($user,country()->country),
        ],'layouts.dashboard');
    }

    public function show(Request $request): Response
    {
        $purchase=app('ai_trading')->findForUser((int)$this->user()['id'],(string)$request->route('purchase'));
        if(!$purchase) throw new NotFoundException('AI trading purchase not found.');
        return $this->view('ai-trading.show',[
            'title'=>$purchase['category_name'].' AI trading code',
            'trade'=>$purchase,
        ],'layouts.dashboard');
    }

    public function activate(Request $request): Response
    {
        $purchase=(string)$request->route('purchase');
        try{
            app('ai_trading')->activate((int)$this->user()['id'],$purchase,(string)$request->input('code',''));
            $this->flash('success','AI trading has been activated. Your timed trading cycle is now running.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return Response::redirect(route('dashboard.ai-trading.show',['purchase'=>$purchase]));
    }
}
