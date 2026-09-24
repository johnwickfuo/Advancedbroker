<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
try{$r=\App\Application::boot(BASE_PATH);$result=app('investments')->matureDue(in_array('--dry-run',$argv,true));echo (in_array('--dry-run',$argv,true)?'Would process ':'Processed ').count($result)." maturity position(s).\n";}catch(Throwable $e){fwrite(STDERR,"Maturity processing failed safely: {$e->getMessage()}\n");exit(1);}
