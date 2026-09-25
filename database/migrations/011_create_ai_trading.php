<?php
declare(strict_types=1);

use App\Support\Database;

return static function(Database $db): void {
    $db->execute("ALTER TABLE ledger_transactions MODIFY transaction_type ENUM('DEPOSIT','INVESTMENT_PURCHASE','INVESTMENT_PRINCIPAL_RETURN','INVESTMENT_PROFIT','SHARE_SALE','WITHDRAWAL','WITHDRAWAL_FEE','DEPOSIT_FEE','ADMIN_CREDIT','ADMIN_DEBIT','REVERSAL','ADJUSTMENT','RESERVATION','RESERVATION_RELEASE','AI_CODE_PURCHASE','AI_PRINCIPAL_RETURN','AI_PROFIT') NOT NULL");

    $db->execute("CREATE TABLE IF NOT EXISTS ai_trading_categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(36) NOT NULL,
        name VARCHAR(190) NOT NULL,
        slug VARCHAR(190) NOT NULL,
        description TEXT NULL,
        price_usd_minor BIGINT UNSIGNED NOT NULL,
        profit_percent DECIMAL(10,4) NOT NULL,
        duration_value INT UNSIGNED NOT NULL,
        duration_unit ENUM('HOURS','DAYS','WEEKS','MONTHS') NOT NULL,
        code_batch_size SMALLINT UNSIGNED NOT NULL DEFAULT 50,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by_user_id BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY ai_category_public(public_id),
        UNIQUE KEY ai_category_slug(slug),
        INDEX ai_category_active(is_active,created_at),
        CONSTRAINT fk_ai_category_creator FOREIGN KEY(created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->execute("CREATE TABLE IF NOT EXISTS ai_trading_codes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(36) NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        purchase_id BIGINT UNSIGNED NULL,
        batch_number INT UNSIGNED NOT NULL,
        code_hash CHAR(64) NOT NULL,
        code_encrypted TEXT NOT NULL,
        status ENUM('AVAILABLE','SOLD','ACTIVATED','COMPLETED','VOID') NOT NULL DEFAULT 'AVAILABLE',
        generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sold_at DATETIME NULL,
        activated_at DATETIME NULL,
        completed_at DATETIME NULL,
        UNIQUE KEY ai_code_public(public_id),
        UNIQUE KEY ai_code_hash(code_hash),
        INDEX ai_code_inventory(category_id,status,id),
        INDEX ai_code_purchase(purchase_id),
        CONSTRAINT fk_ai_code_category FOREIGN KEY(category_id) REFERENCES ai_trading_categories(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->execute("CREATE TABLE IF NOT EXISTS ai_trading_purchases (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(36) NOT NULL,
        reference VARCHAR(80) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        wallet_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        code_id BIGINT UNSIGNED NOT NULL,
        category_name_snapshot VARCHAR(190) NOT NULL,
        price_usd_minor_snapshot BIGINT UNSIGNED NOT NULL,
        profit_percent_snapshot DECIMAL(10,4) NOT NULL,
        duration_value_snapshot INT UNSIGNED NOT NULL,
        duration_unit_snapshot ENUM('HOURS','DAYS','WEEKS','MONTHS') NOT NULL,
        fx_rate_snapshot DECIMAL(24,10) NOT NULL,
        fx_snapshot_date DATE NOT NULL,
        currency_code CHAR(3) NOT NULL,
        purchase_amount_minor BIGINT UNSIGNED NOT NULL,
        profit_amount_minor BIGINT UNSIGNED NOT NULL,
        maturity_payout_minor BIGINT UNSIGNED NOT NULL,
        status ENUM('PURCHASED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PURCHASED',
        purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        activated_at DATETIME NULL,
        matures_at DATETIME NULL,
        completed_at DATETIME NULL,
        purchase_ledger_transaction_id BIGINT UNSIGNED NULL,
        principal_return_ledger_transaction_id BIGINT UNSIGNED NULL,
        profit_ledger_transaction_id BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY ai_purchase_public(public_id),
        UNIQUE KEY ai_purchase_reference(reference),
        UNIQUE KEY ai_purchase_code(code_id),
        INDEX ai_purchase_user(user_id,status,purchased_at),
        INDEX ai_purchase_due(status,matures_at),
        CONSTRAINT fk_ai_purchase_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_wallet FOREIGN KEY(wallet_id) REFERENCES wallets(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_category FOREIGN KEY(category_id) REFERENCES ai_trading_categories(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_code FOREIGN KEY(code_id) REFERENCES ai_trading_codes(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_debit FOREIGN KEY(purchase_ledger_transaction_id) REFERENCES ledger_transactions(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_principal FOREIGN KEY(principal_return_ledger_transaction_id) REFERENCES ledger_transactions(id) ON DELETE RESTRICT,
        CONSTRAINT fk_ai_purchase_profit FOREIGN KEY(profit_ledger_transaction_id) REFERENCES ledger_transactions(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $hasPurchaseFk=(bool)$db->scalar('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME="ai_trading_codes" AND CONSTRAINT_NAME="fk_ai_code_purchase"');
    if(!$hasPurchaseFk){
        $db->execute("ALTER TABLE ai_trading_codes ADD CONSTRAINT fk_ai_code_purchase FOREIGN KEY(purchase_id) REFERENCES ai_trading_purchases(id) ON DELETE RESTRICT");
    }
};
