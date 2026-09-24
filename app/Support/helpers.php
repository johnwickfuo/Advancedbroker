<?php
declare(strict_types=1);
use App\Support\Container;
function env(string $key, mixed $default = null): mixed { $value = $_ENV[$key] ?? getenv($key); return ($value === false || $value === null || $value === '') ? $default : $value; }
function app(?string $id = null): mixed { $container = Container::instance(); return $id ? $container->get($id) : $container; }
function config(string $key, mixed $default = null): mixed { $value = app('config'); foreach (explode('.', $key) as $part) { if (!is_array($value) || !array_key_exists($part, $value)) return $default; $value = $value[$part]; } return $value; }
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(int $minor): string { return country_money(new \App\Support\Money($minor, country()->currencyCode())); }
function route(string $name, array $parameters = []): string { return app('router')->url($name, $parameters); }
function url(string $path = ''): string { return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function country(): \App\Data\CountryContext { return app('country_context'); }
function __(string $key, array $replace = []): string { $context = country(); return app('translator')->get($key, $context->languageCode, app('countries')->defaultLanguage($context->country), $replace); }
function country_money(\App\Support\Money $money): string { $context = country(); $scale = config('localization.currency_scales.' . $context->currencyCode(), 2); $raw = $money->format('', $scale); return ($context->country['currency_symbol_position'] ?? 'before') === 'after' ? $raw . ' ' . $context->currencySymbol() : $context->currencySymbol() . $raw; }
function localized_date(\DateTimeInterface $date, string $format = 'd M Y'): string { return \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone(country()->timezone()))->format($format); }
function localized_number(int|float|string $value, int $decimals = 2): string { $decimalMark = str_starts_with(country()->locale(), 'de') || str_starts_with(country()->locale(), 'fr') || str_starts_with(country()->locale(), 'it') || str_starts_with(country()->locale(), 'es') || str_starts_with(country()->locale(), 'nl') || str_starts_with(country()->locale(), 'pt') ? ',' : '.'; $thousands = $decimalMark === ',' ? '.' : ','; return number_format((float)$value, $decimals, $decimalMark, $thousands); }
function localized_percent(int|float|string $value, int $decimals = 2): string { return localized_number($value, $decimals) . '%'; }
