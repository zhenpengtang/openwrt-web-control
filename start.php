<?php
// OpenClaw Gateway - VM Auto-Suspend Controller
// Starts VM and automatically suspends it after 20 minutes

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
    
    if ($_GET['action'] === 'suspend_vm') {
        // Build the API URL for suspend
        $api_url = "https://{$pve_host}:{$pve_port}/api2/json/nodes/{$node_name}/qemu/{$vm_id}/status/suspend";
        
        // Create stream context for POST request with todisk=1
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    "Authorization: PVEAPIToken={$api_token}",
                    "Content-Type: application/x-www-form-urlencoded"
                ],
                'content' => "todisk=1",
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

// Handle auto-suspend scheduling
if (isset($_GET['schedule_suspend'])) {
    // Schedule automatic suspend after 20 minutes (1200 seconds)
    $suspend_time = time() + 1200; // 20 minutes from now
    
    // Create a simple cron job or use system sleep
    // For simplicity, we'll create a background process
    
    // Write a simple suspend script
    $suspend_script = "<?php
// Auto-suspend script for VM {$vm_id}
sleep(1200); // Wait 20 minutes

// Suspend VM
\$pve_host = '{$pve_host}';
\$pve_port = '{$pve_port}';
\$vm_id = '{$vm_id}';
\$node_name = '{$node_name}';
// TODO: Replace with your actual PVE API token
\$api_token = 'YOUR_PVE_API_TOKEN_HERE';

\$api_url = \"https://{\$pve_host}:{\$pve_port}/api2/json/nodes/{\$node_name}/qemu/{\$vm_id}/status/suspend\";

\$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            \"Authorization: PVEAPIToken={\$api_token}\",
            \"Content-Type: application/x-www-form-urlencoded\"
        ],
        'content' => \"todisk=1\",
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

file_get_contents(\$api_url, false, \$context);
?>";

    file_put_contents('/tmp/auto_suspend_vm.php', $suspend_script);
    
    // Start background process
    exec("php /tmp/auto_suspend_vm.php > /tmp/auto_suspend.log 2>&1 &");
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'VM will be suspended in 20 minutes']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenClaw Gateway - VM自动控制</title>
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
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            font-weight: bold;
        }
        .btn-start {
            background-color: #27ae60;
            color: white;
        }
        .btn-start:hover {
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
        .timer {
            text-align: center;
            font-size: 18px;
            color: #e67e22;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OpenClaw Gateway</h1>
        <h2>VM自动控制面板</h2>
        <p style="text-align: center; color: #7f8c8d;">
            启动VM后，系统将在20分钟后自动暂停VM（保存到磁盘）
        </p>
        
        <div class="button-group">
            <button id="startBtn" class="btn btn-start" onclick="startVM()">启动VM (20分钟自动暂停)</button>
        </div>
        
        <div id="timer" class="timer" style="display: none;">
            自动暂停倒计时: <span id="countdown">20:00</span>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            正在启动VM...
        </div>
        
        <div id="statusContainer" class="status-container">
            <div class="status-title">操作结果:</div>
            <div id="statusContent" class="status-content"></div>
        </div>
    </div>

    <script>
        function startVM() {
            if (!confirm('确定要启动VM吗？VM将在20分钟后自动暂停。')) {
                return;
            }
            
            const btn = document.getElementById('startBtn');
            const loading = document.getElementById('loading');
            const statusContainer = document.getElementById('statusContainer');
            const statusContent = document.getElementById('statusContent');
            const timer = document.getElementById('timer');
            const countdown = document.getElementById('countdown');
            
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
                    
                    // Show timer and start countdown
                    timer.style.display = 'block';
                    startCountdown(1200, countdown); // 1200 seconds = 20 minutes
                    
                    // Schedule auto-suspend on server
                    fetch('?schedule_suspend=true')
                        .then(resp => resp.json())
                        .then(schedData => {
                            console.log('Auto-suspend scheduled:', schedData);
                        })
                        .catch(err => {
                            console.log('Auto-suspend scheduling error:', err);
                        });
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
        
        function startCountdown(duration, display) {
            let timer = duration, minutes, seconds;
            const countdownInterval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                
                display.textContent = minutes + ":" + seconds;
                
                if (--timer < 0) {
                    clearInterval(countdownInterval);
                    display.textContent = "00:00";
                    // Optionally refresh status or show completion message
                }
            }, 1000);
        }
    </script>
</body>
</html>