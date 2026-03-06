# OpenClaw Gateway

一个轻量级的PHP原生Web控制面板，专为OpenWrt路由器设计，运行在Proxmox VE虚拟机环境中。

## 项目概述

OpenClaw Gateway 是一个现代化的Web界面，让你能够通过浏览器轻松管理和监控你的OpenWrt路由器。无需复杂的SSH命令，所有常用功能都集成在一个直观的界面中。

## 主要特性

- **VM状态监控**: 实时查看OpenWrt VM运行状态
- **一键启动/暂停**: 快速启动和暂停VM（支持保存到磁盘）
- **自动暂停定时器**: 启动后可设置自动暂停时间（如20分钟）
- **轻量高效**: 纯PHP原生代码，无外部依赖，资源占用极低
- **安全认证**: 使用Proxmox API令牌进行安全认证

## 技术架构

- **前端**: 原生HTML5 + CSS3 + JavaScript (无框架)
- **后端**: PHP原生 (使用内置stream_context_create，无需cURL扩展)
- **API集成**: Proxmox VE REST API
- **安全**: HTTPS + API令牌认证

## 部署环境

- **主机平台**: Proxmox VE 虚拟化环境
- **目标VM**: OpenWrt x86_64 虚拟机 (VM ID: 105)
- **Web服务器**: PHP内置开发服务器 或 Apache/Nginx
- **PHP版本**: PHP 7.4+

## 安全配置

**重要：在使用前必须配置API令牌！**

1. **获取Proxmox API令牌**:
   - 登录Proxmox VE Web界面
   - 进入 `Datacenter` → `Authentication` → `API Tokens`
   - 为用户 `root@pam` 创建新令牌
   - 复制生成的完整令牌（格式：`username!token_name=token_value`）

2. **配置PHP文件**:
   - 打开 `test.php`, `start.php`, `start_simple.php`
   - 找到 `$api_token = 'YOUR_PVE_API_TOKEN_HERE';` 行
   - 替换为你的实际API令牌

3. **设置文件权限**:
   ```bash
   chmod 600 /path/to/your/php/files/*.php
   ```

## 文件说明

- **`test.php`**: 基础控制面板，包含查看VM状态和暂停VM功能
- **`start.php`**: 高级控制面板，包含启动VM和20分钟自动暂停功能
- **`start_simple.php`**: 简化版启动控制面板，适合PHP内置服务器环境

## 使用说明

### 快速启动
```bash
cd /root/openwrt-web-control
php -S 0.0.0.0:8080
```

### 访问控制面板
- 基础功能: `http://[服务器IP]:8080/test.php`
- 启动功能: `http://[服务器IP]:8080/start_simple.php`

### 手动启动VM并设置自动暂停
```bash
# 启动VM（替换YOUR_TOKEN为实际令牌）
curl -k -X POST "https://192.168.88.22:8006/api2/json/nodes/pve/qemu/105/status/start" \
-H 'Authorization: PVEAPIToken=YOUR_TOKEN'

# 设置20分钟后自动暂停
(sleep 1200 && curl -k -X POST "https://192.168.88.22:8006/api2/json/nodes/pve/qemu/105/status/suspend" \
-H 'Authorization: PVEAPIToken=YOUR_TOKEN' \
-d "todisk=1") > /tmp/auto_suspend.log 2>&1 &
```

## 安全注意事项

- **API令牌保护**: 所有敏感令牌已从代码中移除，请务必使用自己的令牌
- **文件权限**: 确保PHP文件权限设置为600，防止令牌泄露
- **网络访问**: 建议仅在内网使用或配合防火墙限制访问
- **HTTPS**: 生产环境中务必启用HTTPS加密
- **定期轮换**: 定期更换Proxmox API令牌以提高安全性

## 开发状态

✅ **基础功能已完成** ✅

- VM状态查询 ✓
- VM暂停功能 ✓  
- VM启动功能 ✓
- 自动暂停定时器 ✓

## 许可证

MIT License - 允许自由使用、修改和分发。

## 致谢

- Proxmox VE团队
- OpenWrt社区
- 所有开源贡献者

---

**OpenClaw Gateway** - 让OpenWrt VM管理变得更简单！