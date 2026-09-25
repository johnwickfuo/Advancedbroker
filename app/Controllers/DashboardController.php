<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class DashboardController extends Controller {
    public function index(Request $request): Response {
        $user=app('users')->find((int)$_SESSION['user_id']);
        if(!$user)return Response::redirect(route('login'));
        $country=country()->country;
        $wallet=app('wallets')->walletFor($user,$country);
        $notice=app('country_access')->message(country(),$user);
        return $this->view('dashboard.index',[
            'title'=>'Dashboard',
            'user'=>$user,
            'wallet'=>$wallet,
            'notice'=>$notice,
            'portfolio'=>app('investments')->portfolioSummary((int)$user['id']),
            'kycApproved'=>app('kyc')->approved((int)$user['id'],country()->id()),
            'aiTrades'=>app('ai_trading')->activeForUser((int)$user['id'],4),
            'featuredCompanies'=>app('companies')->featured(country(),5),
        ],'layouts.dashboard');
    }
}
