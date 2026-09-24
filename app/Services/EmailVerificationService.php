<?php
declare(strict_types=1);
namespace App\Services;
use App\Mail\MailService; use App\Repositories\UserRepository; use App\Support\Database;
final class EmailVerificationService {
    public function __construct(private ?Database $db,private UserRepository $users,private MailService $mail,private SecurityEventService $events) {}
    public function send(array $user): void { if(!$this->db||!empty($user['email_verified_at']))return; $this->db->execute('UPDATE email_verification_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$user['id']]); $token=TokenService::raw(); $this->db->execute('INSERT INTO email_verification_tokens (user_id,token_hash,expires_at,created_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR),NOW())',[$user['id'],TokenService::hash($token)]); $this->mail->send($user['email'],'Verify your email address','action-link',['greeting'=>$user['first_name'],'message'=>'Confirm your email address to protect your account.','url'=>url('/verify-email?token='.rawurlencode($token)),'action'=>'Verify email']); }
    public function verify(string $token): ?int { if(!$this->db)return null; $row=$this->db->one('SELECT * FROM email_verification_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1',[TokenService::hash($token)]); if(!$row)return null; $this->db->transaction(function()use($row){$this->db->execute('UPDATE email_verification_tokens SET used_at=NOW() WHERE id=?',[$row['id']]);$this->users->update((int)$row['user_id'],['email_verified_at'=>date('Y-m-d H:i:s')]);}); $this->events->record((int)$row['user_id'],'email_verified'); return (int)$row['user_id']; }
}
