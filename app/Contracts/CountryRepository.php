<?php
declare(strict_types=1);
namespace App\Contracts;

interface CountryRepository {
    public function findById(int $id): ?array;
    public function findByCode(string $code): ?array;
    public function findBySlug(string $slug): ?array;
    public function global(): ?array;
    /** @return list<array> */
    public function allEnabled(): array;
    /** @return list<array> */
    public function all(): array;
    /** @return list<array{code:string,locale:string,name:string,native_name:string,is_default:bool}> */
    public function languagesFor(int $countryId): array;
    public function contentFor(int $countryId, string $languageCode, string $page): ?array;
}
