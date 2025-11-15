#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
列出所有缺少预览图的文件,方便在 Billfish 中定位
"""

import json
import os
from collections import defaultdict

# 配置
MAPPING_FILE = 'database-exports/id_based_mapping.json'

def main():
    # 读取映射文件
    with open(MAPPING_FILE, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # 按文件夹分组统计
    missing_by_folder = defaultdict(list)
    
    for video_path, info in data.items():
        if not info['preview_exists']:
            folder = info['video_folder']
            missing_by_folder[folder].append({
                'name': info['video_name'],
                'size': info.get('file_size', 0),
                'file_id': info['file_id'],
                'preview_path': info['preview_path']
            })
    
    # 打印统计
    print("=" * 80)
    print(f"Billfish 预览图缺失报告")
    print("=" * 80)
    print(f"\n总文件数: {len(data)}")
    total_missing = sum(len(files) for files in missing_by_folder.values())
    print(f"缺少预览图: {total_missing} ({total_missing*100/len(data):.1f}%)")
    print(f"有预览图: {len(data)-total_missing} ({(len(data)-total_missing)*100/len(data):.1f}%)")
    
    # 按文件夹详细列出
    print(f"\n" + "=" * 80)
    print("按文件夹分组:")
    print("=" * 80)
    
    for folder in sorted(missing_by_folder.keys()):
        files = missing_by_folder[folder]
        print(f"\n📁 {folder}/ ({len(files)} 个文件缺少预览图)")
        print("-" * 80)
        
        for file_info in sorted(files, key=lambda x: x['name']):
            size_mb = file_info['size'] / (1024 * 1024) if file_info['size'] > 0 else 0
            print(f"  • {file_info['name']:<50} {size_mb:>8.2f} MB  [ID: {file_info['file_id']}]")
        
        # 显示预期的预览图路径
        if files:
            first_file = files[0]
            print(f"\n  预览图位置: .preview/{hex(first_file['file_id'])[2:]}/{first_file['file_id']}.small.webp")
    
    # 提供操作建议
    print(f"\n" + "=" * 80)
    print("📝 操作建议:")
    print("=" * 80)
    print("\n1. 在 Billfish 软件中打开以下文件夹:")
    for folder in sorted(missing_by_folder.keys()):
        count = len(missing_by_folder[folder])
        print(f"   • {folder}/ ({count} 个文件)")
    
    print("\n2. 或者右键素材库选择 '重新生成缩略图'")
    
    print("\n3. 生成完成后,运行以下命令更新映射:")
    print("   cd \"d:\\VS CODE\\rzxme-billfish\\public\"")
    print("   python generate_mapping_simple.py")
    
    print("\n4. 刷新网页验证: http://localhost:8000/")
    
    print("\n" + "=" * 80)
    
    # 导出到文件
    report_file = 'missing_previews_report.txt'
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write(f"Billfish 预览图缺失报告\n")
        f.write(f"生成时间: {os.popen('echo %date% %time%').read().strip()}\n")
        f.write(f"=" * 80 + "\n\n")
        
        for folder in sorted(missing_by_folder.keys()):
            files = missing_by_folder[folder]
            f.write(f"📁 {folder}/ ({len(files)} 个文件)\n")
            f.write("-" * 80 + "\n")
            for file_info in sorted(files, key=lambda x: x['name']):
                size_mb = file_info['size'] / (1024 * 1024) if file_info['size'] > 0 else 0
                f.write(f"  • {file_info['name']:<50} {size_mb:>8.2f} MB\n")
            f.write("\n")
    
    print(f"✅ 详细报告已保存到: {report_file}")

if __name__ == '__main__':
    main()
