<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Support\{Container,Request,Response};

final class CountryResolutionMiddleware {
    public function handle(Request $request,callable $next):Response{
        if(empty($_SESSION['user_id']))app('sessions')->restore($request);

        $account=!empty($_SESSION['user_id'])?app('users')->find((int)$_SESSION['user_id']):null;
        $browserCountryId=null;

        if(!$account){
            // Only a server-signed cookie created after account association may
            // lock an otherwise anonymous browser. No generic guest country is
            // ever persisted in session or cookie.
            $browserCountryId=app('market_lock')->countryId();
        }

        $context=app('country_resolver')->resolve($request,$account,$browserCountryId);
        $context=app('languages')->resolve($context,$request,$account);

        // Existing signed-in users receive/refresh the browser lock too, so a
        // deployment of this feature does not require them to sign out/in.
        if($account && ($account['role']??'user')!=='super_admin'){
            app('market_lock')->lock($context->id());
        }

        Container::instance()->set('country_context',$context);
        return $next($request);
    }
}
