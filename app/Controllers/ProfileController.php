<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};use App\Validation\Validator;
final class ProfileController extends Controller {
    public function edit(Request $request): Response { $user=app('users')->find((int)$_SESSION['user_id']);return $this->view('dashboard.profile',['title'=>'Profile','user'=>$user,'languages'=>app('countries')->languages(country()->country)],'layouts.dashboard'); }
    public function update(Request $request): Response { $data=$request->all();$v=new Validator($data);$v->validate(['first_name'=>'required|max:100','last_name'=>'required|max:100','phone'=>'optional|max:40','language'=>'required']);$allowed=app('countries')->languages(country()->country);$codes=array_column($allowed,'code');if($v->fails()||!in_array((string)($data['language']??''),$codes,true)){$_SESSION['_errors']=$v->errors()?:['language'=>['Selected language is not available for this investment region.']];return Response::redirect(route('dashboard.profile'));}$languageId=app('users')->languageId((string)$data['language']);app('users')->update((int)$_SESSION['user_id'],['first_name'=>trim($data['first_name']),'last_name'=>trim($data['last_name']),'phone'=>trim((string)($data['phone']??''))?:null,'preferred_language_id'=>$languageId]);$_SESSION['preferred_language_code']=$data['language'];$this->flash('success','Profile updated.');return Response::redirect(route('dashboard.profile')); }
}
