<?php
declare(strict_types=1);
namespace App\Security;

final class Session {
    public static function start(array $config=[]): void {
        if (session_status()===PHP_SESSION_ACTIVE) return;
        $secure=(bool)($config['secure'] ?? false);
        session_name((string)($config['name'] ?? 'app_session'));
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        ini_set('session.cookie_httponly','1');
        session_set_cookie_params([
            'lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax'
        ]);
        session_start();
    }
}
