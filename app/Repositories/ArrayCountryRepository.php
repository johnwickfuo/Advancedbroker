<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Contracts\CountryRepository;

final class ArrayCountryRepository implements CountryRepository {
    /** @param list<array> $countries @param array<int,list<array>> $languages */
    public function __construct(private array $countries,private array $languages=[]) {}
    public function findById(int $id):?array{foreach($this->countries as $c)if((int)($c['id']??0)===$id)return$c;return null;}
    public function findByCode(string $code):?array{$code=strtoupper($code);foreach($this->countries as $c)if(strtoupper((string)($c['code']??''))===$code)return$c;return null;}
    public function findBySlug(string $slug):?array{$slug=strtolower($slug);foreach($this->countries as $c)if(strtolower((string)($c['slug']??''))===$slug)return$c;return null;}
    public function global():?array{foreach($this->countries as $c)if(!empty($c['is_global']))return$c;return null;}
    public function allEnabled():array{return array_values(array_filter($this->countries,fn(array $c):bool=>!empty($c['is_global'])||(!empty($c['is_active'])&&!empty($c['is_enabled']))));}
    public function all():array{return array_values($this->countries);}
    public function languagesFor(int $countryId):array{return $this->languages[$countryId]??[['code'=>'en','locale'=>'en','name'=>'English','native_name'=>'English','is_default'=>true]];}
    public function contentFor(int $countryId,string $languageCode,string $page):?array{return null;}
    public function setEnabled(string $code,bool $enabled):void{foreach($this->countries as &$c)if(strtoupper((string)($c['code']??''))===strtoupper($code)){$c['is_enabled']=$enabled?1:0;break;}unset($c);}
}
