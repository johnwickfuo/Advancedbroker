<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$db=\App\Application::database(BASE_PATH);
$expected=['beginner','growth','advanced','professional','elite'];
$failed=false;

foreach($expected as $slug){
    $category=$db->one('SELECT * FROM ai_trading_categories WHERE slug=? LIMIT 1',[$slug]);
    if(!$category){
        fwrite(STDERR,$slug.": missing\n");
        $failed=true;
        continue;
    }
    $available=(int)$db->scalar('SELECT COUNT(*) FROM ai_trading_codes WHERE category_id=? AND status="AVAILABLE"',[$category['id']]);
    printf("%s: present, %d available codes\n",$slug,$available);
    if($available<50) $failed=true;
}

if($failed){
    fwrite(STDERR,"AI trading seed verification failed.\n");
    exit(1);
}
echo "AI trading seed verification passed.\n";
