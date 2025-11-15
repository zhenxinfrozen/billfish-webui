"""
生成完整的视频-预览图映射 (简化版,无特殊字符)
"""
import sqlite3
import json
import os
from pathlib import Path

# 数据库路径
DB_PATH = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db"
BF_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf"
VIDEO_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish"
OUTPUT_DIR = "database-exports"

def get_hex_folder(file_id):
    """根据 file_id 计算 hex 文件夹名 (Billfish 使用后两位)"""
    # Billfish 使用 file_id 十六进制的后两位作为文件夹名
    # 例如: 256 -> 0x100 -> 后两位 "00"
    hex_str = format(file_id, 'x')  # 转十六进制
    return hex_str[-2:].zfill(2)    # 取后两位,不足补0

def get_preview_path(file_id, size='small'):
    """生成预览图路径 (相对于 BF_DIR)"""
    hex_folder = get_hex_folder(file_id)
    return f".preview/{hex_folder}/{file_id}.{size}.webp"

def main():
    print("="*80)
    print("生成完整映射数据")
    print("="*80)
    
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # 获取文件夹映射
    cursor.execute("SELECT id, name FROM bf_folder")
    folders = dict(cursor.fetchall())
    print(f"\n文件夹数: {len(folders)}")
    
    # 获取所有文件
    cursor.execute("""
        SELECT 
            f.id, f.name, f.pid, m.w, m.h, f.file_size, f.mtime
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        ORDER BY f.pid, f.name
    """)
    files = cursor.fetchall()
    print(f"文件数: {len(files)}")
    
    # 获取视频时长
    cursor.execute("SELECT file_id, duration FROM bf_material_video")
    durations = dict(cursor.fetchall())
    
    # 获取评分和备注
    cursor.execute("SELECT file_id, score, note FROM bf_material_userdata")
    userdata = {}
    for file_id, score, note in cursor.fetchall():
        userdata[file_id] = {'score': score or 0, 'note': note or ''}
    
    # 获取标签
    cursor.execute("SELECT t.id, t.name, t.color FROM bf_tag_v2 t")
    tags_dict = {tid: {'name': name, 'color': color} for tid, name, color in cursor.fetchall()}
    
    cursor.execute("SELECT file_id, tag_id FROM bf_tag_join_file")
    file_tags = {}
    for file_id, tag_id in cursor.fetchall():
        if file_id not in file_tags:
            file_tags[file_id] = []
        if tag_id in tags_dict:
            file_tags[file_id].append(tags_dict[tag_id])
    
    # 构建完整映射
    mapping = {}
    complete_info = {}
    preview_exists_count = 0
    
    for file_id, name, folder_id, width, height, file_size, mtime in files:
        folder_name = folders.get(folder_id, 'unknown')
        video_path = f"/{folder_name}/{name}"
        preview_path = get_preview_path(file_id, 'small')
        
        # 验证预览图是否存在
        preview_path_windows = preview_path.replace('/', os.sep)
        full_preview_path = Path(BF_DIR) / preview_path_windows
        preview_exists = full_preview_path.exists()
        if preview_exists:
            preview_exists_count += 1
        
        duration = durations.get(file_id, 0)
        user_info = userdata.get(file_id, {'score': 0, 'note': ''})
        tags = file_tags.get(file_id, [])
        
        mapping[video_path] = {
            'file_id': file_id,
            'video_id': file_id,
            'preview_id': file_id,
            'video_name': name,
            'video_folder': folder_name,
            'folder_id': folder_id,
            'preview_path': preview_path,
            'preview_exists': preview_exists,
            'preview_hex_folder': get_hex_folder(file_id),
            'video_size': file_size or 0,
            'width': width or 0,
            'height': height or 0,
            'duration': duration,
            'modified': mtime or 0,  # 添加修改时间
            'score': user_info['score'],
            'note': user_info['note'],
            'tags': tags
        }
        
        complete_info[name] = {
            'file_id': file_id,
            'folder': folder_name,
            'size': file_size or 0,
            'width': width or 0,
            'height': height or 0,
            'duration': duration,
            'score': user_info['score'],
            'note': user_info['note'],
            'tags': tags,
            'preview_path': preview_path
        }
    
    # 保存映射
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    mapping_file = os.path.join(OUTPUT_DIR, 'id_based_mapping.json')
    with open(mapping_file, 'w', encoding='utf-8') as f:
        json.dump(mapping, f, ensure_ascii=False, indent=2)
    
    print(f"\n[OK] 映射文件已保存: {mapping_file}")
    print(f"     总计: {len(mapping)} 个映射")
    
    info_file = os.path.join(OUTPUT_DIR, 'complete_material_info.json')
    with open(info_file, 'w', encoding='utf-8') as f:
        json.dump(complete_info, f, ensure_ascii=False, indent=2)
    
    print(f"\n[OK] 完整信息已保存: {info_file}")
    print(f"     总计: {len(complete_info)} 个文件")
    
    # 验证
    print("\n"+"="*80)
    print("映射验证")
    print("="*80)
    
    match_rate = preview_exists_count/len(mapping)*100 if mapping else 0
    print(f"\n预览图存在: {preview_exists_count}/{len(mapping)} = {match_rate:.1f}%")
    
    with_tags = sum(1 for v in mapping.values() if v['tags'])
    with_score = sum(1 for v in mapping.values() if v['score'] > 0)
    with_note = sum(1 for v in mapping.values() if v['note'])
    
    print(f"带标签: {with_tags}")
    print(f"带评分: {with_score}")
    print(f"带备注: {with_note}")
    
    # 显示示例
    print("\n映射示例 (前3个):")
    for i, (path, info) in enumerate(list(mapping.items())[:3], 1):
        print(f"\n{i}. {path}")
        print(f"   文件ID: {info['file_id']}")
        print(f"   预览图: {info['preview_path']}")
        print(f"   十六进制文件夹: {info['preview_hex_folder']}")
        print(f"   预览图存在: {info['preview_exists']}")
        if info['score']:
            print(f"   评分: {info['score']} 星")
        if info['tags']:
            print(f"   标签: {[t['name'] for t in info['tags']]}")
    
    conn.close()
    
    print("\n"+"="*80)
    print("完成! 现在可以测试 BillfishManagerV2.php")
    print("="*80)

if __name__ == "__main__":
    main()
