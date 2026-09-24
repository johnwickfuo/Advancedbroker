<?php
declare(strict_types=1);
namespace App\Mail;

use App\Support\Logger;

final class MailService {
    public function __construct(private array $config,private Logger $logger,private string $viewsPath) {}
    public function send(string $to,string $subject,string $template,array $data=[]): void {
        if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new \InvalidArgumentException('Invalid recipient email address.');
        $subject=trim(str_replace(["\r","\n"],' ',$subject));$html=$this->render($template,$data);$driver=strtolower((string)($this->config['driver']??'log'));
        if($driver==='log'){ $this->logger->info('Mail logged',['to'=>$to,'subject'=>$subject,'template'=>$template]); return; }
        if($driver==='mail'){ $this->sendMailFunction($to,$subject,$html); return; }
        if($driver==='smtp'){ $this->sendSmtp($to,$subject,$html); return; }
        throw new \RuntimeException('Unsupported mail driver.');
    }
    private function render(string $template,array $data): string {
        if(!preg_match('/^[a-z0-9_-]+$/i',$template))throw new \InvalidArgumentException('Invalid mail template.');
        $file=$this->viewsPath.'/emails/'.$template.'.php';if(!is_file($file))throw new \RuntimeException('Mail template not found.');
        extract($data,EXTR_SKIP);ob_start();require$file;$body=(string)ob_get_clean();
        return '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">'.$body.'</body></html>';
    }
    private function headers(string $to,string $subject,string $html): string {
        $from=(string)($this->config['from']['address']??'');$name=(string)($this->config['from']['name']??config('app.name'));
        if(!filter_var($from,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('MAIL_FROM_ADDRESS is not configured.');
        return "From: ".$this->encode($name)." <".$from.">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    }
    private function sendMailFunction(string $to,string $subject,string $html): void {
        if(!mail($to,$subject,$html,$this->headers($to,$subject,$html)))throw new \RuntimeException('Mail delivery failed.');
    }
    private function sendSmtp(string $to,string $subject,string $html): void {
        $host=(string)($this->config['host']??'');$port=(int)($this->config['port']??587);$encryption=strtolower((string)($this->config['encryption']??'tls'));
        if($host===''||$port<1)throw new \RuntimeException('SMTP host/port are not configured.');
        $transport=$encryption==='ssl'?'ssl://':'tcp://';$errno=0;$errstr='';
        $fp=@stream_socket_client($transport.$host.':'.$port,$errno,$errstr,15,STREAM_CLIENT_CONNECT);
        if(!$fp)throw new \RuntimeException('SMTP connection failed.');stream_set_timeout($fp,15);$this->expect($fp,[220]);
        $hostname=gethostname()?:'localhost';$this->cmd($fp,'EHLO '.$hostname,[250]);
        if($encryption==='tls'){$this->cmd($fp,'STARTTLS',[220]);if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new \RuntimeException('SMTP TLS negotiation failed.');$this->cmd($fp,'EHLO '.$hostname,[250]);}
        $user=(string)($this->config['username']??'');$pass=(string)($this->config['password']??'');
        if($user!==''){$this->cmd($fp,'AUTH LOGIN',[334]);$this->cmd($fp,base64_encode($user),[334]);$this->cmd($fp,base64_encode($pass),[235]);}
        $from=(string)($this->config['from']['address']??'');if(!filter_var($from,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('MAIL_FROM_ADDRESS is not configured.');
        $this->cmd($fp,'MAIL FROM:<'.$from.'>',[250]);$this->cmd($fp,'RCPT TO:<'.$to.'>',[250,251]);$this->cmd($fp,'DATA',[354]);
        $name=(string)($this->config['from']['name']??config('app.name'));$message='From: '.$this->encode($name).' <'.$from.">\r\n".'To: <'.$to.">\r\n".'Subject: '.$this->encode($subject)."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n".$html;
        $message=preg_replace('/(?m)^\./','..',$message)??$message;fwrite($fp,str_replace(["\r\n","\r"],"\n",$message));fwrite($fp,"\r\n.\r\n");$this->expect($fp,[250]);$this->cmd($fp,'QUIT',[221]);fclose($fp);
    }
    private function cmd($fp,string $command,array $codes): void { fwrite($fp,$command."\r\n");$this->expect($fp,$codes); }
    private function expect($fp,array $codes): void { $last='';do{$line=fgets($fp,515);if($line===false)throw new \RuntimeException('SMTP connection closed unexpectedly.');$last=$line;}while(isset($line[3])&&$line[3]==='-');$code=(int)substr($last,0,3);if(!in_array($code,$codes,true))throw new \RuntimeException('SMTP server rejected the request ('.$code.').'); }
    private function encode(string $value): string { return '=?UTF-8?B?'.base64_encode($value).'?='; }
}
