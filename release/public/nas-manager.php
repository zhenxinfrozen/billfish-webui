<?php
/**
 * NAS 多库管理配置工具
 * 为多个Billfish资料库提供快速切换
 */

class NASLibraryManager {
    private $libraries = [
        'material' => [
            'name' => 'Bill-Material',
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-Material',
            'description' => '主要素材库 (121,758个文件)',
            'icon' => '🎨'
        ],
        'tutorials' => [
            'name' => 'Bill-TUT', 
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-TUT',
            'description' => '教程资料 (10,983个文件)',
            'icon' => '📚'
        ],
        'storyboard' => [
            'name' => 'Bill-Storyboard',
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-Storyboard', 
            'description' => '分镜故事板 (8,214个文件)',
            'icon' => '🎬'
        ],
        'artbooks' => [
            'name' => 'Bill-ArtBooks',
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-ArtBooks',
            'description' => '艺术书籍 (8,441个文件)', 
            'icon' => '📖'
        ],
        'blender' => [
            'name' => 'Bill-SD-Blender',
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-SD-Blender',
            'description' => 'Blender/SD资源 (7,807个文件)',
            'icon' => '🎯'
        ],
        'videos' => [
            'name' => 'Bill-Videos', 
            'path' => 'S:/OneDrive-irm/Bill-Eagle/Bill-Videos',
            'description' => '视频素材 (5,872个文件)',
            'icon' => '🎥'
        ]
    ];
    
    public function listLibraries() {
        echo "=== NAS Billfish 资料库列表 ===\n\n";
        
        foreach ($this->libraries as $key => $lib) {
            echo "{$lib['icon']} {$key}: {$lib['name']}\n";
            echo "   {$lib['description']}\n";
            echo "   路径: {$lib['path']}\n\n";
        }
    }
    
    public function switchLibrary($libraryKey) {
        if (!isset($this->libraries[$libraryKey])) {
            echo "❌ 资料库 '{$libraryKey}' 不存在\n";
            echo "可用的资料库: " . implode(', ', array_keys($this->libraries)) . "\n";
            return false;
        }
        
        $library = $this->libraries[$libraryKey];
        $path = $library['path'];
        
        echo "🔄 切换到: {$library['icon']} {$library['name']}\n";
        echo "路径: {$path}\n";
        echo str_repeat("-", 50) . "\n";
        
        // 验证路径
        if (!$this->validateLibrary($path)) {
            return false;
        }
        
        // 更新配置
        if (!$this->updateConfig($path)) {
            return false;
        }
        
        echo "✅ 切换成功！\n";
        echo "🌐 访问地址: http://localhost:8000/\n";
        
        return true;
    }
    
    private function validateLibrary($path) {
        echo "🔍 验证资料库...\n";
        
        if (!is_dir($path)) {
            echo "❌ 路径不存在: {$path}\n";
            return false;
        }
        
        $dbPath = $path . '/.bf/billfish.db';
        if (!file_exists($dbPath)) {
            echo "❌ 数据库文件不存在\n";
            return false;
        }
        
        try {
            $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
            $fileCount = $db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
            $db->close();
            echo "✅ 数据库验证通过 ({$fileCount} 个文件)\n";
        } catch (Exception $e) {
            echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    private function updateConfig($newPath) {
        $configFile = 'config.php';
        
        if (!file_exists($configFile)) {
            echo "❌ config.php 不存在\n";
            return false;
        }
        
        // 备份配置
        $backupFile = 'config.php.backup.' . date('Y-m-d-H-i-s');
        copy($configFile, $backupFile);
        echo "📁 配置已备份: {$backupFile}\n";
        
        // 读取并更新配置
        $content = file_get_contents($configFile);
        $newPath = str_replace('\\', '\\\\', $newPath);
        $pattern = "/define\('BILLFISH_PATH',\s*'[^']*'\);/";
        $replacement = "define('BILLFISH_PATH', '{$newPath}');";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent === null || $newContent === $content) {
            echo "❌ 配置更新失败\n";
            return false;
        }
        
        if (file_put_contents($configFile, $newContent) === false) {
            echo "❌ 无法写入配置文件\n";
            return false;
        }
        
        echo "✅ 配置更新成功\n";
        return true;
    }
    
    public function generateVPSConfig() {
        echo "=== VPS 部署配置 ===\n\n";
        
        echo "🐧 Linux VPS 挂载脚本:\n";
        echo "```bash\n";
        echo "# 1. 安装CIFS工具\n";
        echo "sudo apt-get update\n";
        echo "sudo apt-get install cifs-utils\n\n";
        
        echo "# 2. 创建挂载点\n";
        echo "sudo mkdir -p /mnt/nas/billfish\n\n";
        
        echo "# 3. 挂载NAS (替换your-nas-ip和认证信息)\n";
        echo "sudo mount -t cifs //your-nas-ip/OneDrive-irm/Bill-Eagle /mnt/nas/billfish \\\n";
        echo "  -o username=your-username,password=your-password,uid=www-data,gid=www-data,iocharset=utf8\n\n";
        
        echo "# 4. 设置开机自动挂载\n";
        echo "echo '//your-nas-ip/OneDrive-irm/Bill-Eagle /mnt/nas/billfish cifs username=your-username,password=your-password,uid=www-data,gid=www-data,iocharset=utf8 0 0' | sudo tee -a /etc/fstab\n";
        echo "```\n\n";
        
        echo "📝 VPS config.php 配置:\n";
        echo "```php\n";
        foreach ($this->libraries as $key => $lib) {
            $linuxPath = '/mnt/nas/billfish/' . basename($lib['path']);
            echo "// {$lib['name']}\n";
            echo "define('BILLFISH_PATH', '{$linuxPath}');\n\n";
        }
        echo "```\n\n";
        
        echo "🔧 Nginx 配置示例:\n";
        echo "```nginx\n";
        echo "server {\n";
        echo "    listen 80;\n";
        echo "    server_name your-domain.com;\n";
        echo "    root /var/www/billfish-web-manager;\n";
        echo "    index index.php;\n\n";
        
        echo "    location / {\n";
        echo "        try_files \$uri \$uri/ =404;\n";
        echo "    }\n\n";
        
        echo "    location ~ \.php\$ {\n";
        echo "        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;\n";
        echo "        fastcgi_index index.php;\n";
        echo "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
        echo "        include fastcgi_params;\n";
        echo "    }\n";
        echo "}\n";
        echo "```\n\n";
        
        echo "🔐 安全建议:\n";
        echo "- 使用VPN保护NAS访问\n";
        echo "- 配置防火墙限制访问\n";
        echo "- 使用HTTPS加密传输\n";
        echo "- 定期更新系统和软件\n";
    }
}

// 主程序
$manager = new NASLibraryManager();

if ($argc < 2) {
    echo "NAS Billfish 多库管理工具\n\n";
    echo "用法:\n";
    echo "  php nas-manager.php list                 # 列出所有资料库\n";
    echo "  php nas-manager.php switch <library>     # 切换资料库\n";
    echo "  php nas-manager.php vps                  # 生成VPS配置\n\n";
    
    $manager->listLibraries();
    
    echo "快速切换示例:\n";
    echo "  php nas-manager.php switch material      # 切换到主素材库\n";
    echo "  php nas-manager.php switch tutorials     # 切换到教程库\n";
    echo "  php nas-manager.php switch storyboard    # 切换到分镜库\n";
    
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'list':
        $manager->listLibraries();
        break;
        
    case 'switch':
        if ($argc < 3) {
            echo "❌ 请指定要切换的资料库\n";
            echo "用法: php nas-manager.php switch <library>\n";
            exit(1);
        }
        $library = $argv[2];
        $manager->switchLibrary($library);
        break;
        
    case 'vps':
        $manager->generateVPSConfig();
        break;
        
    default:
        echo "❌ 未知命令: {$command}\n";
        echo "可用命令: list, switch, vps\n";
        exit(1);
}
?>