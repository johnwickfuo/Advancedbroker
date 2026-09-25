<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!filter_var(env('QA_USER_ENABLED', false), FILTER_VALIDATE_BOOL)) {
    echo "QA user verification skipped (QA_USER_ENABLED=false).\n";
    exit(0);
}

$db = \App\Application::database(BASE_PATH);
$email = strtolower(trim((string)env('QA_USER_EMAIL','user@user.com')));
$username = trim((string)env('QA_USER_USERNAME','user'));

$user = $db->one('SELECT * FROM users WHERE LOWER(email)=? AND username=? LIMIT 1',[$email,$username]);
if(!$user) {
    fwrite(STDERR,"QA user was not created.\n");
    exit(1);
}

$kyc=(int)$db->scalar('SELECT COUNT(*) FROM kyc_submissions WHERE user_id=? AND status="APPROVED"',[$user['id']]);
$wallet=$db->one('SELECT * FROM wallets WHERE user_id=?',[$user['id']]);

$checks = [
    'active' => strtolower((string)$user['account_status']) === 'active',
    'normal_user_role' => (string)$user['role'] === 'user',
    'email_verified' => !empty($user['email_verified_at']),
    'kyc_approved' => $kyc > 0,
    'wallet_available' => $wallet && (int)$wallet['available_balance_minor'] > 0,
];

foreach($checks as $label=>$ok) printf("%s: %s\n",$label,$ok?'OK':'FAILED');

if(in_array(false,$checks,true)) exit(1);
echo "QA user verification passed.\n";
