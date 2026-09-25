<?php
declare(strict_types=1);
use App\Controllers\{AiTradingController,AuthController,CompanyController,DashboardController,FinancialController,HomeController,InvestmentController,KycController,MediaController,NotificationController,PasswordController,ProfileController,PublicContentController,SecurityController,TwoFactorController,WithdrawalController}; use App\Middleware\{AuthMiddleware,CountryAvailabilityMiddleware,GuestMiddleware,SuspendedAccountMiddleware,UserMiddleware,VerifiedEmailMiddleware}; use App\Support\{Request,Router};
/** @var Router $router */
$router->get('/', [HomeController::class, 'index'], 'home');
$router->post('/language', [\App\Controllers\LanguageController::class, 'update'], 'language.update');
$router->get('/ai-trading',[AiTradingController::class,'catalogue'],'ai-trading.catalogue');
$router->get('/companies',[CompanyController::class,'index'],'companies.index');$router->get('/companies/{company}',[CompanyController::class,'show'],'companies.show');
$router->get('/about',[PublicContentController::class,'page'],'about');$router->get('/how-it-works',[PublicContentController::class,'page'],'how-it-works');$router->get('/faq',[PublicContentController::class,'faq'],'faq');$router->get('/contact',[PublicContentController::class,'contact'],'contact');$router->post('/contact',[PublicContentController::class,'submitContact'],'contact.submit');$router->get('/license',[PublicContentController::class,'license'],'license');
$router->get('/terms',fn(Request $r)=>app(PublicContentController::class)->legal(new Request($r->method,'/terms',[],['type'=>'terms'],[],[])),'terms');
$router->get('/privacy',fn(Request $r)=>app(PublicContentController::class)->legal(new Request($r->method,'/privacy',[],['type'=>'privacy'],[],[])),'privacy');
$router->get('/risk-disclosure',fn(Request $r)=>app(PublicContentController::class)->legal(new Request($r->method,'/risk-disclosure',[],['type'=>'risk-disclosure'],[],[])),'risk-disclosure');
$router->get('/media/{asset}',[MediaController::class,'asset'],'media.asset');$router->get('/license-document/{license}',[MediaController::class,'license'],'license.document');
$router->group(['middleware'=>[GuestMiddleware::class]],function(Router $router):void{
    $router->get('/login',[AuthController::class,'login'],'login');$router->post('/login',[AuthController::class,'authenticate'],'login.attempt');
    $router->get('/register',[AuthController::class,'register'],'register');$router->post('/register',[AuthController::class,'store'],'register.store');
    $router->get('/forgot-password',[PasswordController::class,'forgot'],'forgot-password');$router->post('/forgot-password',[PasswordController::class,'sendReset'],'forgot-password.send');
    $router->get('/reset-password',[PasswordController::class,'reset'],'reset-password');$router->post('/reset-password',[PasswordController::class,'updateReset'],'reset-password.update');
    $router->get('/two-factor-challenge',[TwoFactorController::class,'challenge'],'two-factor.challenge');$router->post('/two-factor-challenge',[TwoFactorController::class,'verifyChallenge'],'two-factor.verify');
});
$router->get('/verify-email',[AuthController::class,'verify'],'verify-email');$router->post('/verify-email',[AuthController::class,'confirmVerification'],'verify-email.confirm');
$router->group(['middleware'=>[AuthMiddleware::class,VerifiedEmailMiddleware::class,SuspendedAccountMiddleware::class,CountryAvailabilityMiddleware::class]],function(Router $router):void{
    $router->post('/logout',[AuthController::class,'logout'],'logout');
    $router->group(['middleware'=>[UserMiddleware::class]],function(Router $router):void{
        $router->post('/ai-trading/{category}/buy',[AiTradingController::class,'buy'],'ai-trading.buy');
        $router->group(['prefix'=>'/dashboard','as'=>'dashboard.'],function(Router $router):void{
        $router->get('/',[DashboardController::class,'index'],'index');
        $router->get('/ai-trading',[AiTradingController::class,'dashboard'],'ai-trading');$router->get('/ai-trading/{purchase}',[AiTradingController::class,'show'],'ai-trading.show');$router->post('/ai-trading/{purchase}/activate',[AiTradingController::class,'activate'],'ai-trading.activate');
        $router->get('/profile',[ProfileController::class,'edit'],'profile');$router->post('/profile',[ProfileController::class,'update'],'profile.update');
        $router->get('/security',[SecurityController::class,'index'],'security');$router->post('/security/password',[PasswordController::class,'update'],'security.password');$router->post('/security/resend-verification',[AuthController::class,'resendVerification'],'security.resend-verification');
        $router->post('/security/sessions/{session}/revoke',[SecurityController::class,'revoke'],'security.session.revoke');$router->post('/security/sessions/revoke-others',[SecurityController::class,'revokeOthers'],'security.sessions.revoke-others');
        $router->get('/security/two-factor',[TwoFactorController::class,'setup'],'two-factor.setup');$router->post('/security/two-factor',[TwoFactorController::class,'confirm'],'two-factor.confirm');$router->post('/security/two-factor/disable',[TwoFactorController::class,'disable'],'two-factor.disable');$router->post('/security/two-factor/recovery-codes',[TwoFactorController::class,'regenerate'],'two-factor.regenerate');
        $router->get('/deposit',[FinancialController::class,'deposit'],'deposit');$router->post('/deposit/review',[FinancialController::class,'reviewDeposit'],'deposit.review');$router->post('/deposit',[FinancialController::class,'submitDeposit'],'deposit.submit');$router->get('/deposits',[FinancialController::class,'deposits'],'deposits');$router->get('/deposit/{deposit}/files/{file}',[FinancialController::class,'depositFile'],'deposit.file');$router->get('/deposit/{deposit}',[FinancialController::class,'depositDetail'],'deposit.detail');$router->post('/deposit/{deposit}/cancel',[FinancialController::class,'cancelDeposit'],'deposit.cancel');
        $router->get('/transactions',[FinancialController::class,'transactions'],'transactions');$router->get('/transactions/{transaction}',[FinancialController::class,'transaction'],'transactions.show');
        $router->get('/investments',[InvestmentController::class,'index'],'investments');$router->get('/portfolio',[InvestmentController::class,'portfolio'],'portfolio');$router->get('/investments/{investment}',[InvestmentController::class,'show'],'investments.show');$router->post('/investments/{investment}/sale',[InvestmentController::class,'sale'],'investments.sale');
        $router->get('/kyc',[KycController::class,'index'],'kyc');$router->post('/kyc',[KycController::class,'save'],'kyc.save');$router->get('/kyc/documents/{document}',[KycController::class,'document'],'kyc.document');
        $router->get('/withdraw',[WithdrawalController::class,'index'],'withdraw');$router->post('/withdraw',[WithdrawalController::class,'submit'],'withdraw.submit');$router->get('/withdrawals',[WithdrawalController::class,'history'],'withdrawals');$router->get('/withdraw/{withdrawal}',[WithdrawalController::class,'detail'],'withdraw.detail');$router->post('/withdraw/{withdrawal}/cancel',[WithdrawalController::class,'cancel'],'withdraw.cancel');
        $router->get('/notifications',[NotificationController::class,'index'],'notifications');$router->post('/notifications/{notification}/read',[NotificationController::class,'read'],'notifications.read');$router->post('/notifications/read-all',[NotificationController::class,'readAll'],'notifications.read-all');
        });
    });
});
$router->group(['middleware'=>[AuthMiddleware::class,VerifiedEmailMiddleware::class,SuspendedAccountMiddleware::class,CountryAvailabilityMiddleware::class,UserMiddleware::class]],function(Router $router):void{
    $router->get('/companies/{company}/buy',[InvestmentController::class,'buy'],'companies.buy');$router->post('/companies/{company}/buy/review',[InvestmentController::class,'review'],'companies.buy.review');$router->post('/companies/{company}/buy/confirm',[InvestmentController::class,'confirm'],'companies.buy.confirm');
});
