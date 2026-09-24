<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class SecurityController extends Controller {
    public function index(Request $request): Response { $user=app('users')->find((int)$_SESSION['user_id']);$codes=$_SESSION['recovery_codes_once']??[];unset($_SESSION['recovery_codes_once']);return $this->view('dashboard.security',['title'=>'Security','user'=>$user,'sessions'=>app('sessions')->sessions((int)$user['id']),'events'=>app('security_events')->recent((int)$user['id']),'recoveryCodes'=>$codes,'verificationRequired'=>app('settings')->bool('require_email_verification')],'layouts.dashboard'); }
    public function revoke(Request $request): Response { app('sessions')->revoke((int)$_SESSION['user_id'],(int)$request->route('session'));app('security_events')->record((int)$_SESSION['user_id'],'session_revoked',$request);$this->flash('success','Session revoked.');return Response::redirect(route('dashboard.security')); }
    public function revokeOthers(Request $request): Response { app('sessions')->revokeOthers((int)$_SESSION['user_id'],(string)$_SESSION['auth_session_hash']);app('security_events')->record((int)$_SESSION['user_id'],'other_sessions_revoked',$request);$this->flash('success','Other sessions have been revoked.');return Response::redirect(route('dashboard.security')); }
}
