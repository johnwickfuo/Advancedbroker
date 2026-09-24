<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};use App\Validation\Validator;
final class PublicContentController extends Controller {
    public function page(Request $r):Response{$key=(string)$r->route('page');$country=country();$content=app('content')->page($country->country,$country->languageCode,$key);$titles=['about'=>'About','how-it-works'=>'How it works'];return $this->view('public.page',['title'=>$content['page_title']??($titles[$key]??'Information'),'description'=>$content['meta_description']??'','content'=>$content,'pageKey'=>$key],'layouts.public');}
    public function faq(Request $r):Response{return $this->view('public.faq',['title'=>'Frequently asked questions','faqs'=>app('faqs')->public(country()->country,country()->languageCode)],'layouts.public');}
    public function contact(Request $r):Response{return $this->view('public.contact',['title'=>'Contact','branding'=>app('branding')->forCountry(country()->country)],'layouts.public');}
    public function submitContact(Request $r):Response{$data=$r->all();$v=new Validator($data);$v->validate(['name'=>'required|max:190','email'=>'required|email|max:190','subject'=>'required|max:255','message'=>'required|max:5000','website'=>'optional|max:0']);if($v->fails()||!empty($data['website'])){$this->flash('error','Please review the form and try again.');return Response::redirect(route('contact'));}$key='contact:'.$r->ip().':'.floor(time()/600);$count=(int)app('cache')->get($key,0);if($count>=5){$this->flash('error','Please wait before sending another message.');return Response::redirect(route('contact'));}app('cache')->put($key,$count+1,700);app('contacts')->submit($data,country()->id(),!empty($_SESSION['user_id'])?(int)$_SESSION['user_id']:null,$r->ip(),$r->userAgent());$this->flash('success','Your message has been received.');return Response::redirect(route('contact'));}
    public function legal(Request $r):Response{$type=(string)$r->route('type',$r->input('type',''));$doc=app('legal')->document(country()->country,country()->languageCode,$type);return $this->view('public.legal',['title'=>$doc['title']??'Legal information','document'=>$doc,'type'=>$type],'layouts.public');}
    public function license(Request $r):Response{return $this->view('public.license',['title'=>'License','license'=>app('licenses')->current(country()->id())],'layouts.public');}
}
