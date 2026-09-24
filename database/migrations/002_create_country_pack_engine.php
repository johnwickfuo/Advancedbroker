<?php
declare(strict_types=1);
use App\Support\Database;

return static function (Database $db): void {
    $columns = array_column($db->select('SHOW COLUMNS FROM countries'), 'Field');
    if (!in_array('slug', $columns, true)) $db->execute('ALTER TABLE countries MODIFY code CHAR(2) NULL, MODIFY iso3 CHAR(3) NULL, ADD slug VARCHAR(100) NULL AFTER local_name, ADD currency_symbol_position ENUM("before","after") NOT NULL DEFAULT "before" AFTER currency_symbol, ADD flag_asset VARCHAR(255) NULL AFTER timezone, ADD visual_assets JSON NULL AFTER flag_asset, ADD theme JSON NULL AFTER visual_assets, ADD is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active, ADD UNIQUE KEY unique_country_slug(slug)');
    $db->execute("UPDATE countries SET code=NULL, iso3=NULL, slug='global', local_name='Global' WHERE is_global=1");
    $userColumns = array_column($db->select('SHOW COLUMNS FROM users'), 'Field');
    if (!in_array('assigned_country_id', $userColumns, true)) $db->execute('ALTER TABLE users ADD assigned_country_id BIGINT UNSIGNED NULL AFTER country_id, ADD detected_country_code CHAR(2) NULL AFTER preferred_language_id, ADD CONSTRAINT fk_users_assigned_country FOREIGN KEY (assigned_country_id) REFERENCES countries(id) ON DELETE SET NULL, ADD INDEX(assigned_country_id)');
    $db->execute("CREATE TABLE IF NOT EXISTS country_page_contents (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, country_id BIGINT UNSIGNED NOT NULL, language_id BIGINT UNSIGNED NOT NULL, page_key VARCHAR(80) NOT NULL, content JSON NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY unique_country_page_language(country_id,language_id,page_key), CONSTRAINT fk_country_page_country FOREIGN KEY(country_id) REFERENCES countries(id) ON DELETE CASCADE, CONSTRAINT fk_country_page_language FOREIGN KEY(language_id) REFERENCES languages(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->execute("CREATE TABLE IF NOT EXISTS country_assets (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, country_id BIGINT UNSIGNED NOT NULL, asset_type VARCHAR(60) NOT NULL, storage_path VARCHAR(255) NOT NULL, alt_text VARCHAR(255) NULL, attribution TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX(country_id,asset_type), CONSTRAINT fk_country_assets_country FOREIGN KEY(country_id) REFERENCES countries(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
