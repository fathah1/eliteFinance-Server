<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finance Server Logs</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #0b0f1a;
            --panel: #111827;
            --panel-alt: #0f172a;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --accent: #22d3ee;
            --danger: #f87171;
            --border: #1f2937;
        }
        body {
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            background: radial-gradient(1200px 800px at 20% 10%, #101827 0%, #0b0f1a 50%, #05070d 100%);
            color: var(--text);
        }
        .container {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 20px;
            letter-spacing: 0.5px;
        }
        .sub {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 16px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        .panel {
            background: linear-gradient(180deg, var(--panel) 0%, var(--panel-alt) 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            min-height: 280px;
            display: flex;
            flex-direction: column;
        }
        .panel h2 {
            margin: 0 0 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 8px;
        }
        pre {
            background: #070a12;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            color: #cbd5f5;
            font-size: 12px;
            line-height: 1.45;
            overflow: auto;
            flex: 1;
            white-space: pre-wrap;
        }
        .status {
            display: flex;
            gap: 12px;
            align-items: center;
            font-size: 12px;
            color: var(--muted);
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 10px rgba(34, 211, 238, 0.8);
        }
        .dot.error {
            background: var(--danger);
            box-shadow: 0 0 10px rgba(248, 113, 113, 0.8);
        }
        button {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
        }
        .controls {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .small {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Finance Server Logs</h1>
    <div class="sub">Live view of error logs and API requests.</div>

    <div class="status">
        <div class="dot" id="statusDot"></div>
        <div id="statusText">Connecting...</div>
        <div id="lastUpdated"></div>
    </div>

    <div class="controls">
        <button type="button" id="pauseBtn">Pause</button>
        <button type="button" id="resumeBtn">Resume</button>
        <button type="button" id="refreshBtn">Refresh Now</button>
        <button type="button" id="clearBtn">Clear Logs</button>
    </div>
    <div class="small">Polling every 2 seconds.</div>

    <div class="grid">
        <div class="panel">
            <h2>Laravel Errors</h2>
            <div class="meta" id="laravelMeta">Waiting for data...</div>
            <pre id="laravelLog">Loading...</pre>
        </div>
        <div class="panel">
            <h2>API Requests</h2>
            <div class="meta" id="apiMeta">Waiting for data...</div>
            <pre id="apiLog">Loading...</pre>
        </div>
    </div>
</div>

<script>
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');
    const lastUpdated = document.getElementById('lastUpdated');
    const laravelLog = document.getElementById('laravelLog');
    const apiLog = document.getElementById('apiLog');
    const laravelMeta = document.getElementById('laravelMeta');
    const apiMeta = document.getElementById('apiMeta');
    const pauseBtn = document.getElementById('pauseBtn');
    const resumeBtn = document.getElementById('resumeBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const clearBtn = document.getElementById('clearBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let paused = false;
    let timer = null;

    const renderLog = (entries) => {
        if (!entries || entries.length === 0) {
            return 'No log entries yet.';
        }
        return entries.join('\n');
    };

    const updateStatus = (ok, message) => {
        statusDot.classList.toggle('error', !ok);
        statusText.textContent = message;
    };

    const fetchLogs = async () => {
        if (paused) {
            return;
        }

        try {
            updateStatus(true, 'Connected');
            const response = await fetch('logs/tail?lines=200', { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Request failed');
            }
            const data = await response.json();
            laravelLog.textContent = renderLog(data.laravel.entries);
            apiLog.textContent = renderLog(data.api_requests.entries);
            laravelMeta.textContent = data.laravel.updated_at ? `Updated: ${data.laravel.updated_at}` : 'No file yet.';
            apiMeta.textContent = data.api_requests.updated_at ? `Updated: ${data.api_requests.updated_at}` : 'No file yet.';
            lastUpdated.textContent = `Last refresh: ${new Date().toLocaleTimeString()}`;
        } catch (error) {
            updateStatus(false, 'Disconnected');
        }
    };

    const startPolling = () => {
        if (timer) {
            clearInterval(timer);
        }
        timer = setInterval(fetchLogs, 2000);
        fetchLogs();
    };

    pauseBtn.addEventListener('click', () => {
        paused = true;
        updateStatus(true, 'Paused');
    });

    resumeBtn.addEventListener('click', () => {
        paused = false;
        updateStatus(true, 'Connected');
        fetchLogs();
    });

    refreshBtn.addEventListener('click', fetchLogs);
    clearBtn.addEventListener('click', async () => {
        const ok = confirm('Clear both Laravel and API request logs?');
        if (!ok) {
            return;
        }
        try {
            const response = await fetch('logs/clear', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) {
                throw new Error('Clear failed');
            }
            fetchLogs();
        } catch (error) {
            updateStatus(false, 'Clear failed');
        }
    });

    startPolling();
</script>
</body>
</html>
