<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class AdminPreviewController {public function start(Request $r):Response{$country=app('countries')->byId((int)$r->route('country'));if(!$country)return new Response('Not found',404);$language=(string)$r->input('language','');if($language!==''&&!app('countries')->supportsLanguage($country,$language))$language='';$_SESSION['admin_country_preview']=['country_id'=>(int)$country['id'],'language'=>$language];$page=(string)$r->input('page','/');return Response::redirect(str_starts_with($page,'/')&&!str_starts_with($page,'//')?$page:'/');}public function stop(Request $r):Response{unset($_SESSION['admin_country_preview']);return Response::redirect(route('admin.countries.index'));}}
