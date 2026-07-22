# FFmpeg 手动安装指南（5分钟完成）

## 方案一：快速下载安装（推荐）

### 1. 下载FFmpeg
访问以下任一地址下载：
- **官方构建**：https://www.gyan.dev/ffmpeg/builds/
  - 点击下载：`ffmpeg-release-essentials.zip`（约80MB）
- **GitHub镜像**：https://github.com/BtbN/FFmpeg-Builds/releases
  - 下载：`ffmpeg-master-latest-win64-gpl.zip`

### 2. 解压文件
- 下载完成后，解压到任意位置
- 假设解压后目录为：`D:\Downloads\ffmpeg-6.0-essentials_build`

### 3. 移动到固定位置（可选但推荐）
```
将解压后的文件夹移动/重命名为：C:\ffmpeg
确保目录结构为：
C:\ffmpeg\
  └── bin\
      ├── ffmpeg.exe
      ├── ffprobe.exe
      └── ffplay.exe
```

### 4. 添加到系统PATH
**方法A - PowerShell命令（推荐，无需管理员权限）**
```powershell
# 添加到当前用户PATH（不需要管理员权限）
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
$ffmpegPath = "C:\ffmpeg\bin"
if ($userPath -notlike "*$ffmpegPath*") {
    [Environment]::SetEnvironmentVariable("Path", "$userPath;$ffmpegPath", "User")
}
# 刷新当前会话
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "User") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "Machine")
```

**方法B - 图形界面**
1. 按 `Win + Pause` 打开"系统"
2. 点击"高级系统设置"
3. 点击"环境变量"
4. 在"用户变量"区域，双击"Path"
5. 点击"新建"，输入：`C:\ffmpeg\bin`
6. 点击"确定"保存

### 5. 验证安装
重新打开PowerShell窗口，运行：
```powershell
ffmpeg -version
ffprobe -version
```

如果显示版本信息，说明安装成功！

---

## 方案二：使用Chocolatey（开发者推荐）

如果你安装了Chocolatey包管理器：
```powershell
# 以管理员权限运行
choco install ffmpeg
```

---

## 方案三：使用Scoop（推荐给开发者）

如果你安装了Scoop包管理器：
```powershell
scoop install ffmpeg
```

---

## 快速测试命令

安装完成后，在项目目录运行：

```bash
# 测试FFmpeg是否可用
php -r "echo shell_exec('ffmpeg -version');"

# 测试缩略图生成
php test-thumbnail.php

# 批量重新生成所有视频缩略图
php regenerate-thumbnails.php
```

---

## 常见问题

### Q: 执行策略错误
A: 运行以下命令允许脚本执行：
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Q: 权限不足
A: 使用"方法A"添加到用户PATH，不需要管理员权限

### Q: 命令找不到
A: 
1. 确认 `C:\ffmpeg\bin` 存在且包含 `ffmpeg.exe`
2. 重启PowerShell窗口
3. 运行 `$env:Path` 检查PATH是否包含ffmpeg路径

### Q: 下载速度慢
A: 可以使用迅雷等下载工具下载zip文件

---

## 当前项目视频概况

- 视频总数：21个
- 分组数：5个（animation-clips, comic-anim, storyboard, test-ex, test-videos）
- 当前缩略图：GD库生成（灰色背景+播放图标）
- 目标：FFmpeg生成真实视频截图（25%位置智能提取）

安装FFmpeg后，所有缩略图将升级为真实视频画面！
