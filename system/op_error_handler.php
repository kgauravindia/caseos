<?php
/**
 * CaseOS Custom Error & Exception Handler
 * Provides a clean, modern, secure error design across the entire application.
 */

if (!function_exists('render_caseos_error')) {
    function render_caseos_error($title, $message, $details = null, $http_code = 500) {
        // Clear any previous output buffers to avoid broken HTML
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($http_code);
            header('Content-Type: text/html; charset=utf-8');
        }

        global $CONFIG, $base_url, $inst_name;
        $app_title = !empty($inst_name) ? $inst_name : (!empty($CONFIG['app_name']) ? $CONFIG['app_name'] : 'CaseOS');
        $site_base = !empty($base_url) ? $base_url : (!empty($CONFIG['base_url']) ? $CONFIG['base_url'] : '/');

        // Determine if user has developer role or is in debug mode
        $is_dev = false;
        if (isset($_SESSION['user_type']) && strtoupper($_SESSION['user_type']) === 'DEV') {
            $is_dev = true;
        } elseif (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'], true)) {
            $is_dev = true;
        }

        // Color theme based on HTTP status
        $badge_color = 'danger';
        $icon_name = 'alert-triangle';
        if ($http_code === 404) {
            $badge_color = 'info';
            $icon_name = 'compass';
        } elseif ($http_code === 403) {
            $badge_color = 'warning';
            $icon_name = 'shield-off';
        } elseif ($http_code >= 500) {
            $badge_color = 'danger';
            $icon_name = 'server';
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($app_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
            color: #334155;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .code-block {
            background: #0f172a;
            color: #f1f5f9;
            border-radius: 0.75rem;
            padding: 1rem;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8rem;
            max-height: 220px;
            overflow-y: auto;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .btn-premium {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            transition: all 0.2s ease;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="error-card p-4 p-md-5 text-center">
        <!-- Status Icon -->
        <div class="icon-circle bg-<?= $badge_color ?> bg-opacity-10 text-<?= $badge_color ?>">
            <i data-feather="<?= $icon_name ?>" style="width: 36px; height: 36px;"></i>
        </div>

        <div class="mb-2">
            <span class="badge bg-<?= $badge_color ?> bg-opacity-15 text-<?= $badge_color ?> px-3 py-1 rounded-pill fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                Status Code <?= (int)$http_code ?>
            </span>
        </div>

        <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($title) ?></h2>
        <p class="text-secondary mb-4" style="line-height: 1.6; font-size: 0.95rem;">
            <?= $message ?>
        </p>

        <!-- Technical Diagnostic Drawer (Developer mode or debug) -->
        <?php if ($details !== null): ?>
            <div class="mb-4 text-start">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <a class="small text-decoration-none fw-semibold text-muted d-inline-flex align-items-center" data-bs-toggle="collapse" href="#diagnosticDetails" role="button">
                        <i data-feather="terminal" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= $is_dev ? 'Technical Diagnostics' : 'System Information' ?>
                        <i data-feather="chevron-down" class="ms-1" style="width: 14px; height: 14px;"></i>
                    </a>
                    <?php if ($is_dev): ?>
                        <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none p-0 small" onclick="copyDiagnostics()">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i> Copy
                        </button>
                    <?php endif; ?>
                </div>
                <div class="collapse <?= $is_dev ? 'show' : '' ?>" id="diagnosticDetails">
                    <pre class="code-block mb-0" id="diagOutput"><?= htmlspecialchars($details) ?></pre>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-2 pt-2 border-top">
            <a href="<?= htmlspecialchars($site_base) ?>" class="btn btn-primary btn-premium shadow-sm">
                <i data-feather="home" class="me-1" style="width: 16px; height: 16px;"></i> Dashboard
            </a>
            <button onclick="history.back()" class="btn btn-light btn-premium border text-dark">
                <i data-feather="arrow-left" class="me-1" style="width: 16px; height: 16px;"></i> Go Back
            </button>
            <button onclick="window.location.reload()" class="btn btn-light btn-premium border text-dark">
                <i data-feather="refresh-cw" class="me-1" style="width: 16px; height: 16px;"></i> Try Again
            </button>
        </div>

        <div class="mt-4 pt-3 text-muted small border-top" style="font-size: 0.75rem;">
            <?= htmlspecialchars($app_title) ?> Platform &bull; <?= date('Y') ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();
        function copyDiagnostics() {
            var txt = document.getElementById('diagOutput');
            if (txt) {
                navigator.clipboard.writeText(txt.innerText).then(function() {
                    alert('Diagnostic information copied to clipboard.');
                });
            }
        }
    </script>
</body>
</html>
        <?php
        exit;
    }
}

// Global Exception Handler
if (!function_exists('caseos_exception_handler')) {
    function caseos_exception_handler(Throwable $e) {
        $msg = "An unexpected system exception occurred while processing your request. Please try again or contact support if the issue persists.";
        $details = "Exception: " . get_class($e) . "\nMessage: " . $e->getMessage() . "\nFile: " . $e->getFile() . ":" . $e->getLine() . "\n\nStack Trace:\n" . $e->getTraceAsString();
        render_caseos_error("Application Exception", $msg, $details, 500);
    }
}

// Global Shutdown Handler for Fatal Errors
if (!function_exists('caseos_shutdown_handler')) {
    function caseos_shutdown_handler() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $msg = "A critical system error occurred. Our team has been notified.";
            $details = "Fatal Error: " . $error['message'] . "\nFile: " . $error['file'] . ":" . $error['line'];
            render_caseos_error("Critical System Error", $msg, $details, 500);
        }
    }
}

// Register global handlers
set_exception_handler('caseos_exception_handler');
register_shutdown_function('caseos_shutdown_handler');
