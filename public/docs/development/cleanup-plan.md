# 文件清理计划 - v0.1.0

## 清理目标
移除开发过程中产生的临时文件、测试文件和旧版本文�?保留核心功能文件�?

---

## 📂 文件分类

### �?保留文件 (核心功能)

#### PHP核心文件
- `index.php` - 主页
- `browse.php` - 浏览页面
- `view.php` - 单视频页�?
- `search.php` - 搜索功能
- `download.php` - 下载功能
- `file-serve.php` - 文件服务
- `preview.php` - 预览图服�?
- `config.php` - 配置文件
- `api.php` - API接口 (如需�?
- `install.php` - 安装向导 (如需�?

#### 管理器类
- `includes/BillfishManagerV2.php` - �?当前使用的管理器

#### Python脚本 (保留核心)
- `generate_mapping_simple.py` - �?核心映射生成�?
- `list_tables.py` - �?数据库表分析
- `deep_analysis.py` - �?深度分析工具
- `export_database.py` - �?数据库导�?

#### 文档
- `README.md` - 项目说明
- `generate_previews_guide.md` - 预览图指�?
- `SYSTEM_SUMMARY.md` - 系统总结

#### 数据库导�?
- `database-exports/` - �?保留整个目录

---

### �?删除文件 (临时/测试/旧版�?

#### PHP临时分析文件 (20�?
```
analyze.php                      # 临时分析
binary-analysis.php              # 二进制分�?
build-mapping.php                # 旧映射构�?
complete-analysis.php            # 完整分析
ctime-mapping.php                # ctime映射测试
database-analysis.php            # 数据库分�?
database-complete-analysis.php   # 完整数据库分�?
debug-preview.php                # 调试预览
deep-analyze.php                 # 深度分析
final-mapping.php                # 最终映�?
final-verification.php           # 最终验�?
fresh-analysis.php               # 新鲜分析
pattern-analysis.php             # 模式分析
perfect-mapping.php              # 完美映射
real-analysis.php                # 真实分析
rebuild-mapping.php              # 重建映射
reverse-engineering.php          # 逆向工程
simple-analyze.php               # 简单分�?
solution-analysis.php            # 解决方案分析
verify-mapping.php               # 验证映射
```

#### PHP测试文件 (5�?
```
test-ctime.php                   # ctime测试
test-mapping.php                 # 映射测试
test-preview.php                 # 预览测试
test-v2.php                      # v2测试
test-output.html                 # 测试输出
```

#### Python旧版�?(3�?
```
analyze_preview_mapping.py       # 预览映射分析(�?
build_true_mapping.py            # 真实映射构建(�?
generate_mapping.py              # 映射生成(旧版,已被simple版替�?
list_missing_previews.py         # 缺失预览列表(已完�?00%)
```

#### JSON映射文件 (旧版�?6�?
```
preview-mapping.json             # 旧映�?
preview-mapping-v2.json          # v2映射
preview-mapping-ctime.json       # ctime映射
preview-mapping-final.json       # 最终映�?
preview-mapping-perfect.json     # 完美映射
preview_mapping_analysis.json    # 映射分析
```
**保留**: `database-exports/id_based_mapping.json` (当前使用)

#### 文本文件 (2�?
```
mapping_result.txt               # 映射结果文本
missing_previews_report.txt      # 缺失报告(已解�?
```

#### 旧管理器 (1�?
```
includes/BillfishManager.php     # 旧版管理�?已被V2替代)
```

#### SQLite工具 (可选删�?
```
sqlite-tools-win32-x86-3420000/  # SQLite工具目录
sqlite-tools.zip                 # SQLite工具压缩�?
sqlite3.exe                      # SQLite可执行文�?
```
**说明**: 如果系统已安装SQLite,可删�?

#### 批处理脚�?(可选保�?
```
export-database.bat              # Windows批处�?
export-database.ps1              # PowerShell脚本
```
**说明**: 如果只用Python脚本,可删�?

---

## 🗑�?删除计划

### 方案1: 移动到archive目录(推荐)
创建归档目录,保留历史文件:
```bash
mkdir public/archive
mv [临时文件] public/archive/
```

### 方案2: 直接删除
永久删除不再需要的文件:
```bash
rm [临时文件]
```

---

## 📊 清理统计

### 清理�?
- 总文件数: ~62�?
- PHP文件: ~35�?
- Python文件: ~7�?
- JSON文件: ~6�?

### 清理�?
- 保留核心文件: ~20�?
- PHP核心: ~10�?
- Python核心: ~4�?
- JSON映射: ~1�?在database-exports�?

### 空间节省
- 预计清理: ~40个临时文�?
- 减少混乱�? ~65%

---

## �?执行步骤

### 1. 创建归档目录
```bash
cd public
mkdir archive
mkdir archive/old-php-scripts
mkdir archive/old-python-scripts
mkdir archive/old-mappings
mkdir archive/test-files
```

### 2. 移动PHP分析文件
```bash
mv analyze.php archive/old-php-scripts/
mv binary-analysis.php archive/old-php-scripts/
mv build-mapping.php archive/old-php-scripts/
# ... 其他分析文件
```

### 3. 移动测试文件
```bash
mv test-*.php archive/test-files/
mv test-*.html archive/test-files/
```

### 4. 移动旧映射文�?
```bash
mv preview-mapping*.json archive/old-mappings/
mv mapping_result.txt archive/old-mappings/
mv missing_previews_report.txt archive/old-mappings/
```

### 5. 移动旧Python脚本
```bash
mv analyze_preview_mapping.py archive/old-python-scripts/
mv build_true_mapping.py archive/old-python-scripts/
mv generate_mapping.py archive/old-python-scripts/
mv list_missing_previews.py archive/old-python-scripts/
```

### 6. 删除旧管理器
```bash
mv includes/BillfishManager.php archive/old-php-scripts/
```

### 7. 可�? 删除SQLite工具
```bash
# 如果系统已有SQLite
rm -rf sqlite-tools-win32-x86-3420000/
rm sqlite-tools.zip
rm sqlite3.exe
```

---

## 🎯 清理后的目录结构

```
public/
├── assets/                      # 静态资�?
├── includes/
�?  └── BillfishManagerV2.php    # �?当前管理�?
├── database-exports/             # �?数据库导�?
�?  ├── id_based_mapping.json    # �?当前映射
�?  └── ...
├── archive/                      # 🗄�?归档文件
�?  ├── old-php-scripts/
�?  ├── old-python-scripts/
�?  ├── old-mappings/
�?  └── test-files/
├── index.php                     # �?主页
├── browse.php                    # �?浏览
├── view.php                      # �?单视�?
├── search.php                    # �?搜索
├── download.php                  # �?下载
├── file-serve.php                # �?文件服务
├── preview.php                   # �?预览服务
├── config.php                    # �?配置
├── generate_mapping_simple.py    # �?映射生成
├── list_tables.py                # �?表分�?
├── deep_analysis.py              # �?深度分析
├── export_database.py            # �?数据库导�?
├── README.md                     # �?说明文档
└── generate_previews_guide.md    # �?预览指南
```

---

## ⚠️ 注意事项

1. **备份重要数据**
   - 在清理前先提交Git: `git add . && git commit -m "chore: 清理前备�?`
   - 或创建archive目录而不是直接删�?

2. **验证功能**
   - 清理后运行完整测�?
   - 确保核心功能正常

3. **更新.gitignore**
   - 添加archive/目录�?gitignore
   - 避免归档文件被提�?

---

## 📝 执行记录

- [ ] 创建archive目录
- [ ] 移动PHP分析文件(20�?
- [ ] 移动测试文件(5�?
- [ ] 移动旧映射文�?6�?
- [ ] 移动旧Python脚本(4�?
- [ ] 移动旧管理器(1�?
- [ ] 可�? 删除SQLite工具
- [ ] 更新.gitignore
- [ ] 提交清理结果
- [ ] 验证功能正常

---

## 🎊 预期结果

清理后的项目将更�?
- �?**简�?* - 只保留核心文�?
- �?**清晰** - 目录结构一目了�?
- �?**专业** - 移除开发痕�?
- �?**可维�?* - 减少文件混乱

---

**建议**: 先执行方�?(归档),确认无问题后再考虑永久删除�?

