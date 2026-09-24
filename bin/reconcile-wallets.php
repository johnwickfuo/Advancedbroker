<?php
declare(strict_types=1);
/** Read-only financial integrity report. It never repairs or changes data. */
require __DIR__ . '/bootstrap.php';

try {
    $db = \App\Application::database(BASE_PATH);
    $critical = 0;
    $report = static function (string $severity, string $check, string $entity, string $expected, string $actual) use (&$critical): void {
        if ($severity === 'CRITICAL') $critical++;
        echo sprintf("[%s] %s | %s | expected=%s | actual=%s\n", $severity, $check, $entity, $expected, $actual);
    };
    $wallets = $db->select('SELECT id,user_id,available_balance_minor,reserved_balance_minor FROM wallets ORDER BY id');
    foreach ($wallets as $wallet) {
        $net = (int) $db->scalar('SELECT COALESCE(SUM(CASE WHEN direction="CREDIT" THEN amount_minor ELSE -amount_minor END),0) FROM ledger_transactions WHERE wallet_id=? AND status="COMPLETED"', [$wallet['id']]);
        $reserved = (int) $db->scalar('SELECT COALESCE(SUM(amount_minor),0) FROM wallet_reservations WHERE wallet_id=? AND status="ACTIVE"', [$wallet['id']]);
        $expectedAvailable = $net - $reserved;
        if ((int)$wallet['available_balance_minor'] !== $expectedAvailable) $report('CRITICAL','wallet available balance','wallet:'.$wallet['id'],(string)$expectedAvailable,(string)$wallet['available_balance_minor']);
        if ((int)$wallet['reserved_balance_minor'] !== $reserved) $report('CRITICAL','wallet reserved balance','wallet:'.$wallet['id'],(string)$reserved,(string)$wallet['reserved_balance_minor']);
    }
    $checks = [
        ['approved deposit ledger', 'SELECT d.reference FROM deposit_requests d LEFT JOIN ledger_transactions l ON l.id=d.approved_ledger_transaction_id AND l.transaction_type="DEPOSIT" WHERE d.status="APPROVED" AND l.id IS NULL'],
        ['duplicate deposit credit', 'SELECT related_entity_id FROM ledger_transactions WHERE related_entity_type="deposit" AND transaction_type="DEPOSIT" GROUP BY related_entity_id HAVING COUNT(*)>1'],
        ['matured principal', 'SELECT public_id FROM investment_positions WHERE status="MATURED" AND maturity_principal_ledger_transaction_id IS NULL'],
        ['matured profit', 'SELECT public_id FROM investment_positions WHERE status="MATURED" AND maturity_profit_ledger_transaction_id IS NULL'],
        ['duplicate maturity principal', 'SELECT related_entity_id FROM ledger_transactions WHERE related_entity_type="investment-principal" AND transaction_type="INVESTMENT_PRINCIPAL_RETURN" GROUP BY related_entity_id HAVING COUNT(*)>1'],
        ['duplicate maturity profit', 'SELECT related_entity_id FROM ledger_transactions WHERE related_entity_type="investment-profit" AND transaction_type="INVESTMENT_PROFIT" GROUP BY related_entity_id HAVING COUNT(*)>1'],
        ['sold sale credit', 'SELECT public_id FROM sale_requests WHERE status="APPROVED" AND sale_ledger_transaction_id IS NULL'],
        ['duplicate sale credit', 'SELECT related_entity_id FROM ledger_transactions WHERE related_entity_type="sale" AND transaction_type="SHARE_SALE" GROUP BY related_entity_id HAVING COUNT(*)>1'],
        ['paid withdrawal debit', 'SELECT reference FROM withdrawal_requests WHERE status="PAID" AND completed_ledger_transaction_id IS NULL'],
        ['duplicate withdrawal debit', 'SELECT related_entity_id FROM ledger_transactions WHERE related_entity_type="withdrawal" AND transaction_type="WITHDRAWAL" GROUP BY related_entity_id HAVING COUNT(*)>1'],
        ['orphan active withdrawal reservation', 'SELECT w.reference FROM withdrawal_requests w LEFT JOIN wallet_reservations r ON r.id=w.reservation_id AND r.status="ACTIVE" WHERE w.status IN ("PENDING","UNDER_REVIEW","APPROVED","PROCESSING") AND r.id IS NULL'],
        ['released withdrawal reservation', 'SELECT w.reference FROM withdrawal_requests w JOIN wallet_reservations r ON r.id=w.reservation_id WHERE w.status IN ("REJECTED","CANCELLED","PAID") AND r.status="ACTIVE"'],
    ];
    foreach ($checks as [$name, $sql]) foreach ($db->select($sql) as $row) $report('CRITICAL',$name,implode(':',array_map('strval',$row)),'consistent','inconsistent');
    echo $critical ? "Reconciliation found {$critical} critical inconsistency(s).\n" : "Reconciliation passed with no critical inconsistencies.\n";
    exit($critical ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Reconciliation could not connect to the configured database or schema.\n");
    exit(2);
}
