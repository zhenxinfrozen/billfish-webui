<?php
/**
 * Billfish Manager V3 - 基于SQLite3直连
 * 不再依赖JSON映射文件,直接查询数据库
 */

class BillfishManagerV3 {
    private $db;
    private $billfishPath;
    private $dbPath;
    
    public function __construct($billfishPath) {
        $this->billfishPath = rtrim($billfishPath, '\\/');
        $this->dbPath = $this->billfishPath . '/.bf/billfish.db';
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        if (!file_exists($this->dbPath)) {
            throw new Exception('Billfish数据库不存在: ' . $this->dbPath);
        }
        
        try {
            $this->db = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        } catch (Exception $e) {
            throw new Exception('无法连接数据库: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取库统计信息
     */
    public function getLibraryStats() {
        $stats = [];
        
        // 总文件数
        $stats['total_files'] = $this->db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
        
        // 视频文件数(与bf_file相同,因为bf_material_v2只是附加信息)
        $stats['video_count'] = $stats['total_files'];
        
        // 总大小(使用bf_file.file_size)
        $stats['total_size'] = $this->db->querySingle('SELECT SUM(file_size) FROM bf_file WHERE is_hide = 0');
        $stats['total_size_gb'] = round($stats['total_size'] / 1024 / 1024 / 1024, 2);
        
        // 标签数
        $stats['tag_count'] = $this->db->querySingle('SELECT COUNT(*) FROM bf_tag');
        
        return $stats;
    }
    
    /**
     * 获取最近文件
     */
    public function getRecentFiles($limit = 12) {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.file_size,
                f.ctime,
                f.mtime,
                t.name as type_name
            FROM bf_file f
            LEFT JOIN bf_type t ON f.tid = t.tid
            WHERE f.is_hide = 0
            ORDER BY f.ctime DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $files = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $files[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'size' => $row['file_size'],
                'size_mb' => round($row['file_size'] / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i', $row['ctime']),
                'category' => $row['type_name'] ?? 'unknown',
                'preview_url' => $this->getPreviewUrl($row['id'])
            ];
        }
        
        return $files;
    }
    
    /**
     * 获取所有文件
     */
    public function getAllFiles(&$files, $category = null) {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.file_size,
                f.ctime,
                t.name as type_name
            FROM bf_file f
            LEFT JOIN bf_type t ON f.tid = t.tid
            WHERE f.is_hide = 0
        ";
        
        if ($category) {
            $query .= " AND t.name = ?";
        }
        
        $query .= " ORDER BY f.ctime DESC";
        
        if ($category) {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(1, $category, SQLITE3_TEXT);
            $result = $stmt->execute();
        } else {
            $result = $this->db->query($query);
        }
        
        $files = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $files[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'size' => $row['file_size'],
                'size_mb' => round($row['file_size'] / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i', $row['ctime']),
                'category' => $row['type_name'] ?? 'unknown',
                'preview_url' => $this->getPreviewUrl($row['id'])
            ];
        }
    }
    
    /**
     * 获取预览图URL
     */
    private function getPreviewUrl($fileId) {
        // 添加版本参数强制浏览器刷新缓存
        return "preview.php?id=" . $fileId . "&v=" . time();
    }
    
    /**
     * 搜索文件
     */
    public function searchFiles($keyword, $category = null) {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.file_size,
                f.ctime,
                t.name as type_name
            FROM bf_file f
            LEFT JOIN bf_type t ON f.tid = t.tid
            WHERE f.is_hide = 0
            AND f.name LIKE ?
        ";
        
        if ($category) {
            $query .= " AND t.name = ?";
        }
        
        $query .= " ORDER BY f.ctime DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, '%' . $keyword . '%', SQLITE3_TEXT);
        if ($category) {
            $stmt->bindValue(2, $category, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        
        $files = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $files[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'size' => $row['file_size'],
                'size_mb' => round($row['file_size'] / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i', $row['ctime']),
                'category' => $row['type_name'] ?? 'unknown',
                'preview_url' => $this->getPreviewUrl($row['id'])
            ];
        }
        
        return $files;
    }
    
    /**
     * 根据ID获取单个文件信息
     */
    public function getFileById($id) {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.file_size,
                f.ctime,
                f.mtime,
                f.tid,
                f.pid,
                t.name as type_name,
                fo.name as folder_name,
                fo.id as folder_id
            FROM bf_file f
            LEFT JOIN bf_type t ON f.tid = t.tid
            LEFT JOIN bf_folder fo ON f.pid = fo.id
            WHERE f.id = ? AND f.is_hide = 0
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        // 根据文件名推断文件扩展名和类型
        $extension = strtolower(pathinfo($row['name'], PATHINFO_EXTENSION));
        
        // 构建完整文件路径
        $fullPath = $this->billfishPath;  // 使用billfishPath而不是dbPath
        $folderPath = $this->buildFullFolderPath($row['folder_id']);
        if (!empty($folderPath)) {
            $fullPath .= '/' . $folderPath;
        }
        $fullPath .= '/' . $row['name'];
        
        // 获取预览图路径
        $previewPath = $this->getPreviewPath($row['id']);
        
        // 获取扩展信息
        $extendedInfo = $this->getExtendedFileInfo($row['id']);
        
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'extension' => $extension,
            'size' => $row['file_size'],
            'file_size' => $row['file_size'], // 兼容旧字段名
            'size_mb' => round($row['file_size'] / 1024 / 1024, 2),
            'created_at' => date('Y-m-d H:i:s', $row['ctime']),
            'modified' => $row['mtime'] ?: $row['ctime'], // 修改时间，如果没有则用创建时间
            'ctime' => $row['ctime'],
            'mtime' => $row['mtime'],
            'category' => $row['type_name'] ?? 'unknown',
            'type_name' => $row['type_name'] ?? 'unknown',
            'folder_name' => $row['folder_name'] ?? '',
            'folder_id' => $row['folder_id'] ?? null,
            'path' => $row['folder_name'] ? $row['folder_name'] . '/' . $row['name'] : $row['name'],
            'full_path' => $fullPath,
            'preview_url' => $this->getPreviewUrl($row['id']),
            'preview_path' => $previewPath,
            'has_preview' => $previewPath && file_exists($previewPath),
            // Billfish元数据 - 从扩展信息获取
            'score' => $extendedInfo['score'],
            'tags' => $extendedInfo['tags'],
            'annotation' => $extendedInfo['annotation'],
            'note' => $extendedInfo['note'],
            'origin' => $extendedInfo['origin'],
            'comments_summary' => $extendedInfo['comments_summary'],
            'comments_count' => $extendedInfo['comments_count'],
            'rotation' => $extendedInfo['rotation'],
            'width' => $extendedInfo['width'],
            'height' => $extendedInfo['height'],
            'colors' => $extendedInfo['colors'],
            'status' => $extendedInfo['status'],
            // 计算字段
            'dimensions' => ($extendedInfo['width'] && $extendedInfo['height']) 
                ? $extendedInfo['width'] . 'x' . $extendedInfo['height'] 
                : null,
        ];
    }
    
    /**
     * 获取预览图文件路径
     */
    private function getPreviewPath($fileId) {
        // 获取文件信息和缩略图状态
        $fileQuery = "
            SELECT f.name, f.pid, m.thumb_tid
            FROM bf_file f
            LEFT JOIN bf_material_v2 m ON f.id = m.file_id
            WHERE f.id = ? AND f.is_hide = 0
        ";
        $stmt = $this->db->prepare($fileQuery);
        $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $fileInfo = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$fileInfo) {
            return null;
        }
        
        // 根据thumb_tid决定使用缩略图还是原图
        if ($fileInfo['thumb_tid'] == 60) {
            // 有缩略图，使用Billfish的标准缩略图路径
            $hexFolder = sprintf("%02x", $fileId % 256);
            $previewDir = $this->billfishPath . "/.bf/.preview/{$hexFolder}/";
            
            // 优先检查用户自定义缩略图 (cover.png)
            $coverPath = $previewDir . "{$fileId}.cover.png";
            if (file_exists($coverPath)) {
                return $coverPath;
            }
            
            // 检查用户自定义缩略图 (cover.webp)
            $coverWebpPath = $previewDir . "{$fileId}.cover.webp";
            if (file_exists($coverWebpPath)) {
                return $coverWebpPath;
            }
            
            // 回退到默认缩略图
            $smallPath = $previewDir . "{$fileId}.small.webp";
            if (file_exists($smallPath)) {
                return $smallPath;
            }
            
            // 尝试HD版本
            $hdPath = $previewDir . "{$fileId}.hd.webp";
            if (file_exists($hdPath)) {
                return $hdPath;
            }
            
            // 如果缩略图文件不存在，返回null
            return null;
        } else {
            // thumb_tid = 0，没有缩略图，使用原图
            // 构建完整文件路径
            $folderPath = $this->buildFullFolderPath($fileInfo['pid']);
            $originalPath = $this->billfishPath;
            if (!empty($folderPath)) {
                $originalPath .= '/' . $folderPath;
            }
            $originalPath .= '/' . $fileInfo['name'];
            
            if (file_exists($originalPath)) {
                return $originalPath;
            }
        }
        
        return null;
    }
    
    /**
     * 获取文件的扩展信息（评分、标签、备注等）
     */
    public function getExtendedFileInfo($fileId) {
        $info = [
            'score' => 0,
            'tags' => [],
            'annotation' => '',
            'note' => '',
            'origin' => '',
            'comments_summary' => '',
            'comments_count' => 0,
            'rotation' => 0,
            'width' => null,
            'height' => null,
            'colors' => [],
            'status' => null
        ];
        
        // 从bf_material_userdata表获取用户数据
        $query = "SELECT * FROM bf_material_userdata WHERE file_id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row) {
            $info['score'] = intval($row['score'] ?? 0);
            $info['annotation'] = $row['note'] ?? '';
            $info['note'] = $row['note'] ?? '';
            $info['origin'] = $row['origin'] ?? '';
            $info['comments_summary'] = $row['comments_summary'] ?? '';
            $info['comments_count'] = intval($row['comments_count'] ?? 0);
            $info['rotation'] = intval($row['rotation'] ?? 0);
        }
        
        // 从bf_material_v2表获取媒体元数据
        $materialQuery = "SELECT * FROM bf_material_v2 WHERE file_id = ? LIMIT 1";
        $stmt = $this->db->prepare($materialQuery);
        $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $materialRow = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($materialRow) {
            $info['width'] = $materialRow['w'] ? intval($materialRow['w']) : null;
            $info['height'] = $materialRow['h'] ? intval($materialRow['h']) : null;
            $info['status'] = intval($materialRow['status'] ?? 1);
            
            // 解析颜色信息
            if (!empty($materialRow['colors'])) {
                $colorPairs = explode('|', $materialRow['colors']);
                $colors = [];
                foreach ($colorPairs as $pair) {
                    $parts = explode(',', $pair);
                    if (count($parts) === 2) {
                        $colors[] = [
                            'percentage' => floatval($parts[0]),
                            'color' => '#' . str_pad(dechex(intval($parts[1])), 6, '0', STR_PAD_LEFT)
                        ];
                    }
                }
                $info['colors'] = $colors;
            }
        }
        
        // 获取标签 - 从bf_tag_v2表读取真实标签名称
        $tagQuery = "
            SELECT tjf.tag_id, tv2.name, tv2.color 
            FROM bf_tag_join_file tjf
            LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id 
            WHERE tjf.file_id = ?
        ";
        $stmt = $this->db->prepare($tagQuery);
        $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $tags = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tags[] = [
                'id' => $row['tag_id'],
                'name' => $row['name'] ?: "未知标签#{$row['tag_id']}", // 如果名称为空，显示ID
                'color' => $row['color'] ?: '#6c757d' // 默认颜色
            ];
        }
        $info['tags'] = $tags;
        
        return $info;
    }

    /**
     * 获取所有分类及文件数量
     */
    public function getAllCategories() {
        $query = "
            SELECT 
                t.tid,
                t.name,
                COUNT(f.id) as file_count
            FROM bf_type t
            LEFT JOIN bf_file f ON f.tid = t.tid AND f.is_hide = 0
            GROUP BY t.tid, t.name
            HAVING file_count > 0
            ORDER BY t.name ASC
        ";
        
        $result = $this->db->query($query);
        $categories = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = [
                'id' => $row['tid'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $categories;
    }
    
    /**
     * 获取所有文件夹及文件数量
     */
    public function getAllFolders() {
        $query = "
            SELECT 
                f.id,
                f.name,
                COUNT(bf.id) as file_count
            FROM bf_folder f
            LEFT JOIN bf_file bf ON bf.pid = f.id AND bf.is_hide = 0
            GROUP BY f.id, f.name
            HAVING file_count > 0
            ORDER BY f.name ASC
        ";
        
        $result = $this->db->query($query);
        $folders = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $folders[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $folders;
    }
    
    /**
     * 获取所有标签及文件数量
     */
    public function getAllTags() {
        $query = "
            SELECT 
                tv2.id,
                tv2.name,
                COUNT(tjf.file_id) as file_count
            FROM bf_tag_v2 tv2
            LEFT JOIN bf_tag_join_file tjf ON tv2.id = tjf.tag_id
            LEFT JOIN bf_file f ON tjf.file_id = f.id AND f.is_hide = 0
            GROUP BY tv2.id, tv2.name
            HAVING file_count > 0
            ORDER BY tv2.name ASC
        ";
        
        $result = $this->db->query($query);
        $tags = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tags[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $tags;
    }

    /**
     * 获取当前筛选结果中的标签
     */
    public function getTagsFromFiles($files) {
        if (empty($files)) {
            return [];
        }
        
        $fileIds = array_column($files, 'id');
        $fileIdsStr = implode(',', array_map('intval', $fileIds));
        
        $query = "
            SELECT 
                tv2.id,
                tv2.name,
                COUNT(tjf.file_id) as file_count
            FROM bf_tag_v2 tv2
            INNER JOIN bf_tag_join_file tjf ON tv2.id = tjf.tag_id
            WHERE tjf.file_id IN ({$fileIdsStr})
            GROUP BY tv2.id, tv2.name
            ORDER BY tv2.name ASC
        ";
        
        $result = $this->db->query($query);
        $tags = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tags[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $tags;
    }
    
    /**
     * 高级筛选获取文件
     */
    public function getFilesWithFilters($filters = []) {
        $query = "
            SELECT DISTINCT
                f.id,
                f.name,
                f.file_size,
                f.ctime,
                t.name as type_name
            FROM bf_file f
            LEFT JOIN bf_type t ON f.tid = t.tid
        ";
        
        // 如果有标签筛选，添加JOIN
        if (!empty($filters['tag'])) {
            $query .= " INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id";
        }
        
        $query .= " WHERE f.is_hide = 0";
        
        $conditions = [];
        
        // 分类筛选
        if (!empty($filters['category'])) {
            $conditions[] = "t.name = '" . $this->db->escapeString($filters['category']) . "'";
        }
        
        // 文件夹筛选
        if (!empty($filters['folder'])) {
            $conditions[] = "f.pid = " . intval($filters['folder']);
        }
        
        // 标签筛选
        if (!empty($filters['tag'])) {
            $conditions[] = "tjf.tag_id = " . intval($filters['tag']);
        }
        
        // 文件大小筛选
        if (!empty($filters['size_min'])) {
            $conditions[] = "f.file_size >= " . (intval($filters['size_min']) * 1024 * 1024);
        }
        if (!empty($filters['size_max'])) {
            $conditions[] = "f.file_size <= " . (intval($filters['size_max']) * 1024 * 1024);
        }
        
        // 关键词搜索
        if (!empty($filters['search'])) {
            $conditions[] = "f.name LIKE '%" . $this->db->escapeString($filters['search']) . "%'";
        }
        
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }
        
        // 排序
        $orderBy = "f.ctime DESC"; // 默认排序
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'newest':
                    $orderBy = "f.ctime DESC";
                    break;
                case 'oldest':
                    $orderBy = "f.ctime ASC";
                    break;
                case 'name_asc':
                    $orderBy = "f.name ASC";
                    break;
                case 'name_desc':
                    $orderBy = "f.name DESC";
                    break;
                case 'size_desc':
                    $orderBy = "f.file_size DESC";
                    break;
                case 'size_asc':
                    $orderBy = "f.file_size ASC";
                    break;
            }
        }
        
        $query .= " ORDER BY " . $orderBy;
        
        $result = $this->db->query($query);
        $files = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $files[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'size' => $row['file_size'],
                'size_mb' => round($row['file_size'] / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i', $row['ctime']),
                'category' => $row['type_name'] ?? 'unknown',
                'preview_url' => $this->getPreviewUrl($row['id'])
            ];
        }
        
        return $files;
    }
    
    /**
     * 构建完整的文件夹路径（处理嵌套文件夹）
     */
    private function buildFullFolderPath($folderId) {
        if (!$folderId) {
            return '';
        }
        
        $pathParts = [];
        $currentId = $folderId;
        
        while ($currentId) {
            $query = "SELECT name, pid FROM bf_folder WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(1, $currentId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$row) {
                break;
            }
            
            array_unshift($pathParts, $row['name']);
            $currentId = $row['pid'];
        }
        
        return implode('/', $pathParts);
    }
    
    /**
     * 获取文件夹树形结构
     */
    public function getFolderTree() {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.pid,
                COUNT(bf.id) as file_count
            FROM bf_folder f
            LEFT JOIN bf_file bf ON bf.pid = f.id AND bf.is_hide = 0
            GROUP BY f.id, f.name, f.pid
            ORDER BY f.name ASC
        ";
        
        $result = $this->db->query($query);
        $folders = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $folders[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'pid' => $row['pid'],
                'file_count' => $row['file_count'],
                'children' => []
            ];
        }
        
        // 构建树形结构
        $tree = [];
        $folderMap = [];
        
        foreach ($folders as $folder) {
            $folderMap[$folder['id']] = $folder;
        }
        
        foreach ($folders as $folder) {
            if ($folder['pid'] == 0 || !isset($folderMap[$folder['pid']])) {
                $tree[] = &$folderMap[$folder['id']];
            } else {
                $folderMap[$folder['pid']]['children'][] = &$folderMap[$folder['id']];
            }
        }
        
        return $tree;
    }
    
    /**
     * 获取基于当前筛选条件的分类统计
     */
    public function getFilteredCategories($filters = []) {
        $query = "
            SELECT 
                t.tid,
                t.name,
                COUNT(f.id) as file_count
            FROM bf_type t
            INNER JOIN bf_file f ON f.tid = t.tid AND f.is_hide = 0
        ";
        
        // 如果有标签筛选，添加JOIN
        if (!empty($filters['tag'])) {
            $query .= " INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id";
        }
        
        $query .= " WHERE 1=1";
        
        $conditions = [];
        
        // 文件夹筛选
        if (!empty($filters['folder'])) {
            $conditions[] = "f.pid = " . intval($filters['folder']);
        }
        
        // 标签筛选
        if (!empty($filters['tag'])) {
            $conditions[] = "tjf.tag_id = " . intval($filters['tag']);
        }
        
        // 文件大小筛选
        if (!empty($filters['size_min'])) {
            $conditions[] = "f.file_size >= " . (intval($filters['size_min']) * 1024 * 1024);
        }
        if (!empty($filters['size_max'])) {
            $conditions[] = "f.file_size <= " . (intval($filters['size_max']) * 1024 * 1024);
        }
        
        // 关键词搜索
        if (!empty($filters['search'])) {
            $conditions[] = "f.name LIKE '%" . $this->db->escapeString($filters['search']) . "%'";
        }
        
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }
        
        $query .= " GROUP BY t.tid, t.name HAVING file_count > 0 ORDER BY t.name ASC";
        
        $result = $this->db->query($query);
        $categories = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = [
                'id' => $row['tid'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $categories;
    }
    
    /**
     * 获取基于当前筛选条件的标签统计
     */
    public function getFilteredTags($filters = []) {
        $query = "
            SELECT 
                tv2.id,
                tv2.name,
                COUNT(DISTINCT f.id) as file_count
            FROM bf_tag_v2 tv2
            INNER JOIN bf_tag_join_file tjf ON tv2.id = tjf.tag_id
            INNER JOIN bf_file f ON tjf.file_id = f.id AND f.is_hide = 0
        ";
        
        $query .= " WHERE 1=1";
        
        $conditions = [];
        
        // 文件夹筛选
        if (!empty($filters['folder'])) {
            $conditions[] = "f.pid = " . intval($filters['folder']);
        }
        
        // 分类筛选
        if (!empty($filters['category'])) {
            $conditions[] = "f.tid IN (SELECT tid FROM bf_type WHERE name = '" . $this->db->escapeString($filters['category']) . "')";
        }
        
        // 文件大小筛选
        if (!empty($filters['size_min'])) {
            $conditions[] = "f.file_size >= " . (intval($filters['size_min']) * 1024 * 1024);
        }
        if (!empty($filters['size_max'])) {
            $conditions[] = "f.file_size <= " . (intval($filters['size_max']) * 1024 * 1024);
        }
        
        // 关键词搜索
        if (!empty($filters['search'])) {
            $conditions[] = "f.name LIKE '%" . $this->db->escapeString($filters['search']) . "%'";
        }
        
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }
        
        $query .= " GROUP BY tv2.id, tv2.name HAVING file_count > 0 ORDER BY tv2.name ASC";
        
        $result = $this->db->query($query);
        $tags = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tags[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'count' => $row['file_count']
            ];
        }
        
        return $tags;
    }
    
    /**
     * 获取文件夹名称
     */
    public function getFolderName($folderId) {
        if (empty($folderId)) {
            return null;
        }
        
        $query = "SELECT name FROM bf_folder WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, intval($folderId), SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['name'] : null;
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
