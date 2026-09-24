<?php
declare(strict_types=1);
namespace App\Support;

final class Cache {
    public function __construct(private readonly string $directory){}
    public function get(string $key,mixed $default=null):mixed{$path=$this->path($key);if(!is_file($path))return$default;$raw=@file_get_contents($path);if($raw===false)return$default;$item=@unserialize($raw,['allowed_classes'=>false]);if(!is_array($item)||!isset($item['expires'])||(int)$item['expires']<time()){@unlink($path);return$default;}return$item['value']??null;}
    public function put(string $key,mixed $value,int $seconds=3600):void{if(!is_dir($this->directory)&&!mkdir($this->directory,0750,true)&&!is_dir($this->directory))throw new \RuntimeException('Cache directory is unavailable.');file_put_contents($this->path($key),serialize(['expires'=>time()+max(1,$seconds),'value'=>$value]),LOCK_EX);}
    public function forget(string $key):void{@unlink($this->path($key));}
    public function remember(string $key,int $seconds,callable $resolver):mixed{$sentinel=new \stdClass();$value=$this->get($key,$sentinel);if($value!==$sentinel)return$value;$value=$resolver();$this->put($key,$value,$seconds);return$value;}
    private function path(string $key):string{return $this->directory.'/'.hash('sha256',$key).'.cache';}
}
