#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Billfish Database Export Tool
从 Billfish 数据库导出完整数据，建立准确的视频-预览图映射关系
"""

import sqlite3
import csv
import json
import os
import sys

# 设置路径
DB_PATH = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db'
OUTPUT_DIR = 'database-exports'

def main():
    print("=== Billfish Database Export Tool ===")
    print("")
    
    # 创建输出目录
    if not os.path.exists(OUTPUT_DIR):
        os.makedirs(OUTPUT_DIR)
        print(f"Created output directory: {OUTPUT_DIR}")
    
    # 连接数据库
    print("Connecting to database...")
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        print("Connected successfully!")
        print("")
    except Exception as e:
        print(f"Error connecting to database: {e}")
        return
    
    # 1. 导出材料表
    print("1. Exporting bf_material_v2...")
    cursor.execute("SELECT * FROM bf_material_v2")
    with open(f'{OUTPUT_DIR}/bf_material_v2.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 2. 导出文件表
    print("2. Exporting bf_file...")
    cursor.execute("SELECT * FROM bf_file")
    with open(f'{OUTPUT_DIR}/bf_file.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 3. 导出标签表
    print("3. Exporting bf_tag...")
    cursor.execute("SELECT * FROM bf_tag")
    with open(f'{OUTPUT_DIR}/bf_tag.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 4. 导出材料-标签关联（正确的表名是 bf_tag_join_file）
    print("4. Exporting bf_tag_join_file...")
    cursor.execute("SELECT * FROM bf_tag_join_file")
    with open(f'{OUTPUT_DIR}/bf_tag_join_file.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 4b. 导出标签 v2 表
    print("4b. Exporting bf_tag_v2...")
    cursor.execute("SELECT * FROM bf_tag_v2")
    with open(f'{OUTPUT_DIR}/bf_tag_v2.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 5. 导出用户数据
    print("5. Exporting bf_material_userdata...")
    cursor.execute("SELECT * FROM bf_material_userdata")
    with open(f'{OUTPUT_DIR}/bf_material_userdata.csv', 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow([desc[0] for desc in cursor.description])
        writer.writerows(cursor.fetchall())
    print("   Done!")
    
    # 6. 生成准确的视频-预览图映射（核心）
    print("6. Generating accurate video-preview mapping...")
    cursor.execute("""
        SELECT 
            f.id as file_id,
            f.name as file_name,
            f.file_size,
            f.ctime,
            f.mtime,
            f.tid as file_tid,
            m.thumb_tid,
            m.image_tid,
            m.w,
            m.h,
            v.duration
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        LEFT JOIN bf_material_video v ON f.id = v.file_id
        WHERE m.thumb_tid IS NOT NULL AND m.thumb_tid > 0
    """)
    
    mapping_data = {}
    rows = cursor.fetchall()
    for row in rows:
        file_id, file_name, file_size, ctime, mtime, file_tid, thumb_tid, image_tid, w, h, duration = row
        
        if not file_name or not thumb_tid:
            continue
        
        # 生成预览图路径（使用 thumb_tid）
        tid_hex = hex(thumb_tid)[2:]  # Remove 0x prefix
        folder = tid_hex.zfill(2)[:2]
        preview_path = f"\\.bf\\.preview\\{folder}\\{thumb_tid}.small.webp"
        
        # 提取文件扩展名
        ext = os.path.splitext(file_name)[1].lower()
        
        mapping_data[file_name] = {
            'file_id': file_id,
            'name': file_name,
            'thumb_tid': thumb_tid,
            'image_tid': image_tid,
            'preview_path': preview_path,
            'ext': ext,
            'size': file_size,
            'width': w,
            'height': h,
            'duration': duration,
            'ctime': ctime,
            'mtime': mtime
        }
    
    with open(f'{OUTPUT_DIR}/accurate_mapping.json', 'w', encoding='utf-8') as f:
        json.dump(mapping_data, f, ensure_ascii=False, indent=2)
    
    print(f"   Done! Generated {len(mapping_data)} accurate mappings")
    
    # 7. 生成完整材料信息（包含标签和评分）
    print("7. Generating complete material info with tags and ratings...")
    cursor.execute("""
        SELECT 
            f.id,
            f.name,
            f.file_size,
            m.thumb_tid,
            m.image_tid,
            m.w,
            m.h,
            v.duration,
            u.score,
            u.note,
            u.origin,
            GROUP_CONCAT(t.name) as tags,
            GROUP_CONCAT(t.color) as tag_colors
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        LEFT JOIN bf_material_video v ON f.id = v.file_id
        LEFT JOIN bf_material_userdata u ON f.id = u.file_id
        LEFT JOIN bf_tag_join_file tj ON f.id = tj.file_id
        LEFT JOIN bf_tag_v2 t ON tj.tag_id = t.id
        GROUP BY f.id
    """)
    
    complete_info = {}
    for row in cursor.fetchall():
        file_id, name, file_size, thumb_tid, image_tid, w, h, duration, score, note, origin, tags, tag_colors = row
        
        if not name or not thumb_tid:
            continue
        
        tid_hex = hex(thumb_tid)[2:]
        folder = tid_hex.zfill(2)[:2]
        preview_path = f"\\.bf\\.preview\\{folder}\\{thumb_tid}.small.webp"
        
        # 提取文件扩展名
        ext = os.path.splitext(name)[1].lower()
        
        # 处理标签
        tag_list = []
        if tags:
            tag_names = tags.split(',')
            tag_color_list = tag_colors.split(',') if tag_colors else []
            for i, tag_name in enumerate(tag_names):
                tag_list.append({
                    'name': tag_name,
                    'color': int(tag_color_list[i]) if i < len(tag_color_list) and tag_color_list[i] else 0
                })
        
        complete_info[name] = {
            'id': file_id,
            'name': name,
            'thumb_tid': thumb_tid,
            'image_tid': image_tid,
            'preview_path': preview_path,
            'ext': ext,
            'size': file_size,
            'width': w,
            'height': h,
            'duration': duration,
            'score': score if score else 0,
            'note': note if note else '',
            'origin': origin if origin else '',
            'tags': tag_list
        }
    
    with open(f'{OUTPUT_DIR}/complete_material_info.json', 'w', encoding='utf-8') as f:
        json.dump(complete_info, f, ensure_ascii=False, indent=2)
    
    print(f"   Done! Generated {len(complete_info)} complete material records")
    
    # 8. 导出统计信息
    print("8. Generating statistics...")
    stats = {
        'total_materials': len(complete_info),
        'total_tags': 0,
        'materials_with_tags': 0,
        'materials_with_ratings': 0,
        'materials_with_annotations': 0
    }
    
    cursor.execute("SELECT COUNT(*) FROM bf_tag_v2")
    stats['total_tags'] = cursor.fetchone()[0]
    
    for info in complete_info.values():
        if info['tags']:
            stats['materials_with_tags'] += 1
        if info['score'] > 0:
            stats['materials_with_ratings'] += 1
        if info['note']:
            stats['materials_with_annotations'] += 1
    
    with open(f'{OUTPUT_DIR}/statistics.json', 'w', encoding='utf-8') as f:
        json.dump(stats, f, ensure_ascii=False, indent=2)
    
    print(f"   Done!")
    print("")
    print("=== Export Statistics ===")
    print(f"Total materials: {stats['total_materials']}")
    print(f"Total tags: {stats['total_tags']}")
    print(f"Materials with tags: {stats['materials_with_tags']}")
    print(f"Materials with ratings: {stats['materials_with_ratings']}")
    print(f"Materials with annotations: {stats['materials_with_annotations']}")
    
    conn.close()
    print("")
    print("=== Export Complete ===")
    print(f"All data saved to: {OUTPUT_DIR}/")

if __name__ == '__main__':
    main()