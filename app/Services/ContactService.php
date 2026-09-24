<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;

final class ContactService {
    public function __construct(private ?Database $db) {}
    public function submit(array $data,int $countryId,?int $userId,string $ip,string $ua): int {
        if(!$this->db)throw new \RuntimeException('Database unavailable.');
        $this->db->execute('INSERT INTO contact_messages(country_id,user_id,name,email,subject,message,status,ip_address,user_agent,created_at,updated_at) VALUES (?,?,?,?,?,?,"unread",?,?,NOW(),NOW())',[$countryId,$userId,trim((string)$data['name']),strtolower(trim((string)$data['email'])),trim((string)$data['subject']),trim((string)$data['message']),$ip,substr($ua,0,1000)]);
        return (int)$this->db->pdo()->lastInsertId();
    }
    public function list(string $status=''): array { if(!$this->db)return [];return $status===''?$this->db->select('SELECT m.*,c.name country_name FROM contact_messages m JOIN countries c ON c.id=m.country_id ORDER BY m.created_at DESC LIMIT 200'):$this->db->select('SELECT m.*,c.name country_name FROM contact_messages m JOIN countries c ON c.id=m.country_id WHERE m.status=? ORDER BY m.created_at DESC LIMIT 200',[$status]); }
    public function setStatus(int $id,string $status): void { if(!in_array($status,['unread','read','resolved','archived'],true))throw new \InvalidArgumentException('Invalid contact status.');$this->db?->execute('UPDATE contact_messages SET status=?,updated_at=NOW() WHERE id=?',[$status,$id]); }
}
