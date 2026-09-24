<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Exceptions\NotFoundException;use App\Support\{Request,Response};
final class CompanyController extends Controller {
    public function index(Request $request):Response{$context=country();$filters=['q'=>(string)$request->input('q',''),'industry'=>(string)$request->input('industry',''),'exchange'=>(string)$request->input('exchange',''),'featured'=>(string)$request->input('featured',''),'sort'=>(string)$request->input('sort','featured')];$page=max(1,(int)$request->input('page',1));$catalogue=app('companies')->catalogue($context,$filters,$page);$repo=app('companies')->repo();return $this->view('companies.index',['title'=>'Investment opportunities','description'=>'Country-specific company information and platform investment offerings.','country'=>$context,'filters'=>$filters,'companies'=>$catalogue['items'],'pagination'=>$catalogue['pagination'],'industries'=>$repo->industries($context->id()),'exchanges'=>$repo->exchanges($context->id())]);}
    public function show(Request $request):Response{$company=app('companies')->company(country(),(string)$request->route('company'));if(!$company)throw new NotFoundException('Investment opportunity not found.');$repo=app('companies')->repo();return $this->view('companies.show',['title'=>$company['display_name'].' investment opportunity','description'=>$company['short_description']?:'Company information and platform investment offering.','company'=>$company,'images'=>$repo->images((int)$company['id']),'sources'=>$repo->sources((int)$company['id']),'country'=>country()]);}
}
