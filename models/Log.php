<?php

class Log extends Model {
    protected $table = 'operation_logs';
    
    
    public function addLog($action, $targetType = null, $targetId = null, $details = null) {
        $userId = Session::getUserId();
        
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => Helper::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    public function getLogs($page = 1, $perPage = 20, $userId = null, $action = null) {
        $where = "1=1";
        $params = [];
        
        if ($userId) {
            $where .= " AND l.user_id = ?";
            $params[] = $userId;
        }
        
        if ($action) {
            $where .= " AND l.action = ?";
            $params[] = $action;
        }
        
        $countSql = "SELECT COUNT(*) as count FROM operation_logs l WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['count'];
        
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT l.*, u.username, u.email
                FROM operation_logs l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE {$where}
                ORDER BY l.created_at DESC
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
    
    
    public function getUserLogs($userId, $page = 1, $perPage = 20) {
        return $this->getLogs($page, $perPage, $userId);
    }
    
    
    public function cleanOldLogs() {
        $sql = "DELETE FROM operation_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
        return $this->db->execute($sql);
    }
}
