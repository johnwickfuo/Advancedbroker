<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Exceptions\NotFoundException;
use App\Support\{Request,Response};

final class CompanyController {
    private function layout(): string {
        if(empty($_SESSION['user_id']))return 'layouts.public';
        $user=app('users')->find((int)$_SESSION['user_id']);
        return $user&&($user['role']??'user')!=='super_admin'?'layouts.dashboard':'layouts.public';
    }

    public function index(Request $request):Response{
        $context=country();
        $filters=['q'=>(string)$request->input('q',''),'industry'=>(string)$request->input('industry',''),'exchange'=>(string)$request->input('exchange',''),'featured'=>(string)$request->input('featured',''),'sort'=>(string)$request->input('sort','featured')];
        $page=max(1,(int)$request->input('page',1));
        $catalogue=app('companies')->catalogue($context,$filters,$page);
        $repo=app('companies')->repo();
        return new Response(app('view')->render('companies.index',[
            'title'=>'Investment opportunities','description'=>'Company information and current investment opportunities.',
            'country'=>$context,'filters'=>$filters,'companies'=>$catalogue['items'],'pagination'=>$catalogue['pagination'],
            'industries'=>$repo->industries($context->id()),'exchanges'=>$repo->exchanges($context->id()),
            'media'=>app('country_media')->forCountry($context->country)
        ],$this->layout()));
    }

    public function show(Request $request):Response{
        $company=app('companies')->company(country(),(string)$request->route('company'));
        if(!$company)throw new NotFoundException('Investment opportunity not found.');
        $repo=app('companies')->repo();
        return new Response(app('view')->render('companies.show',[
            'title'=>$company['display_name'].' investment opportunity',
            'description'=>$company['short_description']?:'Company information and current investment terms.',
            'company'=>$company,'images'=>$repo->images((int)$company['id']),'sources'=>$repo->sources((int)$company['id']),
            'country'=>country(),'media'=>app('country_media')->forCountry(country()->country)
        ],$this->layout()));
    }
}
