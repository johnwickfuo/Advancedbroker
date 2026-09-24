<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\UserRepository;
use App\Security\PasswordHasher;
use App\Support\Request;

final class AuthService {
    public function __construct(private UserRepository $users,private SessionService $sessions,private SecurityEventService $events,private NotificationService $notifications) {}
    public function attempt(string $email,string $password,bool $remember,Request $request): array {
        $user=$this->users->byEmail($email);
        if(!$user||!PasswordHasher::verify($password,(string)$user['password_hash'])){ $this->events->record($user?(int)$user['id']:null,'login_failed',$request); return ['status'=>'invalid']; }
        if(!app('account_access')->canLogin($user)){ $this->events->record((int)$user['id'],'login_blocked',$request); return ['status'=>'blocked']; }
        if(PasswordHasher::needsRehash((string)$user['password_hash'])) $this->users->update((int)$user['id'],['password_hash'=>PasswordHasher::hash($password)]);
        if(!empty($user['two_factor_enabled_at'])&&!empty($user['two_factor_secret_encrypted'])){ $_SESSION['two_factor_challenge']=['user_id'=>(int)$user['id'],'remember'=>$remember,'created_at'=>time()]; return ['status'=>'two_factor']; }
        $this->complete($user,$request,$remember); return ['status'=>'ok','user'=>$user];
    }
    public function completeTwoFactor(string $code,Request $request): bool {
        $challenge=$_SESSION['two_factor_challenge']??null;
        if(!is_array($challenge)||time()-(int)($challenge['created_at']??0)>600){ unset($_SESSION['two_factor_challenge']); return false; }
        $user=$this->users->find((int)($challenge['user_id']??0));
        if(!$user||!app('account_access')->canLogin($user))return false;
        $ok=app('two_factor')->verifyUser($user,$code)||app('two_factor')->consumeRecoveryCode($user,$code);
        if(!$ok){$this->events->record((int)$user['id'],'two_factor_failed',$request);return false;}
        $remember=(bool)($challenge['remember']??false);unset($_SESSION['two_factor_challenge']);$this->complete($user,$request,$remember);return true;
    }
    public function complete(array $user,Request $request,bool $remember=false): void {
        $this->sessions->establish($user,$request,$remember);
        $this->users->update((int)$user['id'],['last_login_at'=>date('Y-m-d H:i:s')]);
        $this->events->record((int)$user['id'],'login_success',$request);
    }
    public function register(array $data,Request $request): array {
        $email=strtolower(trim((string)$data['email']));
        if($this->users->byEmail($email))throw new \DomainException('An account already exists for this email address.');
        $context=country();$language=$context->languageCode;$languageId=$this->users->languageId($language);
        $user=$this->users->create([
            'uuid'=>$this->uuid(),'first_name'=>trim((string)$data['first_name']),'last_name'=>trim((string)$data['last_name']),'phone'=>trim((string)($data['phone']??''))?:null,
            'email'=>$email,'password_hash'=>PasswordHasher::hash((string)$data['password']),'country_id'=>$context->id(),'assigned_country_id'=>null,
            'country_assignment_source'=>strtoupper(substr($context->resolutionSource,0,30)),'original_country_id'=>$context->id(),'preferred_language_id'=>$languageId,
            'detected_country_code'=>$context->geo->countryCode,'account_status'=>'active','role'=>'user',
            'terms_accepted_at'=>date('Y-m-d H:i:s'),'terms_version'=>'registration','privacy_accepted_at'=>date('Y-m-d H:i:s'),'privacy_version'=>'registration',
            'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
        ]);
        $this->events->record((int)$user['id'],'account_registered',$request,['country_id'=>$context->id(),'resolution_source'=>$context->resolutionSource]);
        $this->notifications->create((int)$user['id'],'registration','Welcome to '.(string)config('app.name'),'Your account has been created.');
        return $user;
    }
    private function uuid():string{$h=bin2hex(random_bytes(16));return sprintf('%s-%s-%s-%s-%s',substr($h,0,8),substr($h,8,4),substr($h,12,4),substr($h,16,4),substr($h,20,12));}
}
