"""
Billfish 数据库深度分析
目标: 完全理解数据库结构和映射逻辑
"""
import sqlite3
import json
import os
from pathlib import Path

# 数据库路径
DB_PATH = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db"
BF_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf"
VIDEO_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish"

def analyze_database():
    """完整分析数据库"""
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    print("=" * 80)
    print("BILLFISH 数据库深度分析报告")
    print("=" * 80)
    
    # 1. 获取所有表
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
    tables = [row[0] for row in cursor.fetchall()]
    print(f"\n📊 发现 {len(tables)} 个表")
    
    analysis_result = {
        'tables': {},
        'sample_records': {},
        'relationships': [],
        'preview_analysis': {}
    }
    
    # 2. 分析每个表的结构
    for table in tables:
        print(f"\n{'=' * 80}")
        print(f"📋 表: {table}")
        print(f"{'=' * 80}")
        
        # 获取表结构
        cursor.execute(f"PRAGMA table_info({table})")
        columns = cursor.fetchall()
        
        print("\n字段结构:")
        col_info = []
        for col in columns:
            col_dict = {
                'cid': col[0],
                'name': col[1],
                'type': col[2],
                'notnull': col[3],
                'default': col[4],
                'pk': col[5]
            }
            col_info.append(col_dict)
            pk_marker = " [PRIMARY KEY]" if col[5] else ""
            print(f"  {col[1]:30} {col[2]:15} {pk_marker}")
        
        # 获取索引
        cursor.execute(f"PRAGMA index_list({table})")
        indexes = cursor.fetchall()
        if indexes:
            print("\n索引:")
            for idx in indexes:
                print(f"  {idx[1]} (unique: {idx[2]})")
        
        # 获取记录数
        cursor.execute(f"SELECT COUNT(*) FROM {table}")
        count = cursor.fetchone()[0]
        print(f"\n记录数: {count}")
        
        # 获取前3条完整记录(如果有)
        if count > 0:
            cursor.execute(f"SELECT * FROM {table} LIMIT 3")
            sample_rows = cursor.fetchall()
            
            print("\n样本数据 (前3条):")
            col_names = [col[1] for col in columns]
            
            for i, row in enumerate(sample_rows, 1):
                print(f"\n  记录 {i}:")
                for col_name, value in zip(col_names, row):
                    # 截断过长的值
                    str_value = str(value)
                    if len(str_value) > 100:
                        str_value = str_value[:100] + "..."
                    print(f"    {col_name:30} = {str_value}")
            
            # 保存到结果
            analysis_result['sample_records'][table] = [
                dict(zip(col_names, row)) for row in sample_rows
            ]
        
        analysis_result['tables'][table] = {
            'columns': col_info,
            'indexes': [{'name': idx[1], 'unique': idx[2]} for idx in indexes],
            'count': count
        }
    
    # 3. 重点分析核心表的关联关系
    print("\n" + "=" * 80)
    print("🔍 核心表关联分析")
    print("=" * 80)
    
    # bf_file 表分析
    print("\n📁 bf_file 表详细分析:")
    cursor.execute("SELECT * FROM bf_file LIMIT 5")
    files = cursor.fetchall()
    cursor.execute("PRAGMA table_info(bf_file)")
    file_cols = [col[1] for col in cursor.fetchall()]
    
    print(f"字段: {', '.join(file_cols)}")
    for file in files:
        print(f"\n文件记录:")
        for col, val in zip(file_cols, file):
            print(f"  {col:20} = {val}")
    
    # bf_material_v2 表分析
    print("\n🎬 bf_material_v2 表详细分析:")
    cursor.execute("SELECT * FROM bf_material_v2 LIMIT 5")
    materials = cursor.fetchall()
    cursor.execute("PRAGMA table_info(bf_material_v2)")
    mat_cols = [col[1] for col in cursor.fetchall()]
    
    print(f"字段: {', '.join(mat_cols)}")
    for mat in materials:
        print(f"\n素材记录:")
        for col, val in zip(mat_cols, mat):
            print(f"  {col:20} = {val}")
    
    # 4. 分析预览图路径规律
    print("\n" + "=" * 80)
    print("🖼️  预览图路径分析")
    print("=" * 80)
    
    preview_dir = Path(BF_DIR) / ".preview"
    if preview_dir.exists():
        preview_files = list(preview_dir.rglob("*.webp"))
        print(f"\n找到 {len(preview_files)} 个预览图")
        
        # 分析预览图命名规律
        print("\n预览图命名样本:")
        for pf in preview_files[:10]:
            rel_path = pf.relative_to(preview_dir)
            parts = str(rel_path).split(os.sep)
            file_name = pf.stem.replace('.small', '').replace('.medium', '').replace('.large', '')
            print(f"  {rel_path}")
            print(f"    文件夹: {parts[0] if len(parts) > 1 else 'N/A'}")
            print(f"    文件ID: {file_name}")
            
            # 尝试在数据库中查找这个ID
            cursor.execute("""
                SELECT id, name FROM bf_file WHERE id = ?
            """, (file_name,))
            match = cursor.fetchone()
            if match:
                print(f"    ✓ 匹配到文件: {match[1]}")
            else:
                # 尝试其他字段
                cursor.execute("""
                    SELECT id, name FROM bf_file WHERE name LIKE ?
                """, (f"%{file_name}%",))
                match2 = cursor.fetchone()
                if match2:
                    print(f"    ~ 可能匹配: {match2[1]}")
        
        analysis_result['preview_analysis'] = {
            'total_previews': len(preview_files),
            'sample_paths': [str(pf.relative_to(preview_dir)) for pf in preview_files[:10]]
        }
    
    # 5. 查找可能的关联字段
    print("\n" + "=" * 80)
    print("🔗 表关联关系分析")
    print("=" * 80)
    
    # 查找 bf_file 和 bf_material_v2 的关联
    cursor.execute("SELECT id, name FROM bf_file LIMIT 1")
    sample_file = cursor.fetchone()
    if sample_file:
        file_id = sample_file[0]
        print(f"\n测试文件 ID: {file_id}")
        print(f"测试文件名: {sample_file[1]}")
        
        # 在 bf_material_v2 中查找
        cursor.execute("SELECT * FROM bf_material_v2 WHERE file_id = ?", (file_id,))
        mat = cursor.fetchone()
        if mat:
            print("✓ 在 bf_material_v2 中找到匹配记录")
            cursor.execute("PRAGMA table_info(bf_material_v2)")
            cols = [c[1] for c in cursor.fetchall()]
            for col, val in zip(cols, mat):
                print(f"  {col:20} = {val}")
    
    # 6. 保存完整分析结果
    output_file = "database-analysis-full.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        # 处理不可序列化的值
        def default_handler(obj):
            if isinstance(obj, bytes):
                return obj.hex()
            return str(obj)
        
        json.dump(analysis_result, f, ensure_ascii=False, indent=2, default=default_handler)
    
    print(f"\n\n✅ 完整分析结果已保存到: {output_file}")
    
    conn.close()

def find_preview_mapping_logic():
    """专门分析预览图映射逻辑"""
    print("\n" + "=" * 80)
    print("🔍 预览图映射逻辑专项分析")
    print("=" * 80)
    
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # 获取所有视频文件
    video_dir = Path(VIDEO_DIR)
    video_files = []
    for ext in ['*.mp4', '*.webm', '*.mkv', '*.avi', '*.mov']:
        video_files.extend(video_dir.rglob(ext))
    
    print(f"\n找到 {len(video_files)} 个视频文件")
    
    # 获取所有预览图
    preview_dir = Path(BF_DIR) / ".preview"
    preview_files = list(preview_dir.rglob("*.webp"))
    print(f"找到 {len(preview_files)} 个预览图")
    
    # 分析前10个视频的映射
    print("\n详细映射分析 (前10个视频):")
    for i, video_path in enumerate(video_files[:10], 1):
        print(f"\n--- 视频 {i} ---")
        print(f"路径: {video_path.relative_to(video_dir)}")
        print(f"文件名: {video_path.name}")
        
        # 在数据库中查找
        cursor.execute("""
            SELECT id, name, pid, tid 
            FROM bf_file 
            WHERE name = ?
        """, (video_path.name,))
        
        file_record = cursor.fetchone()
        if file_record:
            file_id, name, pid, tid = file_record
            print(f"✓ 数据库记录:")
            print(f"  id: {file_id}")
            print(f"  name: {name}")
            print(f"  pid (folder_id): {pid}")
            print(f"  tid (type_id): {tid}")
            
            # 查找对应的 material 记录
            cursor.execute("""
                SELECT file_id, thumb_tid, image_tid 
                FROM bf_material_v2 
                WHERE file_id = ?
            """, (file_id,))
            
            mat_record = cursor.fetchone()
            if mat_record:
                mat_file_id, thumb_tid, image_tid = mat_record
                print(f"✓ Material 记录:")
                print(f"  file_id: {mat_file_id}")
                print(f"  thumb_tid: {thumb_tid}")
                print(f"  image_tid: {image_tid}")
                
                # 🔍 关键发现: thumb_tid 和 image_tid 是什么?
                # 尝试查找预览图 - 使用 file_id
                possible_preview_paths = [
                    preview_dir / f"{file_id}.small.webp",
                    preview_dir / f"{file_id}.medium.webp",
                    preview_dir / f"{file_id}.large.webp",
                    preview_dir / f"{file_id}.hd.webp",
                ]
                
                # 也尝试在子目录中查找
                for folder in preview_dir.iterdir():
                    if folder.is_dir():
                        possible_preview_paths.extend([
                            folder / f"{file_id}.small.webp",
                            folder / f"{file_id}.medium.webp",
                            folder / f"{file_id}.large.webp",
                            folder / f"{file_id}.hd.webp",
                        ])
                
                found_preview = None
                for pp in possible_preview_paths:
                    if pp.exists():
                        found_preview = pp
                        break
                
                if found_preview:
                    print(f"✓ 找到预览图: {found_preview.relative_to(preview_dir)}")
                else:
                    print(f"✗ 未找到预览图 (file_id: {file_id})")
                    print(f"  尝试过的路径:")
                    for pp in possible_preview_paths[:5]:
                        print(f"    {pp.relative_to(preview_dir) if pp.exists() else pp.name}")
            else:
                print(f"✗ 未找到 Material 记录")
        else:
            print(f"✗ 未在数据库中找到")
    
    conn.close()

if __name__ == "__main__":
    analyze_database()
    find_preview_mapping_logic()
