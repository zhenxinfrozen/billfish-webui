# API 参考文档

Billfish Web Manager 提供了 RESTful 风格的 API 接口，用于管理资源库和访问文档系统。

## 基础信息

- **Base URL**：`http://your-domain.com/api/`
- **Content-Type**：`application/json`
- **字符编码**：UTF-8

## 资源库配置 API

### 端点：`/api/library-config.php`

#### 1. 获取资源库列表

```http
GET /api/library-config.php?action=list
```

**响应示例**：
```json
{
  "success": true,
  "libraries": [
    {
      "id": "demo",
      "name": "演示资源库",
      "type": "project",
      "path": "./demo-billfish",
      "active": true
    }
  ],
  "current": "demo"
}
```

#### 2. 切换资源库

```http
POST /api/library-config.php
Content-Type: application/json

{
  "action": "switch",
  "libraryId": "demo"
}
```

**响应示例**：
```json
{
  "success": true,
  "message": "资源库切换成功",
  "library": {
    "id": "demo",
    "name": "演示资源库",
    "path": "./demo-billfish"
  }
}
```

#### 3. 添加资源库

```http
POST /api/library-config.php
Content-Type: application/json

{
  "action": "add",
  "library": {
    "id": "new-lib",
    "name": "新资源库",
    "type": "computer",
    "path": "D:/MyBillfish"
  }
}
```

**响应示例**：
```json
{
  "success": true,
  "message": "资源库添加成功"
}
```

#### 4. 删除资源库

```http
POST /api/library-config.php
Content-Type: application/json

{
  "action": "delete",
  "libraryId": "old-lib"
}
```

#### 5. 验证资源库

```http
POST /api/library-config.php
Content-Type: application/json

{
  "action": "validate",
  "path": "D:/MyBillfish"
}
```

**响应示例**：
```json
{
  "success": true,
  "valid": true,
  "message": "资源库路径有效",
  "details": {
    "databaseFound": true,
    "previewFolderExists": true
  }
}
```

## 文档 API

### 端点：`/api/docs.php`

#### 1. 获取文档列表

```http
GET /api/docs.php?action=list
```

**响应示例**：
```json
{
  "success": true,
  "sections": [
    {
      "id": "getting-started",
      "name": "入门指南",
      "icon": "🚀",
      "documents": [
        {
          "file": "quick-start.md",
          "title": "快速开始",
          "description": "快速上手指南"
        }
      ]
    }
  ]
}
```

#### 2. 获取文档内容

```http
GET /api/docs.php?action=get&section=getting-started&file=quick-start.md
```

**响应示例**：
```json
{
  "success": true,
  "document": {
    "title": "快速开始",
    "content": "# 快速开始\n\n...",
    "metadata": {
      "section": "getting-started",
      "file": "quick-start.md"
    }
  }
}
```

#### 3. 搜索文档

```http
GET /api/docs.php?action=search&query=配置
```

**响应示例**：
```json
{
  "success": true,
  "results": [
    {
      "section": "getting-started",
      "document": {
        "file": "library-configuration.md",
        "title": "资源库配置"
      },
      "preview": "...配置资源库的方法..."
    }
  ],
  "total": 1
}
```

## 工具 API

### 端点：`/api/tools.php`

#### 1. 获取工具列表

```http
GET /api/tools.php?action=list
```

#### 2. 执行工具操作

```http
POST /api/tools.php
Content-Type: application/json

{
  "action": "analyze",
  "target": "database"
}
```

## 错误响应

所有 API 在发生错误时返回统一格式：

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "错误描述"
  }
}
```

### 常见错误码

| 错误码 | 说明 |
|--------|------|
| INVALID_ACTION | 无效的操作 |
| MISSING_PARAMETER | 缺少必需参数 |
| LIBRARY_NOT_FOUND | 资源库不存在 |
| INVALID_PATH | 无效的路径 |
| PERMISSION_DENIED | 权限被拒绝 |
| DATABASE_ERROR | 数据库错误 |

## HTTP 状态码

- `200 OK`：请求成功
- `400 Bad Request`：请求参数错误
- `404 Not Found`：资源不存在
- `500 Internal Server Error`：服务器错误

## 使用示例

### JavaScript (Fetch API)

```javascript
// 切换资源库
async function switchLibrary(libraryId) {
  const response = await fetch('/api/library-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'switch',
      libraryId: libraryId
    })
  });
  
  const data = await response.json();
  if (data.success) {
    console.log('切换成功:', data.message);
  } else {
    console.error('切换失败:', data.error);
  }
}

// 搜索文档
async function searchDocs(query) {
  const response = await fetch(`/api/docs.php?action=search&query=${encodeURIComponent(query)}`);
  const data = await response.json();
  return data.results;
}
```

### PHP (cURL)

```php
<?php
// 添加资源库
$data = [
    'action' => 'add',
    'library' => [
        'id' => 'new-lib',
        'name' => '新资源库',
        'type' => 'computer',
        'path' => 'D:/MyBillfish'
    ]
];

$ch = curl_init('http://localhost:8800/api/library-config.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "添加成功\n";
}
?>
```

### Python (requests)

```python
import requests

# 获取资源库列表
response = requests.get('http://localhost:8800/api/library-config.php?action=list')
data = response.json()

if data['success']:
    for lib in data['libraries']:
        print(f"{lib['name']}: {lib['path']}")

# 切换资源库
payload = {
    'action': 'switch',
    'libraryId': 'demo'
}
response = requests.post(
    'http://localhost:8800/api/library-config.php',
    json=payload,
    headers={'Content-Type': 'application/json'}
)
result = response.json()
```

## 安全注意事项

1. **输入验证**：所有输入都应验证
2. **路径安全**：防止路径遍历攻击
3. **CORS**：根据需要配置跨域访问
4. **认证**：生产环境建议添加认证机制
5. **日志记录**：记录 API 调用日志

## 速率限制

目前未实施速率限制，但建议：
- 避免频繁调用
- 使用适当的缓存
- 批量操作时添加延迟

## 版本信息

- **API 版本**：1.0
- **最后更新**：2025-01-17

## 相关文档

- [快速开始](../getting-started/quick-start.md)
- [资源库配置](../getting-started/library-configuration.md)
- [开发文档](../development/README.md)

---

**提示**：API 仍在持续开发中，接口可能会有变化。

