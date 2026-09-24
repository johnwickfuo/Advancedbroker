<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class AdminSettingsController extends Controller {
    public function index(Request $request): Response{return $this->view('admin.settings',['title'=>'Security settings','requireEmailVerification'=>app('settings')->bool('require_email_verification')],'layouts.admin');}
    public function emailVerification(Request $request): Response{$enabled=!empty($request->input('enabled'));$old=app('settings')->bool('require_email_verification');app('settings')->set('require_email_verification',['enabled'=>$enabled]);app('audit')->record((int)$_SESSION['user_id'],'settings.email_verification_changed','settings','require_email_verification',['enabled'=>$old],['enabled'=>$enabled],null,$request);$this->flash('success','Email verification requirement updated.');return Response::redirect(route('admin.settings'));}
}
