<?php
declare(strict_types=1);
namespace App\Security;

final class Csrf {
    public function token(): string {
        if (empty($_SESSION['_csrf_token'])) $_SESSION['_csrf_token']=bin2hex(random_bytes(32));
        return (string)$_SESSION['_csrf_token'];
    }
    public function input(): string { return '<input type="hidden" name="_token" value="'.htmlspecialchars($this->token(),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'">'; }
    public function valid(?string $token): bool { return is_string($token) && $token!=='' && hash_equals($this->token(),$token); }
    public function regenerate(): void { $_SESSION['_csrf_token']=bin2hex(random_bytes(32)); }
}
