<?php

class User extends Model {
    protected $table = 'users';
    
    
    public function findByEmail($email) {
        return $this->findOne('email = ?', [$email]);
    }
    
    
    public function findByUsername($username) {
        return $this->findOne('username = ?', [$username]);
    }
    
    
    public function createUser($data) {
        $config = require __DIR__ . '/../config/app.php';
        
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['storage_total'] = $config['default_user_space'];
        $data['storage_used'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    
    public function updateLastLogin($userId) {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    
    public function updateStorageUsed($userId, $delta) {
        $db = Database::getInstance();
        $sql = "UPDATE users SET storage_used = storage_used + ? WHERE id = ?";
        return $db->execute($sql, [$delta, $userId]);
    }
    
    
    public function checkStorage($userId, $requiredSize) {
        $user = $this->find($userId);
        return ($user['storage_total'] - $user['storage_used']) >= $requiredSize;
    }
    
    
    public function getAllUsers($page = 1, $perPage = 20) {
        return $this->paginate('1=1', [], $page, $perPage, 'created_at DESC');
    }
    
    
    public function getUserCount() {
        return $this->count();
    }
    
    
    public function getTotalStorageUsed() {
        return $this->sum('storage_used');
    }
    
    
    public function toggleStatus($userId) {
        $user = $this->find($userId);
        $newStatus = $user['status'] == 1 ? 0 : 1;
        return $this->update($userId, ['status' => $newStatus]);
    }
    
    
    public function search($keyword, $page = 1, $perPage = 20) {
        $where = "username LIKE ? OR email LIKE ?";
        $params = ["%{$keyword}%", "%{$keyword}%"];
        return $this->paginate($where, $params, $page, $perPage);
    }
}
