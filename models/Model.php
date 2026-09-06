<?php

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    
    public function findOne($where, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }
    
    
    public function findAll($where = '1=1', $params = [], $orderBy = '', $limit = '') {
        $sql = "SELECT * FROM {$this->table} WHERE {$where}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->fetchAll($sql, $params);
    }
    
    
    public function paginate($where = '1=1', $params = [], $page = 1, $perPage = 20, $orderBy = 'created_at DESC') {
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['count'];
        
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    
    public function create($data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        return $this->db->insert($sql, array_values($data));
    }
    
    
    public function update($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
        }
        $set = implode(', ', $set);
        $sql = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, array_merge(array_values($data), [$id]));
    }
    
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    
    public function count($where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
        return $this->db->fetchOne($sql, $params)['count'];
    }
    
    
    public function sum($field, $where = '1=1', $params = []) {
        $sql = "SELECT SUM({$field}) as total FROM {$this->table} WHERE {$where}";
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
}
