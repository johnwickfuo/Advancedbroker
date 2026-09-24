<?php
declare(strict_types=1);
namespace App\Support;

final readonly class Money {
    public function __construct(public int $minor, public string $currency = 'USD') {}
    public static function parse(string $amount, string $currency = 'USD', int $scale = 2): self { $amount = preg_replace('/[^0-9.-]/', '', trim($amount)) ?? ''; if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) throw new \InvalidArgumentException('Invalid money amount.'); $negative = str_starts_with($amount, '-'); $amount = ltrim($amount, '-'); [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, ''); if (strlen($fraction) > $scale) throw new \InvalidArgumentException('Too many decimal places.'); $minor = ((int)$whole * (10 ** $scale)) + (int)str_pad($fraction, $scale, '0'); return new self($negative ? -$minor : $minor, $currency); }
    public function add(self $other): self { $this->same($other); return new self($this->minor + $other->minor, $this->currency); }
    public function subtract(self $other): self { $this->same($other); return new self($this->minor - $other->minor, $this->currency); }
    public function compare(self $other): int { $this->same($other); return $this->minor <=> $other->minor; }
    public function percentage(int $basisPoints): self { return new self(intdiv($this->minor * $basisPoints, 10000), $this->currency); }
    public function format(string $symbol = '', int $scale = 2): string { $negative = $this->minor < 0; if($scale===0)return $symbol.($negative?'-':'').number_format(abs($this->minor),0,'.',','); $digits = (string) abs($this->minor); $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT); $whole = substr($digits, 0, -$scale); $fraction = substr($digits, -$scale); $whole = number_format((int)$whole, 0, '.', ','); return $symbol . ($negative ? '-' : '') . $whole . '.' . $fraction; }
    private function same(self $other): void { if ($this->currency !== $other->currency) throw new \InvalidArgumentException('Currencies must match.'); }
}
