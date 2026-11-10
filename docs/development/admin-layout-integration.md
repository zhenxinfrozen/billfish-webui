# Layout 与 Admin 后台接入说明

本文记录 Billfish Web Manager 接入两套通用套件的关键步骤、目录结构、鉴权与写操作逻辑，以及落地时的注意事项和已知风险。面向后续维护者与同类项目复用参考。

- 接入日期：2026-11-10
- 涉及套件：`shared-admin`（后台骨架）、`shared-layout`（前台公共头尾）
- 前置背景：项目原本没有独立后台，资料库配置、文档概览、工具中心等功能以「被动式后台」形式散落在前台页面，且全站无鉴权。

## 1. 套件与挂载方式

两套套件均为独立 GitHub 仓库，以 **git submodule** 挂在仓库根目录：

| 子模块 | 远端 | 作用 |
|---|---|---|
| `shared-admin/` | `https://github.com/zhenxinfrozen/shared-admin.git` | 后台深色侧边栏 + 顶栏骨架、`AdminCore` 渲染助手、内置系统信息页、通用 CSS/JS 资产 |
| `shared-layout/` | `https://github.com/zhenxinfrozen/shared-layout.git` | 前台可挂载的 `SiteHeader` / `SiteFooter` 组件 |

### 1.1 挂载命令

```bash
cd <项目根>
git submodule add https://github.com/zhenxinfrozen/shared-admin.git shared-admin
git submodule add https://github.com/zhenxinfrozen/shared-layout.git shared-layout
```

新环境克隆后需初始化子模块：

```bash
git clone <repo>
git submodule update --init --recursive
```

### 1.2 静态资源暴露链接（重要）

本项目 docroot 是 `public/`，而套件源码在仓库根目录，Web 服务器无法直接访问根目录文件，因此在 `public/` 下建立指向套件的链接，仅用于暴露 `assets/` 静态资源：

- Windows（本地开发，junction）：
  ```powershell
  New-Item -ItemType Junction -Path "public\shared-admin" -Target "<根>\shared-admin"
  New-Item -ItemType Junction -Path "public\shared-layout" -Target "<根>\shared-layout"
  ```
- Linux / VPS（软链，部署时手动创建）：
  ```bash
  ln -s ../shared-admin public/shared-admin
  ln -s ../shared-layout public/shared-layout
  ```

这两个链接已写入 `.gitignore`，**不随仓库提交**；PHP `require` 走根目录相对路径，浏览器静态资源 URL 走 `/shared-admin/assets`、`/shared-layout/assets`。

## 2. 前台 Layout 接入（shared-layout）

### 2.1 改造点

公共头尾由原来自写的 Bootstrap 导航，替换为 `SiteHeader` / `SiteFooter` 组件：

- `public/includes/header.php`：输出 HTML 头部、favicon、Bootstrap/FA/项目样式，再 `echo SiteHeader::render(...)`。
- `public/includes/footer.php`：输出底部脚本，再 `echo SiteFooter::render(...)`。

**保留的页面契约**（业务页面无需改动）：

| 变量 | 作用 |
|---|---|
| `$pageTitle` | 页面标题，拼到 `<title>` |
| `$currentPage` | 预留当前页标识；导航激活态由组件按当前 URL 自动判定 |
| `$extraCss` | 额外 `<link>` 片段，在 `</head>` 前输出 |
| `$extraJs` | 额外 `<script>` 片段，在底部脚本区输出 |

### 2.2 头部配置示例

```php
require_once __DIR__ . '/../../shared-layout/src/SiteHeader.php';
use SharedLayout\SiteHeader;

echo SiteHeader::render([
    'brand'      => 'Billfish Web Manager',
    'tagline'    => '素材资源库',
    'badges'     => [BILLFISH_WEB_VERSION],   // git 分支/版本徽章
    'logo'       => '/assets/images/logo.webp',
    'logo_file'  => __DIR__ . '/../assets/images/logo.webp', // 存在性检查；缺失或未传时回退套件内置 default-logo.svg
    'home_url'   => '/index.php',
    'nav'        => ['首页' => '/index.php', '浏览' => '/browse.php', /* ... */],
    'assets_url' => '/shared-layout/assets',
]);
```

### 2.3 样式协调注意事项

- `header.css` 已用 `body.has-fixed-header { padding-top: var(--header-height) }` 处理固定头部留白，项目自己的 `style.css` **不要再写死固定导航栏 `padding-top`**。
- `SiteFooter` 是 sticky 底部（`margin-top: auto` 沉到视口底部），**不要再用 `position: fixed` 定位 footer**；旧的 `.site-footer` 固定规则与 `padding-bottom` 已从 `style.css` 移除。
- 换肤通过接入项目在 `:root` 覆盖 CSS 变量完成，不要改动套件自带 CSS。

### 2.4 项目图标

图标存放于 `public/assets/images/logo.webp`（112×112），用于：浏览器 favicon、前台头部品牌 logo、前台底部 logo、后台登录页品牌图标、后台侧边栏品牌 logo。

> 套件层退守：shared-layout 的 SiteHeader/SiteFooter、shared-admin 侧边栏在未传 logo 或 `logo_file` 磁盘文件缺失时，自动回退各自套件内置的中性占位图 `assets/images/default-logo.svg`；接入方传入有效项目图则优先用项目图。

## 3. 后台接入（shared-admin）

后台位于 `public/admin/`，必须落在 docroot 下才能被 Web 服务访问。

### 3.1 目录与文件清单

```
public/admin/
├── bootstrap.php            # 引导：路径常量、业务配置、会话/鉴权/CSRF/限流/flash/读写备份助手
├── ui.php                    # 门面：初始化 AdminCore，提供 admin_head / admin_foot
├── login.php                 # 独立登录页（不走后台布局）
├── logout.php                # 退出登录
├── config.example.php        # 配置示例（入库）
├── config.local.php           # 本地私有配置（gitignore，不入库）
├── library-manager.php        # 后台资料库管理器（受保护副本）
├── index.php                  # 控制台仪表盘
├── libraries.php              # 资料库配置（受控副本）
├── docs.php                   # 文档知识库（复用 shared-admin 内置 docs Wiki）
├── settings.php               # 网站设置（复用 shared-admin site-settings 模板）
├── template-new.php           # 标准模板与组件展厅（复用 shared-admin 内置页）
├── tools.php                  # 工具中心（只读副本）
├── system.php                 # 系统信息（复用 shared-admin 内置 system-info）
└── api/
    └── libraries.php          # 后台配置 API（登录态 + CSRF）
```

### 3.1.1 内置通用页面接入对照

| 后台页 | 套件能力 | 接入方式 |
|---|---|---|
| `docs.php` | 内置 Markdown Wiki：侧栏目录、阅读、全文搜索、代码高亮 | `AdminCore::handleBuiltinPage('docs', ['docsDir' => DOCS_DIR])` |
| `system.php` | 内置系统信息看板 | `handleBuiltinPage('system-info')` |
| `template-new.php` | 内置标准模板 / 组件展厅 | `handleBuiltinPage('template-new')` |
| `settings.php` | 内置网站设置表单模板（图标/名称/配色/版本/Git 分支） | 项目准备配置数组 + 保存回调后手动 require 模板（见 3.5） |

### 3.2 引导层 `bootstrap.php`

职责划分：

1. **路径常量**：`ADMIN_DIR`、`PUBLIC_DIR`、`ROOT`，业务文件路径 `LIBRARIES_FILE`、`TOOLS_CONFIG_FILE`、`DOCS_DIR`。
2. **加载业务配置**：`require PUBLIC_DIR . '/config.php'`，拿到 `BILLFISH_PATH` 等常量。
3. **后台私有配置**：优先读 `config.local.php`，缺失时回退 `config.example.php`。
4. **会话与鉴权**：独立会话名 `BFADMIN_SID`，`admin_require_login()` 校验登录态与 TTL，未登录 302 到 `login.php`。
5. **CSRF**：`admin_csrf_token()` 生成并存会话；`admin_csrf_check()` 校验 POST 头/表单字段。
6. **失败限流**：按来源 IP 落盘到系统临时目录，连续失败达到上限后锁定一段时间（默认 8 次锁 15 分钟）。
7. **通用助手**：`h()` 编码、`admin_flash()` 消息、`admin_read_json()`、`admin_write_json()`、`admin_save_with_backup()` 原子写、`admin_backup()` + `admin_backup_gc()` 备份轮转。

### 3.3 门面 `ui.php`

- `require ROOT . '/shared-admin/src/AdminCore.php'`，`AdminCore::init()` 注入站点名、副标题、侧边栏 `logo` / `logo_file`、资产 URL、退出地址、当前用户与分组菜单；未传 logo 时由套件回退内置默认图。
- `admin_head($title, $current, $actions, $extraCss)` 渲染后台头部；`admin_foot($extraJs, $inlineJs)` 渲染底部。
- **flash 字段映射**：套件头部模板期望 `type` / `msg`，bootstrap 的 flash 是 `k` / `m`，在 `admin_head()` 内做了一次映射，不要直接混用。

### 3.4 鉴权模型

- 单用户，`password_hash(..., PASSWORD_DEFAULT)` 校验；会话记录 `admin_ok` / `admin_at` / `admin_user`。
- 登录成功后 `session_regenerate_id(true)` 防会话固定；清空失败计数。
- Cookie：`HttpOnly`、`SameSite=Lax`，HTTPS 下带 `Secure`。
- 配置项（`config.local.php`）：`admin_user`、`password_hash`、`login_ttl`、`login_max_attempts`、`login_lock_seconds`、`backup_dir`、`backup_keep`。

生成密码哈希：

```bash
php -r "echo password_hash('你的密码', PASSWORD_DEFAULT);"
```

### 3.5 网站设置（site-settings）

- 配置文件：`public/includes/site_settings.php`，返回关联数组，字段与套件模板对齐——
  `site_name` / `tagline` / `site_description` / `site_icon` / `primary_color` / `header_bg` /
  `brand_color` / `nav_text_color` / `show_version` / `version_text` / `use_git_branch` / `git_branch_cache`。
- `settings.php` 引导后：读入配置并用默认值补齐缺失字段；POST 先 `admin_csrf_check()`；
  保存回调把合并后的数组原子写回配置文件（`admin_save_with_backup()`，写前备份）；
  图标上传目录 `public/assets/images`，URL 前缀 `/assets/images/`；检测当前 Git 分支（main/master 不显示）；
  把 `$csrf_token` 传给套件模板输出隐藏字段。
- 前台 `includes/header.php` / `footer.php` 读取该配置驱动品牌名、副标题、图标与描述，缺字段时兜底当前项目默认值。
- **注意**：项目配置必须补全模板引用的所有字段，不能依赖模板 `?? 默认值` 兜底；否则首次保存会把套件示例值写回项目配置。

### 3.6 后台页面与前台对照

按项目要求，**前台原有页面与公开入口全部保留，后台为受控副本，形成双份**：

| 后台页面 | 路由 | 对应前台副本 | 权限 |
|---|---|---|---|
| 控制台 | `/admin/index.php` | — | 登录后 |
| 资料库配置 | `/admin/libraries.php` | `/tools/library-config.php` | 登录 + CSRF |
| 文档知识库 | `/admin/docs.php` | `/docs-admin.php` | 登录后（只读 Wiki） |
| 网站设置 | `/admin/settings.php` | — | 登录 + CSRF |
| 标准模板展厅 | `/admin/template-new.php` | — | 登录后 |
| 工具中心 | `/admin/tools.php` | `/tools-ui.php` | 登录后（只读） |
| 系统信息 | `/admin/system.php` | shared-admin 内置 `system-info` | 登录后 |
| 后台配置 API | `/admin/api/libraries.php` | `/api/library-config.php` | 登录 + CSRF |

### 3.7 受控 API 与写操作逻辑

`api/libraries.php` 路由：`list`（GET）、`add` / `switch` / `delete` / `scan_nas`（POST）。

- 所有动作先 `admin_require_login()`；写动作再 `admin_csrf_check()`。
- **不开放跨域**：后台同站访问，未设置 `Access-Control-Allow-Origin: *`。
- 资料库清单 `libraries.json` 经 `admin_write_json()` 落盘（UTF-8、不转义斜杠、原子写入，写前自动备份）。
- 切换资料库改写 `config.php` 的 `$primaryPath` 前，先 `admin_backup()` 备份原文件，再正则替换 + 原子落盘；备份保存在 `public/admin/backups/`，超出 `backup_keep` 份数清理最旧。
- 路径解析、有效性校验（`.bf/billfish.db` 存在性）、NAS 扫描逻辑与前台 API 保持一致，封装在 `library-manager.php` 的 `AdminLibraryManager`。

## 4. 目录与忽略规则

`.gitignore` 中已排除：

```
/public/shared-admin        # 本地链接，VPS 手动建软链
/public/shared-layout
public/admin/config.local.php   # 私有密码配置
public/admin/backups/           # 写操作备份
```

子模块指针（`shared-admin`、`shared-layout`）随主仓库提交；`config.example.php` 入库作为模板。

## 5. 注意事项与已知风险

### 5.1 部署注意

- 新环境必须先 `git submodule update --init --recursive`，否则套件源码缺失，后台与前台布局无法渲染。
- VPS 上记得补 `public/shared-admin`、`public/shared-layout` 软链，否则后台 CSS/JS 与前台样式 404。
- 首次部署务必创建 `config.local.php` 并设置强密码；留空哈希时后台会告警且无法登录。

### 5.2 安全遗留（生产前处理）

- **前台公开副本仍可写**：`/api/library-config.php`、`/tools/library-config.php` 按要求保留在前台且未加鉴权。生产若不希望公开写入口，应在反代 / Basic Auth / 目录访问控制层面对这些路径加保护，或后续下线前台副本。
- **遗留调试 / 测试文件**仍在 docroot 下，建议移出 `public/` 或在生产屏蔽：`analyze-thumbnail.php`、`debug-file.php`、`direct-check.php`、`get-file-id.php`、`preview-debug.php`、`simple-serve.php`、`status.php`、`test-custom-thumbnail.php`、`test-nas-connection.php`、`test-preview-logic.php`、`test-robustness.php`、`video-list.php`。
- 工具执行类能力（Python / 命令行）未在后台开放，后台工具页仅展示清单与 Web 入口。
- `config.php` 仍开启 `display_errors`，生产环境建议按部署规范关闭错误回显。

### 5.3 开发坑点备忘

- 套件 PHP 类用根目录相对路径 `require`，浏览器静态资源用 `/shared-*` URL，两套路径不要混用。
- `AdminCore` 菜单分组用 `type => section` 分隔；普通菜单项字段为 `key` / `title` / `icon` / `url` / `badge`。
- 后台只读页面不要复用前台 API；需要写能力时走 `admin/api/`，并补齐登录态与 CSRF。
- 子模块内不要直接改业务代码；通用改进应回流到 shared-admin / shared-layout 上游仓库。

## 6. 待反哺 shared-admin 的通用点（后续 upstream）

以下范式目前在各接入项目间复制，适合沉淀为 shared-admin 的标准能力：

- 后台鉴权引导：会话初始化、登录校验、CSRF、失败限流、flash、JSON 原子写 + 备份 GC，可抽取为可复用的 `AdminAuth` 服务。
- 独立登录页模板（当前在各项目内各写一份）。
- ~~侧边栏品牌 logo 插槽~~ 已回流 shared-admin：`AdminCore` 支持 `logo` / `logo_file`，侧边栏头部渲染 `.sidebar-logo`，缺省回退内置默认图。

## 7. 接入验证记录

本地 `php -S localhost:8099 -t public` 冒烟结果：

- 未登录访问 `/admin/*` → 302 跳转 `login.php`；登录页 200。
- 登录后：`index.php` / `libraries.php` / `docs.php`（内置 Wiki）/ `settings.php` / `template-new.php` / `tools.php` / `system.php` 均 200，无 PHP Warning / Fatal。
- 文档 Wiki：侧栏列出根目录 / api / development 等分组；`?file=` 可正常渲染 Markdown 文档。
- 网站设置：表单预填当前品牌/图标/配色；带 CSRF 提交后配置原子写回 `site_settings.php`，字段完整无示例值污染；缺 CSRF 的 POST 返回 419。
- 后台 API：已登录 `list` 返回 JSON；未登录 302；缺 CSRF 的写请求返回 419。
- 前台五页（首页、浏览、文档中心、文档管理、工具中心）头尾组件读取站点配置正常渲染，无报错。
