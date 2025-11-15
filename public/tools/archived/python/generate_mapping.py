"""
生成完整的,准确的视频-预览图映射
基于分析结果: 预览图路径 = .preview/{hex(file_id)}/{file_id}.{size}.webp
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
    """根据 file_id 计算 hex 文件夹名"""
    return format(file_id, '02x')

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
    
    # 获取文件夹映射 (id -> name)
    cursor.execute("SELECT id, name FROM bf_folder")
    folders = dict(cursor.fetchall())
    print(f"\n文件夹: {len(folders)} 个")
    for fid, fname in folders.items():
        print(f"  {fid}: {fname}")
    
    # 获取所有文件及其元数据
    cursor.execute("""
        SELECT 
            f.id,
            f.name,
            f.pid,
            m.w,
            m.h,
            f.file_size
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        ORDER BY f.pid, f.name
    """)
    
    files = cursor.fetchall()
    print(f"\n文件: {len(files)} 个")
    
    # 获取视频时长
    cursor.execute("""
        SELECT file_id, duration
        FROM bf_material_video
    """)
    durations = dict(cursor.fetchall())
    
    # 获取评分和备注
    cursor.execute("""
        SELECT file_id, score, note
        FROM bf_material_userdata
    """)
    userdata = {}
    for file_id, score, note in cursor.fetchall():
        userdata[file_id] = {'score': score or 0, 'note': note or ''}
    
    # 获取标签
    cursor.execute("""
        SELECT t.id, t.name, t.color
        FROM bf_tag_v2 t
    """)
    tags_dict = {tid: {'name': name, 'color': color} for tid, name, color in cursor.fetchall()}
    
    cursor.execute("""
        SELECT file_id, tag_id
        FROM bf_tag_join_file
    """)
    file_tags = {}
    for file_id, tag_id in cursor.fetchall():
        if file_id not in file_tags:
            file_tags[file_id] = []
        if tag_id in tags_dict:
            file_tags[file_id].append(tags_dict[tag_id])
    
    # 构建完整映射
    mapping = {}
    complete_info = {}
    
    for file_id, name, folder_id, width, height, file_size in files:
        # 获取文件夹名
        folder_name = folders.get(folder_id, 'unknown')
        
        # 视频路径 (相对于 VIDEO_DIR)
        video_path = f"/{folder_name}/{name}"
        
        # 预览图路径 (相对于 BF_DIR)
        preview_path = get_preview_path(file_id, 'small')
        
        # 完整预览图路径验证 (使用 Windows 路径分隔符)
        preview_path_windows = preview_path.replace('/', '\\')
        full_preview_path = Path(BF_DIR) / preview_path_windows.lstrip('\\')
        preview_exists = full_preview_path.exists()
        
        # 时长
        duration = durations.get(file_id, 0)
        
        # 用户数据
        user_info = userdata.get(file_id, {'score': 0, 'note': ''})
        
        # 标签
        tags = file_tags.get(file_id, [])
        
        mapping[video_path] = {
            'file_id': file_id,
            'video_id': file_id,  # 保持兼容性
            'preview_id': file_id,  # 预览图 ID = 文件 ID
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
            'score': user_info['score'],
            'note': user_info['note'],
            'tags': tags
        }
        
        # 按文件名索引的完整信息
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
    
    print(f"\n✓ 映射文件已保存: {mapping_file}")
    print(f"  总计: {len(mapping)} 个映射")
    
    # 保存完整信息
    info_file = os.path.join(OUTPUT_DIR, 'complete_material_info.json')
    with open(info_file, 'w', encoding='utf-8') as f:
        json.dump(complete_info, f, ensure_ascii=False, indent=2)
    
    print(f"\n✓ 完整信息已保存: {info_file}")
    print(f"  总计: {len(complete_info)} 个文件")
    
    # 验证
    print("\n"+"="*80)
    print("映射验证")
    print("="*80)
    
    exists_count = sum(1 for v in mapping.values() if v['preview_exists'])
    print(f"\n预览图存在: {exists_count}/{len(mapping)} = {exists_count/len(mapping)*100:.1f}%")
    
    with_tags = sum(1 for v in mapping.values() if v['tags'])
    with_score = sum(1 for v in mapping.values() if v['score'] > 0)
    with_note = sum(1 for v in mapping.values() if v['note'])
    
    print(f"带标签: {with_tags}")
    print(f"带评分: {with_score}")
    print(f"带备注: {with_note}")
    
    # 显示示例
    print("\n映射示例 (前5个):")
    for i, (path, info) in enumerate(list(mapping.items())[:5], 1):
        print(f"\n{i}. {path}")
        print(f"   文件ID: {info['file_id']}")
        print(f"   预览图: {info['preview_path']}")
        print(f"   十六进制文件夹: {info['preview_hex_folder']}")
        print(f"   预览图存在: {'✓' if info['preview_exists'] else '✗'}")
        if info['score']:
            print(f"   评分: {'⭐' * info['score']}")
        if info['tags']:
            print(f"   标签: {[t['name'] for t in info['tags']]}")
    
    conn.close()
    
    print("\n"+"="*80)
    print("完成!")
    print("="*80)
    print("\n现在可以测试 BillfishManagerV2.php")

if __name__ == "__main__":
    main()
