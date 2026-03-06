<?php
// OpenClaw Gateway - OpenWrt VM Control Panel
// Simple PHP interface using built-in functions (no cURL required)

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle AJAX requests
if (isset($_GET['action'])) {
    // Set appropriate headers for JSON response
    header('Content-Type: application/json');
    
    // Proxmox API configuration
    $pve_host = '192.168.88.22';
    $pve_port = '8006';
    $vm_id = '105';
    $node_name = 'pve';
    $api_token = 'root@pam!fZ7m2mVBa4vwhlgw=057d76a4-4d2c-4870-afce-05ceb3303e9c';
    
    if ($_GET['action'] === 'get_vm_status') {
        // Build the API URL for status
        $api_url = "https://{$pve_host}:{$pve_port}/api2/json/nodes/{$node_name}/qemu/{$vm_id}/status/current";
        
        // Create stream context with SSL and headers
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Authorization: PVEAPIToken={$api_token}",
                    "User-Agent: OpenClaw-Gateway"
                ],
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Make the request
        $response = @file_get_contents($api_url, false, $context);
        $http_response_header = $http_response_header ?? [];
        
        // Check if request was successful
        if ($response === false) {
            echo json_encode(['error' => 'Failed to connect to Proxmox API']);
            exit;
        }
        
        // Parse HTTP status code from headers
        $status_code = 200;
        if (!empty($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
                    $status_code = (int)$matches[1];
                    break;
                }
            }
        }
        
        if ($status_code !== 200) {
            echo json_encode(['error' => 'HTTP Error: ' . $status_code, 'response' => substr($response, 0, 500)]);
        } else {
            echo $response; // Return raw API response
        }
        exit;
    }
    
    if ($_GET['action'] === 'suspend_vm') {
        // Build the API URL for suspend
        $api_url = "https://{$pve_host}:{$pve_port}/api2/json/nodes/{$node_name}/qemu/{$vm_id}/status/suspend";
        
        // Create stream context for POST request
        $post_data = "todisk=1";
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    "Authorization: PVEAPIToken={$api_token}",
                    "Content-Type: application/x-www-form-urlencoded",
                    "Content-Length: " . strlen($post_data),
                    "User-Agent: OpenClaw-Gateway"
                ],
                'content' => $post_data,
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Make the request
        $response = @file_get_contents($api_url, false, $context);
        $http_response_header = $http_response_header ?? [];
        
        // Check if request was successful
        if ($response === false) {
            echo json_encode(['error' => 'Failed to connect to Proxmox API']);
            exit;
        }
        
        // Parse HTTP status code from headers
        $status_code = 200;
        if (!empty($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
                    $status_code = (int)$matches[1];
                    break;
                }
            }
        }
        
        if ($status_code !== 200) {
            echo json_encode(['error' => 'HTTP Error: ' . $status_code, 'response' => substr($response, 0, 500)]);
        } else {
            echo $response; // Return raw API response
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenClaw Gateway - VM控制面板</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn-check {
            background-color: #3498db;
            color: white;
        }
        .btn-check:hover {
            background-color: #2980b9;
        }
        .btn-suspend {
            background-color: #e74c3c;
            color: white;
        }
        .btn-suspend:hover {
            background-color: #c0392b;
        }
        .btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        .status-container {
            margin-top: 20px;
            padding: 20px;
            border-radius: 5px;
            background-color: #ecf0f1;
            display: none;
        }
        .status-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .status-content {
            white-space: pre-wrap;
            font-family: monospace;
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
        .loading {
            text-align: center;
            color: #7f8c8d;
            margin-top: 20px;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OpenClaw Gateway</h1>
        <h2>OpenWrt VM 控制面板</h2>
        
        <div class="button-group">
            <button id="checkStatusBtn" class="btn btn-check" onclick="checkVMStatus()">查看VM状态</button>
            <button id="suspendBtn" class="btn btn-suspend" onclick="suspendVM()">暂停VM</button>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            正在处理请求...
        </div>
        
        <div id="statusContainer" class="status-container">
            <div class="status-title">操作结果:</div>
            <div id="statusContent" class="status-content"></div>
        </div>
    </div>

    <script>
        function checkVMStatus() {
            const btn = document.getElementById('checkStatusBtn');
            const suspendBtn = document.getElementById('suspendBtn');
            const loading = document.getElementById('loading');
            const statusContainer = document.getElementById('statusContainer');
            const statusContent = document.getElementById('statusContent');
            
            // Disable buttons and show loading
            btn.disabled = true;
            suspendBtn.disabled = true;
            loading.style.display = 'block';
            statusContainer.style.display = 'none';
            
            // Make AJAX request
            fetch('?action=get_vm_status')
                .then(response => response.json())
                .then(data => {
                    statusContent.textContent = JSON.stringify(data, null, 2);
                    statusContainer.style.display = 'block';
                })
                .catch(error => {
                    statusContent.innerHTML = '<span class="error">错误: ' + error.message + '</span>';
                    statusContainer.style.display = 'block';
                })
                .finally(() => {
                    // Re-enable buttons and hide loading
                    btn.disabled = false;
                    suspendBtn.disabled = false;
                    loading.style.display = 'none';
                });
        }
        
        function suspendVM() {
            if (!confirm('确定要暂停VM吗？这将把VM状态保存到磁盘。')) {
                return;
            }
            
            const btn = document.getElementById('checkStatusBtn');
            const suspendBtn = document.getElementById('suspendBtn');
            const loading = document.getElementById('loading');
            const statusContainer = document.getElementById('statusContainer');
            const statusContent = document.getElementById('statusContent');
            
            // Disable buttons and show loading
            btn.disabled = true;
            suspendBtn.disabled = true;
            loading.style.display = 'block';
            statusContainer.style.display = 'none';
            
            // Make AJAX request
            fetch('?action=suspend_vm')
                .then(response => response.json())
                .then(data => {
                    statusContent.innerHTML = '<span class="success">VM已成功暂停！</span>\n\n' + JSON.stringify(data, null, 2);
                    statusContainer.style.display = 'block';
                })
                .catch(error => {
                    statusContent.innerHTML = '<span class="error">错误: ' + error.message + '</span>';
                    statusContainer.style.display = 'block';
                })
                .finally(() => {
                    // Re-enable buttons and hide loading
                    btn.disabled = false;
                    suspendBtn.disabled = false;
                    loading.style.display = 'none';
                });
        }
    </script>
</body>
</html>