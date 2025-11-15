# API接口文档

这是一个放在api目录中的测试文档，用于验证新目录的自动发现功能。

## 接口列表

### 文件管理接口

- `GET /api/files` - 获取文件列表
- `POST /api/files` - 上传文件
- `DELETE /api/files/{id}` - 删除文件

### 预览接口

- `GET /preview.php?id={file_id}` - 获取预览图

## 返回格式

所有接口返回JSON格式数据�?

```json
{
    "status": "success",
    "data": {},
    "message": ""
}
```

这个文档会自动在"API文档"分类中显示�

