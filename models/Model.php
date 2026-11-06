<?php

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * v1.0.8: ORDER BY is no longer interpolated raw. Only a comma-separated
     * list of `identifier [ASC|DESC]` is accepted; anything else falls back
     * to the caller's default (or no ordering for findAll).
     */
    protected function sanitizeOrderBy($orderBy, $default = '') {
        if ($orderBy === '' || $orderBy === null) return $default;
        $parts = explode(',', (string)$orderBy);
        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)(?:\s+(ASC|DESC))?$/i', $part, $m)) {
                return $default;
            }
            $clean[] = $m[1] . (isset($m[2]) ? ' ' . strtoupper($m[2]) : '');
        }
        return $clean ? implode(', ', $clean) : $default;
    }

    /** v1.0.8: LIMIT accepts only integers, optionally "offset, count". */
    protected function sanitizeLimit($limit) {
        if ($limit === '' || $limit === null) return '';
        if (!preg_match('/^\d+(?:\s*,\s*\d+)?$/', (string)$limit)) return '';
        return (string)$limit;
    }

    /** v1.0.8: column / field names used in SQL must be plain identifiers. */
    protected function sanitizeField($field, $default = '') {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$field) ? (string)$field : $default;
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
        $orderBy = $this->sanitizeOrderBy($orderBy);
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $limit = $this->sanitizeLimit($limit);
        if ($limit !== '') {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->fetchAll($sql, $params);
    }
    
    
    public function paginate($where = '1=1', $params = [], $page = 1, $perPage = 20, $orderBy = 'created_at DESC') {
        $page = max(1, (int)$page);
        $perPage = min(100, max(1, (int)$perPage));
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
        $total = $this->db->fetchOne($countSql, $params)['count'];
        
        $offset = ($page - 1) * $perPage;
        $orderBy = $this->sanitizeOrderBy($orderBy, 'created_at DESC');
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
        $fields = [];
        foreach (array_keys($data) as $key) {
            $field = $this->sanitizeField($key);
            if ($field === '') return false;
            $fields[] = $field;
        }
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        return $this->db->insert($sql, array_values($data));
    }
    
    
    public function update($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $field = $this->sanitizeField($key);
            if ($field === '') return false;
            $set[] = "{$field} = ?";
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
        $field = $this->sanitizeField($field);
        if ($field === '') return 0;
        $sql = "SELECT SUM({$field}) as total FROM {$this->table} WHERE {$where}";
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
}
