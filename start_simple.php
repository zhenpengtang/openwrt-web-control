<?php
// OpenClaw Gateway - Simple VM Start Controller
// Starts VM and provides instructions for auto-suspend

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
    // TODO: Replace with your actual PVE API token
    $api_token = 'YOUR_PVE_API_TOKEN_HERE';
    
    if ($_GET['action'] === 'start_vm') {
        // Build the API URL for start
        $api_url = "https://{$pve_host}:{$pve_port}/api2/json/nodes/{$node_name}/qemu/{$vm_id}/status/start";
        
        // Create stream context for POST request
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    "Authorization: PVEAPIToken={$api_token}",
                    "Content-Length: 0"
                ],
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = file_get_contents($api_url, false, $context);
        $http_response_header_parsed = $http_response_header ?? [];
        $http_code = 0;
        
        // Parse HTTP status code from headers
        foreach ($http_response_header_parsed as $header) {
            if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
                $http_code = (int)$matches[1];
                break;
            }
        }
        
        if ($response === false) {
            echo json_encode(['error' => 'Network request failed']);
        } elseif ($http_code !== 200) {
            echo json_encode(['error' => 'HTTP Error: ' . $http_code, 'response' => substr($response, 0, 500)]);
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
    <title>OpenClaw Gateway - VM启动控制</title>
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
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            font-weight: bold;
            background-color: #27ae60;
            color: white;
        }
        .btn:hover {
            background-color: #219a52;
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
        .instructions {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }
        .instructions h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .instructions pre {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OpenClaw Gateway</h1>
        <h2>VM启动控制面板</h2>
        <p style="text-align: center; color: #7f8c8d;">
            启动VM后，请手动设置20分钟后的自动暂停
        </p>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <button id="startBtn" class="btn" onclick="startVM()">启动VM</button>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            正在启动VM...
        </div>
        
        <div id="statusContainer" class="status-container">
            <div class="status-title">操作结果:</div>
            <div id="statusContent" class="status-content"></div>
        </div>
        
        <div class="instructions">
            <h3>20分钟后自动暂停VM的命令</h3>
            <p>VM启动成功后，在终端中运行以下命令来设置20分钟（1200秒）后的自动暂停：</p>
            <pre>sleep 1200 && curl -k -X POST "https://192.168.88.22:8006/api2/json/nodes/pve/qemu/105/status/suspend" \
-H 'Authorization: PVEAPIToken=YOUR_PVE_API_TOKEN_HERE' \
-d "todisk=1"</pre>
            <p><strong>注意：</strong>这个命令会在当前终端中运行，如果关闭终端，定时器也会停止。</p>
        </div>
    </div>

    <script>
        function startVM() {
            if (!confirm('确定要启动VM吗？')) {
                return;
            }
            
            const btn = document.getElementById('startBtn');
            const loading = document.getElementById('loading');
            const statusContainer = document.getElementById('statusContainer');
            const statusContent = document.getElementById('statusContent');
            
            // Disable button and show loading
            btn.disabled = true;
            loading.style.display = 'block';
            statusContainer.style.display = 'none';
            
            // Make AJAX request to start VM
            fetch('?action=start_vm')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    statusContent.innerHTML = '<span class="success">VM已成功启动！</span>\n\n' + JSON.stringify(data, null, 2);
                    statusContainer.style.display = 'block';
                })
                .catch(error => {
                    statusContent.innerHTML = '<span class="error">错误: ' + error.message + '</span>';
                    statusContainer.style.display = 'block';
                })
                .finally(() => {
                    // Hide loading
                    loading.style.display = 'none';
                });
        }
    </script>
</body>
</html>