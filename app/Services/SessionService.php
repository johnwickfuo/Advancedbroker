<?php
declare(strict_types=1);
namespace App\Services;
use App\Repositories\UserRepository;
use App\Support\{Database,Request};
use App\Security\Session;

final class SessionService {
    private const REMEMBER_COOKIE='upgradedbroker_remember';

    public function __construct(private ?Database $db,private UserRepository $users,private SecurityEventService $events) {}

    public function establish(array $user,Request $request,bool $remember=false): void {
        session_regenerate_id(true);
        $hash=TokenService::hash(session_id());
        $this->db?->execute(
            'INSERT INTO user_sessions (user_id,session_hash,device_label,ip_address,user_agent,expires_at) VALUES (?,?,?,?,?,DATE_ADD(NOW(),INTERVAL ? SECOND))',
            [$user['id'],$hash,$this->device($request),$request->ip(),$request->userAgent(),(int)config('app.session.lifetime',7200)]
        );
        $_SESSION['user_id']=(int)$user['id'];
        $_SESSION['user_role']=(string)($user['role']??'user');
        $_SESSION['auth_session_hash']=$hash;
        $_SESSION['assigned_country_id']=$user['assigned_country_id']??$user['country_id']??null;
        $_SESSION['country_id']=$user['country_id']??null;
        $_SESSION['preferred_language_code']=$user['preferred_language_code']??null;
        if($remember)$this->remember($user,$request);
    }

    public function validCurrent(): bool {
        if(empty($_SESSION['user_id'])||empty($_SESSION['auth_session_hash']))return false;
        if(!$this->db)return true;

        $row=$this->db->one(
            'SELECT id FROM user_sessions WHERE user_id=? AND session_hash=? AND revoked_at IS NULL AND expires_at>NOW() LIMIT 1',
            [(int)$_SESSION['user_id'],(string)$_SESSION['auth_session_hash']]
        );
        if(!$row)return false;

        // Refresh after validation. Do not use affected-row count as the
        // validity test: MySQL may report 0 when two requests land within the
        // same second and the timestamp values are unchanged.
        $this->db->execute(
            'UPDATE user_sessions SET last_active_at=NOW(),expires_at=DATE_ADD(NOW(),INTERVAL ? SECOND) WHERE id=?',
            [(int)config('app.session.lifetime',7200),(int)$row['id']]
        );
        return true;
    }

    public function restore(Request $request): bool {
        if(!empty($_SESSION['user_id'])){
            if($this->validCurrent())return true;
            foreach(['user_id','user_role','auth_session_hash','assigned_country_id','country_id','preferred_language_code'] as $key)unset($_SESSION[$key]);
        }

        $raw=$_COOKIE[self::REMEMBER_COOKIE]??'';
        [$selector,$token]=array_pad(explode('.',$raw,2),2,'');
        if($selector===''||$token===''||!$this->db)return false;

        $row=$this->db->one('SELECT * FROM remember_tokens WHERE selector=? AND expires_at>NOW() AND revoked_at IS NULL',[$selector]);
        if(!$row||!hash_equals((string)$row['token_hash'],TokenService::hash($token))){
            $this->forgetRememberCookie();
            return false;
        }

        $user=$this->users->find((int)$row['user_id']);
        if(!$user||!app('account_access')->canLogin($user))return false;

        $this->db->execute('UPDATE remember_tokens SET revoked_at=NOW(),last_used_at=NOW() WHERE id=?',[$row['id']]);
        $this->establish($user,$request,true);
        return true;
    }

    public function logout(Request $request): void {
        if(!empty($_SESSION['user_id'])&&!empty($_SESSION['auth_session_hash'])){
            $this->db?->execute('UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND session_hash=?',[(int)$_SESSION['user_id'],$_SESSION['auth_session_hash']]);
        }
        $this->revokeCurrentRemember();
        $safe=[];
        foreach(['language_code','language_country_id','preview_country'] as $key)if(isset($_SESSION[$key]))$safe[$key]=$_SESSION[$key];
        $_SESSION=[];
        if(session_id()!=='')session_destroy();
        Session::start(config('app.session'));
        $_SESSION=$safe;
        session_regenerate_id(true);
    }

    public function sessions(int $userId): array { return $this->db?->select('SELECT * FROM user_sessions WHERE user_id=? AND revoked_at IS NULL AND expires_at>NOW() ORDER BY last_active_at DESC',[$userId])??[]; }
    public function revoke(int $userId,int $sessionId): bool { $changed=$this->db?->execute('UPDATE user_sessions SET revoked_at=NOW() WHERE id=? AND user_id=? AND revoked_at IS NULL',[$sessionId,$userId])??0;return $changed>0; }
    public function revokeOthers(int $userId,string $currentHash): void { $this->db?->execute('UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND session_hash<>? AND revoked_at IS NULL',[$userId,$currentHash]);$this->db?->execute('UPDATE remember_tokens SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL',[$userId]); }
    public function revokeAll(int $userId): void { $this->db?->execute('UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL',[$userId]);$this->db?->execute('UPDATE remember_tokens SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL',[$userId]); }

    private function remember(array $user,Request $request): void {
        if(!$this->db)return;
        $selector=bin2hex(random_bytes(12));$token=TokenService::raw();
        $this->db->execute('INSERT INTO remember_tokens (user_id,selector,token_hash,device_label,ip_address,expires_at,created_at) VALUES (?,?,?,?,?,DATE_ADD(NOW(),INTERVAL 30 DAY),NOW())',[$user['id'],$selector,TokenService::hash($token),$this->device($request),$request->ip()]);
        setcookie(self::REMEMBER_COOKIE,$selector.'.'.$token,[
            'expires'=>time()+2592000,'path'=>'/','secure'=>Session::cookieShouldBeSecure(config('app.session')),'httponly'=>true,'samesite'=>'Lax'
        ]);
    }

    private function revokeCurrentRemember(): void {
        $raw=$_COOKIE[self::REMEMBER_COOKIE]??'';$selector=strtok($raw,'.');
        if($selector&&$this->db)$this->db->execute('UPDATE remember_tokens SET revoked_at=NOW() WHERE selector=?',[$selector]);
        $this->forgetRememberCookie();
    }

    private function forgetRememberCookie(): void {
        setcookie(self::REMEMBER_COOKIE,'',[
            'expires'=>time()-3600,'path'=>'/','secure'=>Session::cookieShouldBeSecure(config('app.session')),'httponly'=>true,'samesite'=>'Lax'
        ]);
    }

    private function device(Request $request): string { $ua=$request->userAgent();return substr($ua!==''?$ua:'Unknown browser',0,190); }
}
