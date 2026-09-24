<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Exceptions\HttpException;

final class CountryAccessService {
    public function assertAccess(CountryContext $context,int $resourceCountryId):void{if($context->id()!==$resourceCountryId)throw new HttpException(404,'This resource is not available in your Country Pack.');}
    public function canAccess(CountryContext $context,int $resourceCountryId):bool{return $context->id()===$resourceCountryId;}
}
