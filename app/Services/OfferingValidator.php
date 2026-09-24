<?php
declare(strict_types=1);
namespace App\Services;

final class OfferingValidator {
    public function validate(array $input): array {
        $errors=[];$price=(string)($input['share_price']??'');$min=(string)($input['minimum_purchase_quantity']??'');$max=(string)($input['maximum_purchase_quantity']??'');$profit=(string)($input['projected_profit_value']??'');$duration=(string)($input['duration_value']??'');$fractional=isset($input['fractional_shares_allowed'])&&(string)$input['fractional_shares_allowed']==='1';$precision=(int)($input['quantity_decimal_places']??0);
        if(!preg_match('/^\d+(?:\.\d{1,2})?$/',$price)||self::compare($price,'0')<=0)$errors['share_price']='Investment price must be greater than zero.';
        if(!preg_match('/^\d+(?:\.\d{1,8})?$/',$min)||self::compare($min,'0')<=0)$errors['minimum_purchase_quantity']='Minimum quantity must be greater than zero.';
        if($max!==''&&(!preg_match('/^\d+(?:\.\d{1,8})?$/',$max)||self::compare($max,$min)<0))$errors['maximum_purchase_quantity']='Maximum quantity cannot be below the minimum.';
        if(!in_array($input['profit_type']??'', ['FIXED','PERCENTAGE'],true))$errors['profit_type']='Choose a profit type.';
        if(!preg_match('/^\d+(?:\.\d{1,8})?$/',$profit)||self::compare($profit,'0')<0)$errors['projected_profit_value']='Projected profit must be a non-negative decimal.';
        if(($input['profit_type']??'')==='PERCENTAGE'&&self::compare($profit,'10000')>0)$errors['projected_profit_value']='Projected percentage is outside the permitted range.';
        if(($input['profit_type']??'')==='FIXED'&&!in_array($input['fixed_profit_basis']??'', ['PER_SHARE','PER_INVESTMENT'],true))$errors['fixed_profit_basis']='Choose how fixed profit is applied.';
        if(!ctype_digit($duration)||(int)$duration<1)$errors['duration_value']='Duration must be a positive whole number.';
        if(!in_array($input['duration_unit']??'', ['DAYS','WEEKS','MONTHS','YEARS'],true))$errors['duration_unit']='Choose a duration unit.';
        if($precision<0||$precision>8)$errors['quantity_decimal_places']='Fractional precision must be between 0 and 8.';
        if(!$fractional&&$precision!==0)$errors['quantity_decimal_places']='Whole-share offerings use zero decimal places.';
        return $errors;
    }
    private static function compare(string $left,string $right):int{$scale=max(strlen(strstr($left,'.')?:'')-1,strlen(strstr($right,'.')?:'')-1);$toInt=static fn(string $v):int=>((int)str_replace('.','',$v.str_repeat('0',max(0,$scale-(strlen(strstr($v,'.')?:'')-1)))));return $toInt($left)<=>$toInt($right);}
}
