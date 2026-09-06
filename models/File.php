<?php

class File extends Model {
    protected $table = 'files';
    
    
    public function createFolder($userId, $parentId, $name) {

        $existing = $this->findOne(
            'user_id = ? AND parent_id = ? AND name = ? AND type = ? AND is_deleted = 0',
            [$userId, $parentId, $name, 'folder']
        );
        if ($existing) {
            return ['success' => false, 'message' => '同级目录下已存在同名文件夹'];
        }
        
        $folderId = $this->create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => $name,
            'type' => 'folder',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true, 'id' => $folderId];
    }
    
    
    public function getList($userId, $parentId, $page = 1, $perPage = 20, $orderBy = 'type ASC, name ASC', $search = '') {
        $where = "user_id = ? AND parent_id = ? AND is_deleted = 0";
        $params = [$userId, $parentId];
        
        if ($search) {
            $where .= " AND name LIKE ?";
            $params[] = "%{$search}%";
        }
        
        return $this->paginate($where, $params, $page, $perPage, $orderBy);
    }
    
    
    public function getTrashList($userId, $page = 1, $perPage = 20) {
        $where = "user_id = ? AND is_deleted = 1";
        $params = [$userId];
        
        return $this->paginate($where, $params, $page, $perPage, 'deleted_at DESC');
    }
    
    
    public function getUserTotalSize($userId) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(size) as total FROM files WHERE user_id = ? AND is_deleted = 0 AND type = 'file'";
        $result = $db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }
    
    
    public function checkFileExists($md5) {
        $sql = "SELECT f.*, u.storage_used, u.storage_total 
                FROM files f 
                JOIN users u ON f.user_id = u.id 
                WHERE f.md5 = ? AND f.is_deleted = 0 AND f.type = 'file' 
                LIMIT 1";
        return $this->db->fetchOne($sql, [$md5]);
    }
    
    
    public function createFile($userId, $parentId, $data, $physicalPath = null) {
        $existingFile = $this->checkFileExists($data['md5']);
        
        if ($existingFile) {

            $physicalPath = $existingFile['path'];
        }
        
        $fileId = $this->create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => $data['name'],
            'type' => 'file',
            'mime_type' => $data['mime_type'] ?? Helper::getMimeType($data['name']),
            'size' => $data['size'],
            'path' => $physicalPath,
            'md5' => $data['md5'],
            'extension' => pathinfo($data['name'], PATHINFO_EXTENSION),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if (!$existingFile) {
            $userModel = new User();
            $userModel->updateStorageUsed($userId, $data['size']);
        }
        
        return ['id' => $fileId, 'instant' => (bool)$existingFile];
    }
    
    
    public function rename($id, $newName) {
        return $this->update($id, ['name' => $newName]);
    }
    
    
    public function moveToTrash($id) {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        if ($item['type'] === 'folder') {
            $this->moveFolderToTrash($id);
        }
        
        return $this->update($id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    private function moveFolderToTrash($folderId) {
        $children = $this->findAll(
            'parent_id = ? AND is_deleted = 0',
            [$folderId]
        );
        
        foreach ($children as $child) {
            if ($child['type'] === 'folder') {
                $this->moveFolderToTrash($child['id']);
            }
            $this->update($child['id'], [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    
    public function restoreFromTrash($id) {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        if ($item['type'] === 'folder') {
            $this->restoreFolderFromTrash($id);
        }
        
        return $this->update($id, [
            'is_deleted' => 0,
            'deleted_at' => null
        ]);
    }
    
    
    private function restoreFolderFromTrash($folderId) {
        $children = $this->findAll(
            'parent_id = ? AND is_deleted = 1',
            [$folderId]
        );
        
        foreach ($children as $child) {
            if ($child['type'] === 'folder') {
                $this->restoreFolderFromTrash($child['id']);
            }
            $this->update($child['id'], [
                'is_deleted' => 0,
                'deleted_at' => null
            ]);
        }
    }
    
    
    public function forceDelete($id) {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        if ($item['type'] === 'folder') {
            $this->forceDeleteFolder($id);
        } else {

            if ($item['path'] && file_exists($item['path'])) {

                $sql = "SELECT COUNT(*) as count FROM files WHERE md5 = ? AND id != ?";
                $count = $this->db->fetchOne($sql, [$item['md5'], $id])['count'];
                
                if ($count == 0) {
                    unlink($item['path']);

                    $this->deleteChunks($item['md5']);
                }
            }

            $userModel = new User();
            $userModel->updateStorageUsed($item['user_id'], -$item['size']);
        }
        
        return $this->delete($id);
    }
    
    
    private function forceDeleteFolder($folderId) {
        $children = $this->findAll(
            'parent_id = ?',
            [$folderId]
        );
        
        foreach ($children as $child) {
            if ($child['type'] === 'folder') {
                $this->forceDeleteFolder($child['id']);
            } else {

                if ($child['path'] && file_exists($child['path'])) {
                    $sql = "SELECT COUNT(*) as count FROM files WHERE md5 = ? AND id != ?";
                    $count = $this->db->fetchOne($sql, [$child['md5'], $child['id']])['count'];
                    
                    if ($count == 0) {
                        unlink($child['path']);
                        $this->deleteChunks($child['md5']);
                    }
                }
                $userModel = new User();
                $userModel->updateStorageUsed($child['user_id'], -$child['size']);
            }
            $this->delete($child['id']);
        }
    }
    
    
    private function deleteChunks($md5) {
        $db = Database::getInstance();
        $chunks = $db->fetchAll("SELECT * FROM upload_chunks WHERE file_md5 = ?", [$md5]);
        
        foreach ($chunks as $chunk) {
            if (file_exists($chunk['chunk_path'])) {
                unlink($chunk['chunk_path']);
            }
        }
        
        $db->execute("DELETE FROM upload_chunks WHERE file_md5 = ?", [$md5]);
    }
    
    
    public function move($id, $targetParentId) {
        $item = $this->find($id);
        if (!$item) {
            return ['success' => false, 'message' => '文件不存在'];
        }

        if ($targetParentId != 0) {
            $targetFolder = $this->find($targetParentId);
            if (!$targetFolder || $targetFolder['user_id'] != $item['user_id']) {
                return ['success' => false, 'message' => '目标文件夹不存在'];
            }
        }

        if ($item['type'] === 'folder' && $this->isChild($id, $targetParentId)) {
            return ['success' => false, 'message' => '不能将文件夹移动到其子文件夹中'];
        }

        $existing = $this->findOne(
            'user_id = ? AND parent_id = ? AND name = ? AND type = ? AND is_deleted = 0 AND id != ?',
            [$item['user_id'], $targetParentId, $item['name'], $item['type'], $id]
        );
        if ($existing) {
            return ['success' => false, 'message' => '目标目录下已存在同名文件'];
        }
        
        $this->update($id, ['parent_id' => $targetParentId]);
        return ['success' => true];
    }
    
    
    private function isChild($parentId, $targetId) {
        if ($targetId == 0) {
            return false;
        }
        
        $children = $this->findAll('parent_id = ? AND type = ?', [$parentId, 'folder']);
        foreach ($children as $child) {
            if ($child['id'] == $targetId) {
                return true;
            }
            if ($this->isChild($child['id'], $targetId)) {
                return true;
            }
        }
        return false;
    }
    
    
    public function batchDelete($ids) {
        foreach ($ids as $id) {
            $this->moveToTrash($id);
        }
        return true;
    }
    
    
    public function batchMove($ids, $targetParentId) {
        foreach ($ids as $id) {
            $result = $this->move($id, $targetParentId);
            if (!$result['success']) {
                return $result;
            }
        }
        return ['success' => true];
    }
    
    
    public function getFullPath($fileId) {
        $path = [];
        $current = $this->find($fileId);
        
        while ($current && $current['parent_id'] != 0) {
            $path[] = $current['name'];
            $current = $this->find($current['parent_id']);
        }
        
        $path[] = $current['name'];
        
        return implode('/', array_reverse($path));
    }
    
 
    public function getStats() {
        $db = Database::getInstance();
        
        $totalFiles = $this->count("type = 'file' AND is_deleted = 0");
        $totalFolders = $this->count("type = 'folder' AND is_deleted = 0");
        $totalSize = $this->db->fetchOne("SELECT SUM(size) as total FROM files WHERE is_deleted = 0 AND type = 'file'")['total'] ?? 0;
        $trashCount = $this->count("is_deleted = 1");
        
        return [
            'total_files' => $totalFiles,
            'total_folders' => $totalFolders,
            'total_size' => $totalSize,
            'trash_count' => $trashCount
        ];
    }
}
