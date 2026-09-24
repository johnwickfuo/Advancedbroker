<?php
declare(strict_types=1);
namespace App\Services;

final class ThemeService {
    private ?string $nonce = null;
    private const COLORS = ['primary','secondary','accent','dark','text','muted','background','surface','border','success','warning','error'];
    private const FONTS = ['system','Inter','Arial','Georgia','Noto Sans','Noto Sans Arabic','Noto Sans JP','Noto Sans KR'];
    public function nonce(): string { return $this->nonce ??= base64_encode(random_bytes(18)); }
    public function css(array $theme): string { $theme = $this->sanitize($theme); $vars = []; foreach (self::COLORS as $color) $vars[] = '--color-' . $color . ':' . $theme[$color]; $vars[] = '--country-radius:' . $theme['radius']; $vars[] = '--heading-font:' . $theme['heading_font']; $vars[] = '--body-font:' . $theme['body_font']; return ':root{' . implode(';', $vars) . '}'; }
    public function sanitize(array $theme): array { $defaults = ['primary'=>'#1258D5','secondary'=>'#0B348A','accent'=>'#D7F7E5','dark'=>'#101827','text'=>'#101827','muted'=>'#64748B','background'=>'#F5F7FA','surface'=>'#FFFFFF','border'=>'#DDE3EC','success'=>'#147D4E','warning'=>'#A56500','error'=>'#B42336','radius'=>'18px','heading_font'=>'Inter, sans-serif','body_font'=>'Inter, sans-serif']; foreach (self::COLORS as $key) if (isset($theme[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$theme[$key])) $defaults[$key] = strtoupper((string)$theme[$key]); $defaults['radius'] = in_array($theme['radius'] ?? '', ['8px','12px','16px','18px','24px'], true) ? $theme['radius'] : $defaults['radius']; foreach (['heading_font','body_font'] as $font) if (in_array($theme[$font] ?? '', self::FONTS, true)) $defaults[$font] = $theme[$font] . ', sans-serif'; return $defaults; }
}
