"""
Billfish 预览图映射逻辑分析
专注于找出预览图和视频的真实映射关系
"""
import sqlite3
import json
import os
from pathlib import Path

# 数据库路径
DB_PATH = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db"
BF_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf"
VIDEO_DIR = r"D:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish"

def main():
    print("="*80)
    print("BILLFISH 预览图映射分析")
    print("="*80)
    
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # 1. 分析预览图文件结构
    preview_dir = Path(BF_DIR) / ".preview"
    if not preview_dir.exists():
        print("错误: 预览图目录不存在")
        return
    
    preview_files = list(preview_dir.rglob("*.webp"))
    print(f"\n找到 {len(preview_files)} 个预览图文件")
    
    # 分析预览图命名规律
    print("\n预览图命名分析 (前20个):")
    preview_mapping = {}
    
    for pf in preview_files[:20]:
        rel_path = pf.relative_to(preview_dir)
        parts = str(rel_path).split(os.sep)
        
        # 提取文件名中的ID (去掉 .small/.medium/.large/.hd 和 .webp)
        file_name = pf.stem  # 例如: "256.hd" 或 "256.small"
        
        # 提取纯数字ID
        if '.' in file_name:
            file_id = file_name.split('.')[0]  # 例如: "256"
        else:
            file_id = file_name
        
        print(f"\n路径: {rel_path}")
        print(f"  文件夹: {parts[0] if len(parts) > 1 else '根目录'}")
        print(f"  提取的ID: {file_id}")
        
        # 在数据库中查找这个ID
        try:
            cursor.execute("SELECT id, name, pid FROM bf_file WHERE id = ?", (int(file_id),))
            match = cursor.fetchone()
            
            if match:
                print(f"  ✓ 匹配文件: {match[1]} (ID: {match[0]}, 文件夹ID: {match[2]})")
                
                # 保存映射
                if file_id not in preview_mapping:
                    preview_mapping[file_id] = {
                        'file_id': match[0],
                        'file_name': match[1],
                        'folder_id': match[2],
                        'preview_files': []
                    }
                
                preview_mapping[file_id]['preview_files'].append(str(rel_path))
            else:
                print(f"  ✗ 未找到匹配")
        except ValueError:
            print(f"  ✗ ID格式错误: {file_id}")
    
    # 2. 验证所有预览图
    print("\n"+"="*80)
    print("验证所有预览图映射")
    print("="*80)
    
    all_mapping = {}
    matched = 0
    unmatched = 0
    
    for pf in preview_files:
        file_name = pf.stem
        if '.' in file_name:
            file_id = file_name.split('.')[0]
        else:
            file_id = file_name
        
        try:
            file_id_int = int(file_id)
            cursor.execute("SELECT id, name FROM bf_file WHERE id = ?", (file_id_int,))
            match = cursor.fetchone()
            
            if match:
                matched += 1
                if file_id not in all_mapping:
                    all_mapping[file_id] = {
                        'file_id': match[0],
                        'file_name': match[1],
                        'preview_files': []
                    }
                all_mapping[file_id]['preview_files'].append(str(pf.relative_to(preview_dir)))
            else:
                unmatched += 1
        except ValueError:
            unmatched += 1
    
    print(f"\n匹配成功: {matched} 个")
    print(f"匹配失败: {unmatched} 个")
    print(f"匹配率: {matched/(matched+unmatched)*100:.2f}%")
    
    # 3. 详细分析前10个视频文件
    print("\n"+"="*80)
    print("视频文件映射验证 (前10个)")
    print("="*80)
    
    video_dir = Path(VIDEO_DIR)
    video_files = []
    for ext in ['*.mp4', '*.webm', '*.mkv']:
        video_files.extend(video_dir.rglob(ext))
    
    print(f"\n找到 {len(video_files)} 个视频文件")
    
    mapping_results = []
    
    for i, vf in enumerate(video_files[:10], 1):
        print(f"\n--- 视频 {i} ---")
        print(f"文件: {vf.name}")
        print(f"路径: {vf.relative_to(video_dir)}")
        
        # 在数据库中查找
        cursor.execute("SELECT id, pid FROM bf_file WHERE name = ?", (vf.name,))
        file_record = cursor.fetchone()
        
        if file_record:
            file_id, folder_id = file_record
            print(f"✓ 数据库 ID: {file_id}")
            print(f"  文件夹 ID: {folder_id}")
            
            # 查找预览图
            found_previews = []
            for pf in preview_files:
                file_name = pf.stem.split('.')[0]
                try:
                    if int(file_name) == file_id:
                        found_previews.append(pf.relative_to(preview_dir))
                except ValueError:
                    continue
            
            if found_previews:
                print(f"✓ 找到 {len(found_previews)} 个预览图:")
                for fp in found_previews:
                    print(f"    {fp}")
                
                mapping_results.append({
                    'video_file': str(vf.relative_to(video_dir)),
                    'video_name': vf.name,
                    'file_id': file_id,
                    'folder_id': folder_id,
                    'previews': [str(fp) for fp in found_previews],
                    'status': 'success'
                })
            else:
                print(f"✗ 未找到预览图")
                mapping_results.append({
                    'video_file': str(vf.relative_to(video_dir)),
                    'video_name': vf.name,
                    'file_id': file_id,
                    'folder_id': folder_id,
                    'previews': [],
                    'status': 'no_preview'
                })
        else:
            print(f"✗ 未在数据库中找到")
            mapping_results.append({
                'video_file': str(vf.relative_to(video_dir)),
                'video_name': vf.name,
                'status': 'not_in_db'
            })
    
    # 4. 保存结果
    output = {
        'preview_stats': {
            'total_previews': len(preview_files),
            'matched': matched,
            'unmatched': unmatched,
            'match_rate': f"{matched/(matched+unmatched)*100:.2f}%"
        },
        'video_stats': {
            'total_videos': len(video_files),
            'analyzed': len(mapping_results)
        },
        'sample_mappings': mapping_results,
        'all_preview_mapping': all_mapping
    }
    
    output_file = "preview_mapping_analysis.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False, indent=2)
    
    print(f"\n\n完整分析结果已保存到: {output_file}")
    
    # 5. 结论
    print("\n"+"="*80)
    print("分析结论")
    print("="*80)
    
    success_count = sum(1 for r in mapping_results if r.get('status') == 'success')
    print(f"\n视频映射成功率: {success_count}/{len(mapping_results)} = {success_count/len(mapping_results)*100:.1f}%")
    print(f"\n映射规则: 预览图文件名 = bf_file.id")
    print(f"预览图路径: .preview/{{hex_folder}}/{{file_id}}.{{size}}.webp")
    print(f"  其中 size 可以是: small, medium, large, hd")
    
    conn.close()

if __name__ == "__main__":
    main()
