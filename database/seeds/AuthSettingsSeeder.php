<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Security\PasswordHasher;
use App\Support\Database;

final class AuthSettingsSeeder {
    public function run(Database $db): void {
        $db->execute("INSERT INTO settings (setting_key,setting_value,is_public,created_at,updated_at) VALUES ('require_email_verification', JSON_OBJECT('enabled', false), 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE setting_key=setting_key");
        $email = strtolower(trim((string) env('SUPER_ADMIN_EMAIL', '')));
        $password = (string) env('SUPER_ADMIN_PASSWORD', '');
        if ($email === '' || $password === '') return;
        if ($db->scalar("SELECT COUNT(*) FROM users WHERE role='super_admin'") > 0) return;
        $globalId = (int) $db->scalar("SELECT id FROM countries WHERE is_global=1 LIMIT 1");
        $languageId = (int) $db->scalar("SELECT id FROM languages WHERE code='en' LIMIT 1");
        $db->execute('INSERT INTO users (uuid,first_name,last_name,email,password_hash,country_id,assigned_country_id,original_country_id,preferred_language_id,country_assignment_source,account_status,role,email_verified_at,terms_accepted_at,terms_version,privacy_accepted_at,privacy_version,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,\'active\',\'super_admin\',NOW(),NOW(),\'seed\',NOW(),\'seed\',NOW(),NOW())', [self::uuid(), (string) env('SUPER_ADMIN_FIRST_NAME', 'Platform'), (string) env('SUPER_ADMIN_LAST_NAME', 'Administrator'), $email, PasswordHasher::hash($password), $globalId, $globalId, $globalId, $languageId, 'MIGRATION']);
    }
    private static function uuid(): string { $hex = bin2hex(random_bytes(16)); return sprintf('%s-%s-4%s-%s%s-%s', substr($hex,0,8),substr($hex,8,4),substr($hex,13,3),dechex((hexdec($hex[16])&0x3)|0x8),substr($hex,17,3),substr($hex,20)); }
}
