<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;

final class NotificationService {
    public function __construct(private ?Database $db) {}

    public function create(int $userId,string $type,string $title,string $body='',array $data=[]): void {
        $this->db?->execute(
            'INSERT INTO notifications (user_id,channel,notification_type,title,body,data,created_at) VALUES (?,"popup",?,?,?,?,NOW())',
            [$userId,$type,$title,$body,json_encode($data,JSON_THROW_ON_ERROR)]
        );
    }

    public function consumePopups(int $userId,int $limit=6): array {
        if(!$this->db) return [];
        return $this->db->transaction(function(Database $db)use($userId,$limit){
            $rows=$db->select(
                'SELECT * FROM notifications WHERE user_id=? AND channel="popup" AND read_at IS NULL ORDER BY created_at ASC LIMIT '.max(1,min($limit,20)),
                [$userId]
            );
            foreach($rows as $row){
                $db->execute('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?',[$row['id'],$userId]);
            }
            return $rows;
        });
    }

    public function recent(int $userId,int $limit=8): array { return $this->db?->select('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit,[$userId])??[]; }
    public function unreadCount(int $userId): int { return (int)($this->db?->scalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND channel="popup" AND read_at IS NULL',[$userId])??0); }
    public function markRead(int $userId,int $id): void { $this->db?->execute('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?',[$id,$userId]); }
    public function markAllRead(int $userId): void { $this->db?->execute('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[$userId]); }
}
