<?php

class Share extends Model {
    protected $table = 'shares';
    
    
    public function createShare($userId, $fileId, $options = []) {
        $shareCode = Helper::randomString(8);
        
        $data = [
            'user_id' => $userId,
            'file_id' => $fileId,
            'share_code' => $shareCode,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($options['password'])) {
            $data['password'] = password_hash($options['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($options['expire_days'])) {
            $expireTime = strtotime("+{$options['expire_days']} days");
            $data['expire_at'] = date('Y-m-d H:i:s', $expireTime);
        }
        
        if (!empty($options['max_downloads'])) {
            $data['max_downloads'] = $options['max_downloads'];
        }
        
        $shareId = $this->create($data);
        
        return [
            'id' => $shareId,
            'share_code' => $shareCode
        ];
    }
    
    
    public function findByCode($shareCode) {
        if (!preg_match('/^[a-z0-9]{8,32}$/i', $shareCode)) return null;
        $sql = "SELECT s.*, u.username FROM shares s LEFT JOIN users u ON s.user_id = u.id WHERE s.share_code = ? AND s.status = 1";
        return $this->db->fetchOne($sql, [$shareCode]);
    }
    
    
    public function verifyAccess($shareCode, $password = null) {
        $share = $this->findByCode($shareCode);
        
        if (!$share) {
            return ['success' => false, 'message' => '分享链接不存在或已失效'];
        }

        if ($share['expire_at'] && strtotime($share['expire_at']) < time()) {
            return ['success' => false, 'message' => '分享链接已过期'];
        }

        if ($share['max_downloads'] > 0 && $share['download_count'] >= $share['max_downloads']) {
            return ['success' => false, 'message' => '分享链接已达到最大下载次数'];
        }

        if ($share['password'] && !password_verify($password ?? '', $share['password'])) {
            return ['success' => false, 'message' => '密码错误', 'need_password' => true];
        }
        
        return ['success' => true, 'share' => $share];
    }
    
    
    public function incrementDownload($shareId) {
        $db = Database::getInstance();
        $sql = "UPDATE shares SET download_count = download_count + 1 WHERE id = ?";
        return $db->execute($sql . " AND status = 1 AND (max_downloads = 0 OR download_count < max_downloads)", [$shareId]);
    }
    
    
    public function getUserShares($userId, $page = 1, $perPage = 20) {
        $where = "s.user_id = ? AND s.status = 1";
        $params = [$userId];
        
        $countSql = "SELECT COUNT(*) as count FROM shares s WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['count'];
        
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*, f.name as file_name, f.type as file_type, f.size as file_size,
                (CASE WHEN s.expire_at IS NOT NULL AND s.expire_at < NOW() THEN 1 ELSE 0 END) as is_expired
                FROM shares s
                LEFT JOIN files f ON s.file_id = f.id
                WHERE {$where}
                ORDER BY s.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    
    public function cancelShare($id, $userId) {
        return $this->update($id, ['status' => 0]);
    }
    
    
    public function getStats() {
        $totalShares = $this->count();
        $activeShares = $this->count('status = 1');
        $totalDownloads = $this->db->fetchOne("SELECT SUM(download_count) as total FROM shares")['total'] ?? 0;
        
        return [
            'total_shares' => $totalShares,
            'active_shares' => $activeShares,
            'total_downloads' => $totalDownloads
        ];
    }
}
