@echo off
REM Billfish 数据库导出脚本
REM 使用 sqlite3.exe 导出关键数据

echo === Billfish 数据库数据导出 ===
echo.

set DB_PATH=..\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db
set OUTPUT_DIR=database-exports

if not exist %OUTPUT_DIR% mkdir %OUTPUT_DIR%

echo 正在导出数据...
echo.

REM 导出材料表
echo 1. 导出 bf_material_v2 表...
sqlite3 "%DB_PATH%" -header -csv "SELECT * FROM bf_material_v2;" > %OUTPUT_DIR%\bf_material_v2.csv

REM 导出文件表
echo 2. 导出 bf_file 表...
sqlite3 "%DB_PATH%" -header -csv "SELECT * FROM bf_file;" > %OUTPUT_DIR%\bf_file.csv

REM 导出标签表
echo 3. 导出 bf_tag 表...
sqlite3 "%DB_PATH%" -header -csv "SELECT * FROM bf_tag;" > %OUTPUT_DIR%\bf_tag.csv

REM 导出材料标签关联表
echo 4. 导出 bf_material_tag 表...
sqlite3 "%DB_PATH%" -header -csv "SELECT * FROM bf_material_tag;" > %OUTPUT_DIR%\bf_material_tag.csv

REM 导出用户数据表
echo 5. 导出 bf_material_userdata 表...
sqlite3 "%DB_PATH%" -header -csv "SELECT * FROM bf_material_userdata;" > %OUTPUT_DIR%\bf_material_userdata.csv

REM 导出映射关系
echo 6. 生成视频-预览图映射...
sqlite3 "%DB_PATH%" -header -csv "SELECT m.id, m.name, m.preview_tid, m.ext, f.path FROM bf_material_v2 m LEFT JOIN bf_file f ON m.id = f.id WHERE m.preview_tid IS NOT NULL;" > %OUTPUT_DIR%\video_preview_mapping.csv

echo.
echo === 导出完成 ===
echo 数据已保存到 %OUTPUT_DIR% 目录
echo.
pause