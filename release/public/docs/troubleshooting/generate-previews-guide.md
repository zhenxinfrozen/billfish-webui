# 批量生成 Billfish 预览图指�?

## 问题说明
当前�?**67 个文�?(34.7%)** 缺少预览�?主要集中�?
- `test-blender` 目录: 大部分文�?
- `test-ex` 目录: 8 个文�?
- `test-videos` 目录: 7 个文�?

## 原因
Billfish 采用**懒加载机�?*,只在以下情况生成预览�?
1. 用户在软件中浏览到该文件�?
2. 用户点击查看该文�?
3. 手动触发"重新生成缩略�?

## 解决方法

### 方法 1: �?Billfish 中逐个文件夹打开(推荐)
1. 打开 Billfish 软件
2. 导航到素材库 `rzxme-billfish`
3. 依次打开以下文件�?会自动生成预览图):
   - `test-blender` (最多缺�?
   - `test-ex`
   - `test-videos`
   - 其他文件�?如有需�?
4. 等待每个文件夹的缩略图加载完�?
5. 刷新网页查看效果

### 方法 2: 批量重新生成缩略�?
1. 打开 Billfish 软件
2. 选择素材�?`rzxme-billfish`
3. 右键点击 �?选择 **"重新生成缩略�?**
4. 等待批量生成完成(可能需要几分钟)
5. 运行以下命令更新映射:
   ```powershell
   cd "d:\VS CODE\rzxme-billfish\public"
   python generate_mapping_simple.py
   ```
6. 刷新网页查看效果

### 方法 3: 使用 Billfish API(高级)
如果 Billfish 提供 API,可以编写脚本自动触发预览图生成�?
(需要查�?Billfish 文档确认是否支持)

## 验证方法

### 1. 在终端运行统计命�?
```powershell
cd "d:\VS CODE\rzxme-billfish\public"
python -c "import json; data = json.load(open('database-exports/id_based_mapping.json', encoding='utf-8')); missing = [f for f in data.values() if not f['preview_exists']]; print(f'缺少预览�? {len(missing)}/{len(data)} ({len(missing)*100/len(data):.1f}%)')"
```

### 2. 检查预览图文件是否存在
```powershell
cd "d:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf"
Get-ChildItem -Recurse -Filter "*.small.webp" | Measure-Object | Select-Object Count
```

### 3. 在网页中验证
- 打开 http://localhost:8000/
- 查看"最近文�?是否有灰色占位图
- 浏览各个分类文件�?

## 预期结果
完成后应�?
- �?预览图覆盖率: 100% (193/193)
- �?首页无灰色占位图
- �?所有分类文件夹都有完整缩略�?

## 缺失文件列表
主要集中在以下目�?
- `test-blender/*.mp4` - �?52 个文�?
- `test-ex/*.mp4` - 8 个文�? 
- `test-videos/*.mp4` - 7 个文�?

## 注意事项
1. **不要手动创建预览图文�?* - 必须�?Billfish 生成,才能保证格式正确
2. **预览图生成需要时�?* - 视频文件越大,生成时间越长
3. **定期更新映射** - 生成预览图后,运行 `python generate_mapping_simple.py` 更新 JSON 映射文件
4. **Web 系统已正确实�?* - 映射准确�?100%,只是缺少预览图文�?

## 技术细�?
- 预览图路径格�? `.preview/{hex(file_id)}/{file_id}.small.webp`
- 映射规则: `preview_id = file_id` (已验�?100% 准确)
- 系统状�? �?代码无问�?仅需 Billfish 生成预览�?

