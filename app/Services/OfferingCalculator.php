<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Money;

/** Illustration service. Purchase flows must recalculate and snapshot terms server-side. */
final class OfferingCalculator {
    /** Converts an exact minor-unit amount into a permitted quantity without floats. */
    public function quantityForAmount(array $offering, int $amountMinor): string {
        if ($amountMinor <= 0 || (int)$offering['share_price_minor'] <= 0) throw new \InvalidArgumentException('Enter a valid investment amount.');
        $precision = !empty($offering['fractional_shares_allowed']) ? min(8, (int)$offering['quantity_decimal_places']) : 0;
        $scale = 10 ** $precision;
        $scaled = intdiv($amountMinor * $scale, (int)$offering['share_price_minor']);
        if ($scaled <= 0) throw new \InvalidArgumentException('Amount is below one permitted unit.');
        if ($precision === 0) return (string)$scaled;
        return intdiv($scaled,$scale).'.'.str_pad((string)($scaled%$scale),$precision,'0',STR_PAD_LEFT);
    }
    public function calculate(array $offering, string $quantity, ?\DateTimeImmutable $purchasedAt=null): array {
        $this->assertQuantity($offering,$quantity); $scale=$this->currencyScale((string)$offering['currency_code']);$quantityMinor=$this->decimalToInt($quantity,8);$price=(int)$offering['share_price_minor'];
        $investmentMinor=intdiv($price*$quantityMinor,100000000);$profitMinor=0;$type=$offering['profit_type'];
        if($type==='PERCENTAGE')$profitMinor=intdiv($investmentMinor*$this->decimalToInt((string)$offering['projected_profit_value'],4),1000000);
        if($type==='FIXED'){ $fixedMinor=$this->decimalMoneyToMinor((string)$offering['projected_profit_value'],$scale);$profitMinor=($offering['fixed_profit_basis']??'')==='PER_SHARE'?intdiv($fixedMinor*$quantityMinor,100000000):$fixedMinor; }
        $date=$purchasedAt??new \DateTimeImmutable('today');$maturity=match($offering['duration_unit']){'DAYS'=>$date->modify('+'.(int)$offering['duration_value'].' days'),'WEEKS'=>$date->modify('+'.(int)$offering['duration_value'].' weeks'),'MONTHS'=>$date->modify('+'.(int)$offering['duration_value'].' months'),'YEARS'=>$date->modify('+'.(int)$offering['duration_value'].' years')};
        return ['quantity'=>$quantity,'investment'=>new Money($investmentMinor,(string)$offering['currency_code']),'projected_profit'=>new Money($profitMinor,(string)$offering['currency_code']),'projected_total'=>new Money($investmentMinor+$profitMinor,(string)$offering['currency_code']),'maturity_date'=>$maturity];
    }
    private function assertQuantity(array $o,string $q):void { if(!preg_match('/^\d+(?:\.\d{1,8})?$/',$q)||$this->decimalToInt($q,8)<=0)throw new \InvalidArgumentException('Quantity must be positive.');$places=strlen(strstr($q,'.')?:'')-1;if(empty($o['fractional_shares_allowed'])&&$places>0)throw new \InvalidArgumentException('This offering accepts whole shares only.');if($places>(int)$o['quantity_decimal_places'])throw new \InvalidArgumentException('Quantity has too many decimal places.');if($this->decimalToInt($q,8)<$this->decimalToInt((string)$o['minimum_purchase_quantity'],8))throw new \InvalidArgumentException('Quantity is below the offering minimum.');if(!empty($o['maximum_purchase_quantity'])&&$this->decimalToInt($q,8)>$this->decimalToInt((string)$o['maximum_purchase_quantity'],8))throw new \InvalidArgumentException('Quantity exceeds the offering maximum.'); }
    private function decimalToInt(string $value,int $scale):int { [$whole,$fraction]=array_pad(explode('.',$value,2),2,'');return ((int)$whole*(10**$scale))+(int)str_pad(substr($fraction,0,$scale),$scale,'0'); }
    private function decimalMoneyToMinor(string $value,int $scale):int{return $this->decimalToInt($value,$scale);}
    private function currencyScale(string $c):int{return in_array($c,['JPY','KRW'],true)?0:2;}
}
