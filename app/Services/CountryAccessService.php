<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Exceptions\HttpException;

final class CountryAccessService {
    public function assertAccess(CountryContext $context,int $resourceCountryId):void{
        if($context->id()!==$resourceCountryId)throw new HttpException(404,'This resource is not available for your account.');
    }

    public function canAccess(CountryContext $context,int $resourceCountryId):bool{
        return $context->id()===$resourceCountryId;
    }

    public function message(CountryContext $context,array $user=[]):?string{
        $country=$context->country;
        $enabled=!empty($country['is_global'])||(!empty($country['is_active'])&&!empty($country['is_enabled']));
        if($enabled)return null;
        return 'Some financial actions are temporarily unavailable for your account. Your existing account history remains accessible.';
    }
}
