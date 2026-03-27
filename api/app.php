<?php
$file = "state.json";

// default state
if (!file_exists($file)) {
    file_put_contents($file, json_encode([
        "lamp" => "OFF",
        "last_heartbeat" => time()
    ], JSON_PRETTY_PRINT));
}

$data = json_decode(file_get_contents($file), true) ?: ["lamp" => "OFF", "led" => "OFF", "last_heartbeat" => 0];
$data["lamp"] = $data["lamp"] ?? "OFF";
$data["led"] = $data["led"] ?? "OFF";

$response = ["success" => false];
if (isset($_GET["lamp"])) {
    $lamp = strtoupper(trim($_GET["lamp"]));
    if (in_array($lamp, ["ON", "OFF"], true)) {
        $data["lamp"] = $lamp;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        $response = ["success" => true, "lamp" => $lamp];
    } else {
        $response = ["success" => false, "error" => "Invalid lamp value"];
    }
}

if (isset($_GET["led"])) {
    $led = strtoupper(trim($_GET["led"]));
    if (in_array($led, ["ON", "OFF"], true)) {
        $data["led"] = $led;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        $response = ["success" => true, "led" => $led];
    } else {
        $response = ["success" => false, "error" => "Invalid led value"];
    }
}

if (isset($_GET["heartbeat"])) {
    $data["last_heartbeat"] = time();
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    $response = ["success" => true, "heartbeat" => $data["last_heartbeat"]];
}

if (isset($_GET["ajax"]) || isset($_GET["lamp"]) || isset($_GET["led"]) || isset($_GET["heartbeat"])) {
    header('Content-Type: application/json');
    echo json_encode(array_merge($response, ["state" => $data]));
    exit;
}

// fake sensor data
$temp = rand(18, 45);
$child = rand(0, 1) ? "YES" : "NO";
$speed = rand(0, 120);

// ESP disconnect detection
$now = time();
$last = $data["last_heartbeat"] ?? 0;
$offline = ($now - $last > 10); // 10 sec timeout
$lastSeen = $last ? date('H:i:s', $last) . " (" . ($now - $last) . "s ago)" : "never";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0c0f1a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Car Safety System</title>
    <link rel="manifest" href="manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            background: radial-gradient(circle at top, #2f3c58 0%, #0c0f1a 62%);
            color: #fff;
            min-height: 100vh;
        }
        .glass {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.22);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
            border-radius: 18px;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-online { background: #4caf50; }
        .status-offline { background: #f44336; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="text-center mb-4">
        <h1 class="display-6">🚗 Car Dashboard</h1>
        <p class="text-muted">Bootstrap glass UI + JSON state sync</p>
    </div>

    <div class="row gy-3">
        <div class="col-md-4">
            <div class="p-3 glass">
                <h5>Sensor Data</h5>
                <p class="mb-1">🔥 Temperature: <strong id="tempValue"><?= $temp ?> °C</strong></p>
                <p class="mb-1">👶 Child in seat: <strong id="childValue"><?= $child ?></strong></p>
                <p class="mb-1">🚗 Speed: <strong id="speedValue"><?= $speed ?> km/h</strong></p>
                <p class="mb-1">💡 Lamp (GPIO 5): <strong id="lampValue"><?= $data["lamp"] ?></strong></p>
                <p class="mb-0">🔘 Integrated LED (GPIO 2): <strong id="ledValue"><?= $data["led"] ?></strong></p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 glass">
                <h5>System Status</h5>
                <p class="mb-1">Heartbeat: <strong id="heartbeatValue"><?= $lastSeen ?></strong></p>
                <p class="mb-1"><span id="connDot" class="status-dot <?= $offline ? 'status-offline' : 'status-online' ?>"></span>
                    <strong id="connText"><?= $offline ? 'OFFLINE' : 'ONLINE' ?></strong>
                </p>
                <?php if ($offline): ?>
                    <div class="alert alert-danger p-2 mt-2">🚨 DANGER! ESP DISCONNECTED.<br>You might have forgotten your child in the car.</div>
                <?php else: ?>
                    <div class="alert alert-success p-2 mt-2">✅ ESP connected and reporting.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 glass">
                <h5>Controls</h5>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="updateLamp('ON')">Turn ON</button>
                    <button class="btn btn-secondary" onclick="updateLamp('OFF')">Turn OFF</button>
                    <button class="btn btn-warning" onclick="sendHeartbeat()">Send Heartbeat</button>
                    <button class="btn btn-primary" onclick="updateLED('ON')">LED ON</button>
                    <button class="btn btn-secondary" onclick="updateLED('OFF')">LED OFF</button>
                    <button class="btn btn-danger" onclick="blinkEsp()">Blink External Lamp</button>
                    <button class="btn btn-warning" onclick="blinkIntegratedLed()">Blink Integrated LED</button>
                    <button id="installBtn" class="btn btn-info" style="display:none;">Install App</button>
                </div>
                <div id="actionResult" class="mt-3 text-white"></div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center text-muted small">data saved to <code>api/state.json</code></div>
</div>

<script>
    let deferredPrompt;
    const installBtn = document.getElementById('installBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        installBtn.style.display = 'block';
    });

    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        installBtn.style.display = 'none';
        deferredPrompt = null;
        document.getElementById('actionResult').innerText = outcome === 'accepted' ? 'App installed!' : 'Install declined.';
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
            .then(() => console.log('Service worker registered.'))
            .catch((err) => console.warn('Service worker failed:', err));
    }

    async function updateLamp(value) {
        const res = await fetch(`?lamp=${value}&ajax=1`);
        const data = await res.json();
        if (data.success) {
            document.getElementById('lampValue').innerText = data.state.lamp;
            document.getElementById('actionResult').innerText = `Lamp ${data.state.lamp} applied.`;
        } else {
            document.getElementById('actionResult').innerText = `Error: ${data.error || 'unknown'}`;
        }
    }

    async function updateLED(value) {
        const res = await fetch(`?led=${value}&ajax=1`);
        const data = await res.json();
        if (data.success) {
            document.getElementById('ledValue').innerText = data.state.led;
            document.getElementById('actionResult').innerText = `Integrated LED ${data.state.led} applied.`;
        } else {
            document.getElementById('actionResult').innerText = `Error: ${data.error || 'unknown'}`;
        }
    }

    async function sendHeartbeat() {
        const res = await fetch('?heartbeat=1&ajax=1');
        const data = await res.json();
        if (data.success) {
            const now = new Date();
            document.getElementById('heartbeatValue').innerText = `${now.toTimeString().split(' ')[0]} (just now)`;
            document.getElementById('connDot').className = 'status-dot status-online';
            document.getElementById('connText').innerText = 'ONLINE';
            document.getElementById('actionResult').innerText = 'Heartbeat sent.';
        } else {
            document.getElementById('actionResult').innerText = 'Heartbeat failed.';
        }
    }

    async function blinkEsp() {
        const res = await fetch('esp.php?blink=1');
        const text = await res.text();

        if (res.ok && text.trim() === 'UPDATED') {
            document.getElementById('actionResult').innerText = 'Blink command sent to ESP.';
        } else {
            document.getElementById('actionResult').innerText = 'Blink command failed: ' + text;
        }
    }

    async function blinkIntegratedLed() {
        const res = await fetch('esp.php?ledblink=1');
        const text = await res.text();

        if (res.ok && text.trim() === 'UPDATED') {
            document.getElementById('actionResult').innerText = 'Integrated LED blink command sent.';
        } else {
            document.getElementById('actionResult').innerText = 'Integrated LED blink command failed: ' + text;
        }
    }
</script>
</body>
</html>