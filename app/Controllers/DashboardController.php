<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class DashboardController extends Controller {
    public function index(Request $request): Response { $user=app('users')->find((int)$_SESSION['user_id']);return $this->view('dashboard.index',['title'=>__('dashboard.overview'),'user'=>$user,'notifications'=>app('notifications')->recent((int)$user['id']),'notice'=>app('account_access')->notice($user),'wallet'=>app('wallets')->walletFor($user,country()->country),'portfolio'=>app('investments')->portfolio((int)$user['id']),'kycApproved'=>app('kyc')->approved((int)$user['id'],country()->country),'notificationCount'=>app('notifications')->unreadCount((int)$user['id'])],'layouts.dashboard'); }
}
