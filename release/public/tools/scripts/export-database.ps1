# Billfish 数据库导出脚本 (PowerShell)
# 使用 Python 的 sqlite3 模块导出数据

Write-Host "=== Billfish 数据库数据导出 ===" -ForegroundColor Green
Write-Host ""

$dbPath = "..\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db"
$outputDir = "database-exports"

if (!(Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

# 创建 Python 脚本来读取数据库
$pythonScript = @'
import sqlite3
import csv
import json
import sys
import os

db_path = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db'
output_dir = 'database-exports'

print("正在连接数据库...")
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# 1. 导出材料表
print("1. 导出 bf_material_v2 表...")
cursor.execute("SELECT * FROM bf_material_v2")
with open(f'{output_dir}/bf_material_v2.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow([description[0] for description in cursor.description])
    writer.writerows(cursor.fetchall())

# 2. 导出文件表
print("2. 导出 bf_file 表...")
cursor.execute("SELECT * FROM bf_file")
with open(f'{output_dir}/bf_file.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow([description[0] for description in cursor.description])
    writer.writerows(cursor.fetchall())

# 3. 导出标签表
print("3. 导出 bf_tag 表...")
cursor.execute("SELECT * FROM bf_tag")
with open(f'{output_dir}/bf_tag.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow([description[0] for description in cursor.description])
    writer.writerows(cursor.fetchall())

# 4. 导出材料-标签关联
print("4. 导出 bf_material_tag 表...")
cursor.execute("SELECT * FROM bf_material_tag")
with open(f'{output_dir}/bf_material_tag.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow([description[0] for description in cursor.description])
    writer.writerows(cursor.fetchall())

# 5. 导出用户数据
print("5. 导出 bf_material_userdata 表...")
cursor.execute("SELECT * FROM bf_material_userdata")
with open(f'{output_dir}/bf_material_userdata.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow([description[0] for description in cursor.description])
    writer.writerows(cursor.fetchall())

# 6. 生成完整映射（最关键）
print("6. 生成视频-预览图映射...")
cursor.execute("""
    SELECT 
        m.id as material_id,
        m.name as material_name,
        m.preview_tid,
        m.ext,
        m.size,
        m.width,
        m.height,
        m.duration,
        f.path as file_path,
        f.name as file_name
    FROM bf_material_v2 m
    LEFT JOIN bf_file f ON m.id = f.id
    WHERE m.preview_tid IS NOT NULL AND m.preview_tid > 0
""")

mapping_data = {}
rows = cursor.fetchall()
for row in rows:
    material_id, material_name, preview_tid, ext, size, width, height, duration, file_path, file_name = row
    
    # 生成预览图路径
    tid_hex = hex(preview_tid)[2:]  # 去掉 0x
    folder = tid_hex.zfill(2)[:2]
    preview_path = f"\\.bf\\.preview\\{folder}\\{preview_tid}.small.webp"
    
    if file_path:
        mapping_data[file_path] = {
            'material_id': material_id,
            'name': material_name,
            'preview_tid': preview_tid,
            'preview_path': preview_path,
            'ext': ext,
            'size': size,
            'width': width,
            'height': height,
            'duration': duration
        }

# 保存映射
with open(f'{output_dir}/accurate_mapping.json', 'w', encoding='utf-8') as f:
    json.dump(mapping_data, f, ensure_ascii=False, indent=2)

print(f"\n✓ 成功生成 {len(mapping_data)} 条准确映射")

# 7. 获取带标签和评分的完整信息
print("7. 生成完整材料信息（包含标签和评分）...")
cursor.execute("""
    SELECT 
        m.id,
        m.name,
        m.preview_tid,
        f.path,
        mu.star,
        mu.annotation,
        GROUP_CONCAT(t.name) as tags
    FROM bf_material_v2 m
    LEFT JOIN bf_file f ON m.id = f.id
    LEFT JOIN bf_material_userdata mu ON m.id = mu.material_id
    LEFT JOIN bf_material_tag mt ON m.id = mt.material_id
    LEFT JOIN bf_tag t ON mt.tag_id = t.id
    GROUP BY m.id
""")

complete_info = {}
for row in cursor.fetchall():
    material_id, name, preview_tid, path, star, annotation, tags = row
    if path and preview_tid:
        tid_hex = hex(preview_tid)[2:]
        folder = tid_hex.zfill(2)[:2]
        preview_path = f"\\.bf\\.preview\\{folder}\\{preview_tid}.small.webp"
        
        complete_info[path] = {
            'id': material_id,
            'name': name,
            'preview_tid': preview_tid,
            'preview_path': preview_path,
            'star': star if star else 0,
            'annotation': annotation if annotation else '',
            'tags': tags.split(',') if tags else []
        }

with open(f'{output_dir}/complete_material_info.json', 'w', encoding='utf-8') as f:
    json.dump(complete_info, f, ensure_ascii=False, indent=2)

print(f"✓ 成功生成 {len(complete_info)} 条完整材料信息")

conn.close()
print("\n=== 导出完成 ===")
'@

# 保存 Python 脚本
$pythonScript | Out-File -FilePath "temp_export.py" -Encoding UTF8

# 执行 Python 脚本
Write-Host "正在使用 Python 导出数据..." -ForegroundColor Yellow
python temp_export.py

# 清理临时文件
Remove-Item "temp_export.py" -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "=== 导出完成 ===" -ForegroundColor Green
Write-Host "数据已保存到 $outputDir 目录"
Write-Host ""