<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Support\Database;

final class UserRepository {
    public function __construct(private ?Database $db) {}
    public function find(int $id): ?array { return $this->one('u.id=?',[$id]); }
    public function byEmail(string $email): ?array { return $this->one('LOWER(u.email)=?', [strtolower(trim($email))]); }
    public function create(array $data): array {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        $fields=['uuid','first_name','last_name','phone','email','password_hash','country_id','assigned_country_id','country_assignment_source','original_country_id','preferred_language_id','detected_country_code','account_status','role','terms_accepted_at','terms_version','privacy_accepted_at','privacy_version','created_at','updated_at'];
        $values=[]; foreach($fields as $f) $values[]=$data[$f]??null;
        $sql='INSERT INTO users ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
        $this->db->execute($sql,$values);
        return $this->find((int)$this->db->pdo()->lastInsertId()) ?? throw new \RuntimeException('User could not be created.');
    }
    public function update(int $id,array $data): void {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        $allowed=['first_name','last_name','phone','email','password_hash','country_id','assigned_country_id','country_assignment_source','original_country_id','preferred_language_id','detected_country_code','account_status','role','account_restriction_reason','email_verified_at','last_login_at','two_factor_secret_encrypted','two_factor_enabled_at'];
        $sets=[];$values=[];
        foreach($allowed as $f) if(array_key_exists($f,$data)){ $sets[]="$f=?"; $values[]=$data[$f]; }
        if(!$sets) return; $sets[]='updated_at=NOW()'; $values[]=$id;
        $this->db->execute('UPDATE users SET '.implode(',',$sets).' WHERE id=?',$values);
    }
    public function search(string $query='',int $limit=100): array {
        if(!$this->db) return [];
        $limit=max(1,min($limit,200));
        if($query==='') return $this->db->select('SELECT u.*,l.code preferred_language_code,c.name country_name FROM users u LEFT JOIN languages l ON l.id=u.preferred_language_id LEFT JOIN countries c ON c.id=COALESCE(u.assigned_country_id,u.country_id) ORDER BY u.created_at DESC LIMIT '.$limit);
        $like='%'.$query.'%';
        return $this->db->select('SELECT u.*,l.code preferred_language_code,c.name country_name FROM users u LEFT JOIN languages l ON l.id=u.preferred_language_id LEFT JOIN countries c ON c.id=COALESCE(u.assigned_country_id,u.country_id) WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.uuid=? ORDER BY u.created_at DESC LIMIT '.$limit,[$like,$like,$like,$query]);
    }
    public function languageId(string $code): ?int { if(!$this->db)return null; $id=$this->db->scalar('SELECT id FROM languages WHERE code=? AND active=1 LIMIT 1',[$code]); return $id===false||$id===null?null:(int)$id; }
    private function one(string $where,array $params): ?array {
        if(!$this->db) return null;
        return $this->db->one('SELECT u.*,l.code preferred_language_code,c.slug country_slug,c.name country_name FROM users u LEFT JOIN languages l ON l.id=u.preferred_language_id LEFT JOIN countries c ON c.id=COALESCE(u.assigned_country_id,u.country_id) WHERE '.$where.' LIMIT 1',$params);
    }
}
