<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class AdminUserController extends Controller {
    public function index(Request $request): Response { $query=trim((string)$request->input('q',''));return $this->view('admin.users',['title'=>'Users','users'=>app('users')->search($query),'query'=>$query],'layouts.admin'); }
    public function show(Request $request): Response { $user=app('users')->find((int)$request->route('user'));if(!$user)return new Response(app('view')->render('errors.404',['title'=>'User not found'],'layouts.admin'),404);return $this->view('admin.user-detail',['title'=>'User account','user'=>$user,'countries'=>app('countries')->allEnabled(),'events'=>app('security_events')->recent((int)$user['id'])],'layouts.admin'); }
    public function country(Request $request): Response { $user=$this->target($request);$country=app('countries')->enabledById((int)$request->input('country_id',0));if(!$user||!$country)return $this->bad('A valid enabled market is required.',$user);if((int)($user['assigned_country_id']??$user['country_id'])!==(int)$country['id']&&app('wallets')->exposure((int)$user['id']))return $this->bad('Country reassignment is blocked while this user has financial history or a pending deposit. Resolve financial exposure first; no FX conversion is performed.',$user);$old=['country_id'=>$user['assigned_country_id']??$user['country_id']];app('users')->update((int)$user['id'],['assigned_country_id'=>(int)$country['id'],'country_id'=>(int)$country['id'],'country_assignment_source'=>'ADMIN']);app('countries')->forget($country);app('sessions')->revokeAll((int)$user['id']);app('audit')->record((int)$_SESSION['user_id'],'user.country_changed','user',(int)$user['id'],$old,['country_id'=>(int)$country['id'],'country'=>$country['slug']],trim((string)$request->input('reason',''))?:null,$request);app('notifications')->create((int)$user['id'],'country_changed','Investment region updated','Your investment region is now '.$country['name'].'.');$this->flash('success','User market updated; their active sessions were invalidated.');return Response::redirect(route('admin.users.show',['user'=>$user['id']])); }
    public function popup(Request $request): Response {
        $user=$this->target($request);
        if(!$user)return Response::redirect(route('admin.users.index'));
        $title=trim((string)$request->input('title',''));
        $body=trim((string)$request->input('body',''));
        if($title===''||$body==='')return $this->bad('Popup title and message are required.',$user);
        app('notifications')->create((int)$user['id'],'admin_popup',$title,$body);
        app('audit')->record((int)$_SESSION['user_id'],'user.popup_sent','user',(int)$user['id'],[],['title'=>$title],null,$request);
        $this->flash('success','Popup notification queued for '.$user['email'].'.');
        return Response::redirect(route('admin.users.show',['user'=>$user['id']]));
    }
    public function status(Request $request): Response { $user=$this->target($request);$status=strtolower((string)$request->input('account_status',''));$reason=trim((string)$request->input('reason',''));if(!$user||!in_array($status,['active','suspended','restricted'],true)||(($status==='suspended'||$status==='restricted')&&$reason===''))return $this->bad('Status and a reason are required for suspension or restriction.',$user);$old=['account_status'=>$user['account_status']];app('users')->update((int)$user['id'],['account_status'=>$status,'account_restriction_reason'=>$status==='active'?null:$reason]);app('audit')->record((int)$_SESSION['user_id'],'user.status_changed','user',(int)$user['id'],$old,['account_status'=>$status],$reason?:null,$request);if($status!=='active')app('sessions')->revokeAll((int)$user['id']);app('notifications')->create((int)$user['id'],'account_status','Account status updated',$status==='active'?'Your account is active.':'Your account has been '.$status.'.');$this->flash('success','Account status updated.');return Response::redirect(route('admin.users.show',['user'=>$user['id']])); }
    private function target(Request $request): ?array { $user=app('users')->find((int)$request->route('user'));return $user && $user['role']!=='super_admin'?$user:null; }
    private function bad(string $message,?array $user): Response { $this->flash('error',$message);return Response::redirect($user?route('admin.users.show',['user'=>$user['id']]):route('admin.users.index')); }
}
