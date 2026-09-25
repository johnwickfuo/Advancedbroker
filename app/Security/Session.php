<?php
declare(strict_types=1);
namespace App\Security;

final class Session {
    public static function start(array $config=[]): void {
        if(session_status()===PHP_SESSION_ACTIVE)return;
        session_name((string)($config['name']??'app_session'));
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        ini_set('session.cookie_httponly','1');
        session_set_cookie_params([
            'lifetime'=>0,
            'path'=>'/',
            'secure'=>self::cookieShouldBeSecure($config),
            'httponly'=>true,
            'samesite'=>'Lax',
        ]);
        session_start();
    }

    public static function cookieShouldBeSecure(array $config=[]): bool {
        return (bool)($config['secure']??false) && self::requestIsHttps($config);
    }

    public static function requestIsHttps(array $config=[]): bool {
        $https=strtolower((string)($_SERVER['HTTPS']??''));
        if($https!==''&&$https!=='off'&&$https!=='0')return true;
        if((int)($_SERVER['SERVER_PORT']??0)===443)return true;

        $proto=strtolower(trim(explode(',',(string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))[0]??''));
        if($proto!=='https')return false;

        $remote=(string)($_SERVER['REMOTE_ADDR']??'');
        if(in_array($remote,['127.0.0.1','::1'],true))return true;

        foreach((array)($config['trusted_proxies']??[]) as $proxy){
            if(trim((string)$proxy)!==''&&$remote===trim((string)$proxy))return true;
        }
        return false;
    }
}
