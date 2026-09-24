<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;

final class UserPreferenceService {
    public function __construct(private readonly ?Database $db = null) {}
    public function setLanguage(int $userId, string $languageCode): void { if (!$this->db || $userId < 1) return; $this->db->execute('UPDATE users u INNER JOIN languages l ON l.code=? AND l.active=1 SET u.preferred_language_id=l.id, u.updated_at=NOW() WHERE u.id=?', [$languageCode, $userId]); }
}
