<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;
final class LegalService {
    private const TYPES=['terms','privacy','risk-disclosure'];
    public function __construct(private ?Database $db) {}
    public function document(array $country,string $language,string $type): ?array { if(!$this->db||!in_array($type,self::TYPES,true))return null; foreach([$language,app('countries')->defaultLanguage($country),'en'] as $code){$row=$this->db->one('SELECT d.* FROM legal_documents d INNER JOIN languages l ON l.id=d.language_id WHERE d.country_id=? AND l.code=? AND d.document_type=? AND d.is_published=1 AND (d.effective_at IS NULL OR d.effective_at<=NOW()) ORDER BY d.published_at DESC,d.id DESC LIMIT 1',[$country['id'],$code,$type]);if($row)return $row;}return null; }
    public function save(int $countryId,int $languageId,string $type,array $data,int $actor): int { if(!$this->db||!in_array($type,self::TYPES,true))throw new \InvalidArgumentException('Invalid legal type.');$body=$this->sanitize((string)$data['body']);$version=trim((string)$data['version']);if($version===''||$body==='')throw new \DomainException('A legal version and body are required.');$published=!empty($data['published']);$this->db->execute('INSERT INTO legal_documents (country_id,language_id,document_type,title,body,version,is_published,published_at,effective_at,created_by_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,IF(?,NOW(),NULL),?,?,NOW(),NOW())',[$countryId,$languageId,$type,trim((string)$data['title']),$body,$version,$published?1:0,$published?1:0,$data['effective_at']?:null,$actor]);return (int)$this->db->pdo()->lastInsertId(); }
    public function list(int $countryId): array{return $this->db?->select('SELECT d.*,l.code language_code FROM legal_documents d INNER JOIN languages l ON l.id=d.language_id WHERE d.country_id=? ORDER BY d.document_type,d.created_at DESC',[$countryId])??[];}
    public function sanitize(string $html): string { $html=preg_replace('#<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*/\s*\1\s*>#is','',$html)??'';$html=preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i','',$html)??'';$html=preg_replace('/\s(href|src)\s*=\s*["\']?\s*javascript:[^\s>"\']*/i','',$html)??'';return strip_tags($html,'<p><br><h1><h2><h3><h4><strong><b><em><i><ul><ol><li><a><table><thead><tbody><tr><th><td><blockquote>'); }
}
