<?php
declare(strict_types=1);
namespace App\Data;

final readonly class GeoLocation {
    public function __construct(
        public ?string $countryCode,
        public ?string $countryName,
        public string $source,
        public bool $succeeded
    ) {}
    public static function unknown(string $source='unknown'): self { return new self(null,null,$source,false); }
}
