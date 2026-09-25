<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Security\PasswordHasher;
use App\Services\WalletService;
use App\Support\{Database,Money};

final class QaUserSeeder
{
    public function run(Database $db): void
    {
        if (!filter_var(env('QA_USER_ENABLED', false), FILTER_VALIDATE_BOOL)) return;

        $email = strtolower(trim((string)env('QA_USER_EMAIL', 'user@user.com')));
        $username = trim((string)env('QA_USER_USERNAME', 'user'));
        $password = (string)env('QA_USER_PASSWORD', '');
        $balance = (string)env('QA_USER_BALANCE', '100000.00');

        if ($email === '' || $username === '' || $password === '') {
            throw new \RuntimeException('QA user seeding is enabled but QA_USER_EMAIL, QA_USER_USERNAME or QA_USER_PASSWORD is missing.');
        }

        $global = $db->one('SELECT * FROM countries WHERE is_global=1 LIMIT 1');
        if (!$global) throw new \RuntimeException('Global country is required before QA user seeding.');

        $languageId = (int)$db->scalar("SELECT id FROM languages WHERE code='en' LIMIT 1");
        $user = $db->one('SELECT * FROM users WHERE LOWER(email)=? OR username=? LIMIT 1', [$email,$username]);

        if (!$user) {
            $db->execute(
                'INSERT INTO users(uuid,username,first_name,last_name,email,password_hash,country_id,assigned_country_id,country_assignment_source,original_country_id,preferred_language_id,detected_country_code,account_status,role,email_verified_at,terms_accepted_at,terms_version,privacy_accepted_at,privacy_version,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'active','user',NOW(),NOW(),'qa-seed',NOW(),'qa-seed',NOW(),NOW())',
                [
                    $this->uuid(),$username,'User','User',$email,PasswordHasher::hash($password),
                    $global['id'],null,'QA_SEED',$global['id'],$languageId,null
                ]
            );
            $user = $db->one('SELECT * FROM users WHERE id=?', [(int)$db->pdo()->lastInsertId()]);
        } else {
            $db->execute(
                'UPDATE users SET username=?,first_name="User",last_name="User",password_hash=?,country_id=?,assigned_country_id=NULL,original_country_id=?,preferred_language_id=?,country_assignment_source="QA_SEED",account_status="active",role="user",email_verified_at=COALESCE(email_verified_at,NOW()),account_restriction_reason=NULL,updated_at=NOW() WHERE id=?',
                [$username,PasswordHasher::hash($password),$global['id'],$global['id'],$languageId,$user['id']]
            );
            $user = $db->one('SELECT * FROM users WHERE id=?', [$user['id']]);
        }

        if (!$user) throw new \RuntimeException('QA user could not be created.');

        $this->ensureKyc($db,$user,$global);
        $this->ensureWallet($db,$user,$global,$balance);
        $this->ensureNotification($db,(int)$user['id']);
    }

    private function ensureKyc(Database $db, array $user, array $country): void
    {
        $config = $db->one(
            'SELECT * FROM kyc_configurations WHERE country_id=? AND is_active=1 ORDER BY version DESC LIMIT 1',
            [$country['id']]
        );

        if (!$config) {
            $db->execute(
                'INSERT INTO kyc_configurations(country_id,form_id,is_required,instructions,version,is_active,created_at,updated_at) VALUES (?,NULL,0,"QA verification record",1,1,NOW(),NOW())',
                [$country['id']]
            );
            $config = $db->one('SELECT * FROM kyc_configurations WHERE id=?', [(int)$db->pdo()->lastInsertId()]);
        }

        if (!$config) throw new \RuntimeException('QA KYC configuration could not be created.');

        $submission = $db->one(
            'SELECT * FROM kyc_submissions WHERE user_id=? AND country_id=? AND status="APPROVED" LIMIT 1',
            [$user['id'],$country['id']]
        );

        if (!$submission) {
            $db->execute(
                'INSERT INTO kyc_submissions(public_id,user_id,country_id,configuration_id,configuration_version,form_snapshot_id,status,submitted_at,reviewed_at,reviewed_by_user_id,approved_at,created_at,updated_at) VALUES (?,?,?,?,?,NULL,"APPROVED",NOW(),NOW(),NULL,NOW(),NOW(),NOW())',
                [$this->uuid(),$user['id'],$country['id'],$config['id'],$config['version']]
            );
            $submissionId=(int)$db->pdo()->lastInsertId();
            $db->execute(
                'INSERT INTO kyc_events(kyc_submission_id,actor_user_id,event_type,user_message,private_note,created_at) VALUES (?,NULL,"APPROVED","QA account pre-verified for testing.","Seeded QA verification",NOW())',
                [$submissionId]
            );
        }
    }

    private function ensureWallet(Database $db, array $user, array $country, string $balance): void
    {
        $money = Money::parse($balance, (string)$country['currency_code'], 2);
        if ($money->minor <= 0) return;

        $wallets = new WalletService($db);
        $wallet = $wallets->walletFor($user,$country);
        if (!$wallet) throw new \RuntimeException('QA wallet could not be created.');

        $wallets->move(
            (int)$wallet['id'],
            'ADMIN_CREDIT',
            'CREDIT',
            $money->minor,
            'QA demo balance',
            'QA_USER_SEED',
            (string)$user['id'],
            null,
            'Seeded demo funds for QA account'
        );
    }

    private function ensureNotification(Database $db, int $userId): void
    {
        $exists = (int)$db->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id=? AND notification_type="qa_account_ready"',
            [$userId]
        );
        if ($exists === 0) {
            $db->execute(
                'INSERT INTO notifications(user_id,channel,notification_type,title,body,data,created_at) VALUES (?,"in_app","qa_account_ready","QA account ready","This account is configured for full normal-user testing.",JSON_OBJECT(),NOW())',
                [$userId]
            );
        }
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
    }
}
