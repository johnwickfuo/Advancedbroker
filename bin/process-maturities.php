<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
try{
    \App\Application::boot(BASE_PATH);
    $dry=in_array('--dry-run',$argv,true);
    $investments=app('investments')->matureDue($dry);
    $ai=app('ai_trading')->matureDue($dry);
    echo ($dry?'Would process ':'Processed ').count($investments)." investment maturity position(s).\n";
    echo ($dry?'Would process ':'Processed ').count($ai)." AI trading completion(s).\n";
}catch(Throwable $e){fwrite(STDERR,"Maturity processing failed safely: {$e->getMessage()}\n");exit(1);}
