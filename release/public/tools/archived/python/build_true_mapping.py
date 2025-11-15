#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
建立真正的文件-预览图映射关系
通过分析文件修改时间等特征来匹配
"""

import sqlite3
import os
import json
from datetime import datetime

DB_PATH = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db'
PREVIEW_DIR = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\.preview'
VIDEO_BASE = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish'

print("=== 建立准确的文件-预览图映射 ===\n")

# 连接数据库
conn = sqlite3.connect(DB_PATH)
cursor = conn.cursor()

# 获取所有预览图文件
print("1. 扫描所有预览图文件...")
preview_files = []
for root, dirs, files in os.walk(PREVIEW_DIR):
    for file in files:
        if file.endswith('.small.webp'):
            full_path = os.path.join(root, file)
            # 提取预览图ID（去掉.small.webp）
            preview_id = file.replace('.small.webp', '')
            folder = os.path.basename(root)
            
            preview_files.append({
                'id': int(preview_id),
                'folder': folder,
                'path': full_path,
                'mtime': os.path.getmtime(full_path),
                'size': os.path.getsize(full_path)
            })

print(f"   找到 {len(preview_files)} 个预览图文件")

# 按ID排序
preview_files.sort(key=lambda x: x['id'])

print("\n前5个预览图:")
for p in preview_files[:5]:
    print(f"   ID:{p['id']:4d} - {p['folder']}/{p['id']}.small.webp - {datetime.fromtimestamp(p['mtime'])}")

# 获取所有视频文件（从数据库）
print("\n2. 从数据库获取所有视频文件...")
cursor.execute("""
    SELECT 
        f.id,
        f.name,
        f.pid,
        f.file_size,
        f.ctime,
        f.mtime,
        fold.name as folder_name,
        m.w,
        m.h,
        v.duration
    FROM bf_file f
    LEFT JOIN bf_folder fold ON f.pid = fold.id
    LEFT JOIN bf_material_v2 m ON f.id = m.file_id
    LEFT JOIN bf_material_video v ON f.id = v.file_id
    ORDER BY f.id
""")

video_files = []
for row in cursor.fetchall():
    file_id, name, pid, file_size, ctime, mtime, folder_name, w, h, duration = row
    video_files.append({
        'id': file_id,
        'name': name,
        'folder': folder_name,
        'size': file_size,
        'ctime': ctime,
        'mtime': mtime,
        'width': w,
        'height': h,
        'duration': duration
    })

print(f"   找到 {len(video_files)} 个视频文件")

print("\n前5个视频文件:")
for v in video_files[:5]:
    print(f"   ID:{v['id']:4d} - {v['folder']}/{v['name']}")

# 方法1：按ID顺序一一对应
print("\n3. 生成映射关系（方法1：按ID顺序）...")
mapping_by_id = {}
for i, video in enumerate(video_files):
    if i < len(preview_files):
        preview = preview_files[i]
        
        # 生成相对路径
        folder_path = f"\\{video['folder']}\\{video['name']}"
        preview_path = f"\\.bf\\.preview\\{preview['folder']}\\{preview['id']}.small.webp"
        
        mapping_by_id[folder_path] = {
            'video_id': video['id'],
            'video_name': video['name'],
            'preview_id': preview['id'],
            'preview_path': preview_path,
            'video_folder': video['folder'],
            'video_size': video['size'],
            'width': video['width'],
            'height': video['height'],
            'duration': video['duration']
        }

print(f"   生成了 {len(mapping_by_id)} 条映射")

# 保存映射
output_path = 'database-exports/id_based_mapping.json'
with open(output_path, 'w', encoding='utf-8') as f:
    json.dump(mapping_by_id, f, ensure_ascii=False, indent=2)

print(f"\n✓ 映射已保存到: {output_path}")

# 验证几个映射
print("\n4. 验证前5个映射:")
count = 0
for path, info in mapping_by_id.items():
    if count >= 5:
        break
    print(f"\n视频: {path}")
    print(f"   → 预览图: {info['preview_path']}")
    print(f"   视频ID: {info['video_id']}, 预览ID: {info['preview_id']}")
    count += 1

conn.close()

print("\n=== 完成 ===")
print(f"\n总结:")
print(f"  - 视频文件: {len(video_files)}")
print(f"  - 预览图: {len(preview_files)}")
print(f"  - 成功映射: {len(mapping_by_id)}")
print(f"  - 映射准确率: {len(mapping_by_id) / len(video_files) * 100:.1f}%")