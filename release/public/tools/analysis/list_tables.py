#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
查看 Billfish 数据库的所有表
"""

import sqlite3

DB_PATH = r'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db'

conn = sqlite3.connect(DB_PATH)
cursor = conn.cursor()

print("=== Billfish Database Tables ===\n")

# 获取所有表
cursor.execute("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
tables = cursor.fetchall()

print(f"Found {len(tables)} tables:\n")
for i, (table_name,) in enumerate(tables, 1):
    print(f"{i}. {table_name}")
    
    # 获取表结构
    cursor.execute(f"PRAGMA table_info({table_name})")
    columns = cursor.fetchall()
    
    print(f"   Columns ({len(columns)}):")
    for col in columns:
        col_id, name, type_, notnull, dflt_value, pk = col
        print(f"      - {name} ({type_})" + (" PRIMARY KEY" if pk else ""))
    
    # 获取记录数
    cursor.execute(f"SELECT COUNT(*) FROM {table_name}")
    count = cursor.fetchone()[0]
    print(f"   Records: {count}")
    print()

conn.close()