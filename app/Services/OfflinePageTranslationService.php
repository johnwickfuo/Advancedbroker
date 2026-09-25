<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\{Cache,Logger};

final class OfflinePageTranslationService
{
    private const SKIP_TAGS=['script','style','code','pre','svg','textarea'];
    private const VOID_TAGS=['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'];

    public function __construct(private readonly array $config,private readonly Cache $cache,private readonly Logger $logger){}

    public function translateHtml(string $html,string $targetLanguage): string
    {
        $target=$this->targetCode($targetLanguage);
        if($target==='en'||!$this->enabled()) return $html;

        $parts=preg_split('/(<[^>]+>)/s',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
        if(!is_array($parts)) return $html;

        $stack=[];$segments=[];
        foreach($parts as $index=>$part){
            if($part==='') continue;
            if($part[0]==='<'){ $this->updateStack($part,$stack); continue; }
            if($this->skipCurrent($stack)) continue;
            $plain=html_entity_decode($part,ENT_QUOTES|ENT_HTML5,'UTF-8');
            if(!preg_match('/\p{L}/u',$plain)) continue;
            if(!preg_match('/^(\s*)(.*?)(\s*)$/us',$part,$m)) continue;
            if($m[2]===''||!preg_match('/\p{L}/u',html_entity_decode($m[2],ENT_QUOTES|ENT_HTML5,'UTF-8'))) continue;
            $segments[]=['index'=>$index,'leading'=>$m[1],'text'=>$m[2],'trailing'=>$m[3]];
        }
        if(!$segments) return $html;

        $translated=$this->translateSegments(array_column($segments,'text'),$target);
        foreach($segments as $i=>$segment){
            $parts[$segment['index']]=$segment['leading'].($translated[$i]??$segment['text']).$segment['trailing'];
        }
        $result=implode('',$parts);
        $result=preg_replace('/<html\b([^>]*?)\blang=(["\'])[^"\']*\2([^>]*)>/i','<html$1lang="'.$target.'"$3>',$result,1)??$result;
        return $result;
    }

    private function translateSegments(array $texts,string $target): array
    {
        $result=array_fill(0,count($texts),null);$missing=[];$indexes=[];
        foreach($texts as $i=>$text){
            $key=$this->cacheKey($target,$text);
            $cached=$this->cache->get($key);
            if(is_string($cached)){ $result[$i]=$cached; continue; }
            $missing[]=$text;$indexes[]=$i;
        }
        if($missing){
            foreach(array_chunk($missing,80) as $chunkNo=>$chunk){
                $out=$this->invoke($chunk,$target);
                foreach($chunk as $j=>$source){
                    $global=($chunkNo*80)+$j;
                    $idx=$indexes[$global]??null;
                    if($idx===null) continue;
                    $value=is_array($out)&&isset($out[$j])&&is_string($out[$j])?$out[$j]:$source;
                    $result[$idx]=$value;
                    if($value!==$source) $this->cache->put($this->cacheKey($target,$source),$value,max(3600,(int)($this->config['cache_seconds']??604800)));
                }
            }
        }
        return array_map(static fn($v,$source)=>is_string($v)?$v:$source,$result,$texts);
    }

    private function invoke(array $texts,string $target): ?array
    {
        $python=(string)($this->config['python']??'python3');
        $script=(string)($this->config['script']??'');
        if($script===''||!is_file($script)||!function_exists('proc_open')) return null;
        $cmd=escapeshellcmd($python).' '.escapeshellarg($script).' '.escapeshellarg($target);
        $pipes=[];
        $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,['PYTHONUNBUFFERED'=>'1']);
        if(!is_resource($proc)) return null;
        fwrite($pipes[0],json_encode($texts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?:'[]'); fclose($pipes[0]);
        $stdout=stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr=stream_get_contents($pipes[2]); fclose($pipes[2]);
        $code=proc_close($proc);
        if($code!==0){
            $this->logger->error('Offline translation failed.',['target'=>$target,'error'=>trim((string)$stderr)]);
            return null;
        }
        $decoded=json_decode((string)$stdout,true);
        return is_array($decoded)?$decoded:null;
    }

    private function enabled(): bool { return (bool)($this->config['enabled']??true); }
    private function targetCode(string $language): string {
        return match(strtolower($language)){'fil'=>'tl','zh'=>'zh','no'=>'no',default=>strtolower($language)};
    }
    private function cacheKey(string $target,string $text): string { return 'offline-translate:'.$target.':'.hash('sha256',$text); }

    private function updateStack(string $tag,array &$stack): void
    {
        if(preg_match('/^<\s*!|^<\s*\?/',$tag)) return;
        if(preg_match('/^<\s*\/\s*([a-z0-9:-]+)/i',$tag,$m)){
            $closing=strtolower($m[1]);
            for($i=count($stack)-1;$i>=0;$i--){$entry=array_pop($stack);if(($entry['tag']??'')===$closing)break;}
            return;
        }
        if(!preg_match('/^<\s*([a-z0-9:-]+)/i',$tag,$m)) return;
        $name=strtolower($m[1]);
        if(in_array($name,self::VOID_TAGS,true)||str_ends_with(trim($tag),'/>')) return;
        $parent=$this->skipCurrent($stack);
        $self=in_array($name,self::SKIP_TAGS,true)
            ||preg_match('/\btranslate\s*=\s*(["\'])no\1/i',$tag)
            ||preg_match('/\bclass\s*=\s*(["\'])[^"\']*\bnotranslate\b[^"\']*\1/i',$tag);
        $stack[]=['tag'=>$name,'skip'=>$parent||(bool)$self];
    }
    private function skipCurrent(array $stack): bool { return $stack?(bool)($stack[count($stack)-1]['skip']??false):false; }
}
