<?php
/**
 * Test script to verify BASE_URL detection
 * Access this file via browser to see the detected BASE_URL
 * Delete this file after testing
 */
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>BASE_URL Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>BASE_URL Detection Test</h1>
    
    <div class="info">
        <h3>Detected BASE_URL:</h3>
        <p><code><?php echo BASE_URL; ?></code></p>
    </div>
    
    <div class="info">
        <h3>Server Information:</h3>
        <ul>
            <li><strong>HTTP_HOST:</strong> <?php echo isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Not set'; ?></li>
            <li><strong>HTTPS:</strong> <?php echo isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'Not set'; ?></li>
            <li><strong>SERVER_PORT:</strong> <?php echo isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 'Not set'; ?></li>
            <li><strong>DOCUMENT_ROOT:</strong> <?php echo isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Not set'; ?></li>
            <li><strong>SCRIPT_NAME:</strong> <?php echo isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'Not set'; ?></li>
            <li><strong>SCRIPT_FILENAME:</strong> <?php echo isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : 'Not set'; ?></li>
            <li><strong>REQUEST_URI:</strong> <?php echo isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Not set'; ?></li>
        </ul>
    </div>
    
    <div class="info">
        <h3>Test CSS Link:</h3>
        <p><code>&lt;link rel="stylesheet" href="&lt;?php echo BASE_URL; ?&gt;assets/css/style.css"&gt;</code></p>
        <p>Resolves to: <code><?php echo BASE_URL; ?>assets/css/style.css</code></p>
    </div>
    
    <div class="info">
        <h3>Test JS Link:</h3>
        <p><code>&lt;script src="&lt;?php echo BASE_URL; ?&gt;assets/js/main.js"&gt;&lt;/script&gt;</code></p>
        <p>Resolves to: <code><?php echo BASE_URL; ?>assets/js/main.js</code></p>
    </div>
    
    <?php
    $cssPath = BASE_URL . 'assets/css/style.css';
    $jsPath = BASE_URL . 'assets/js/main.js';
    $cssExists = file_exists(__DIR__ . '/assets/css/style.css');
    $jsExists = file_exists(__DIR__ . '/assets/js/main.js');
    ?>
    
    <div class="<?php echo $cssExists ? 'success' : 'error'; ?>">
        <h3>File Check:</h3>
        <ul>
            <li>CSS File (<?php echo __DIR__ . '/assets/css/style.css'; ?>): <?php echo $cssExists ? '✓ EXISTS' : '✗ NOT FOUND'; ?></li>
            <li>JS File (<?php echo __DIR__ . '/assets/js/main.js'; ?>): <?php echo $jsExists ? '✓ EXISTS' : '✗ NOT FOUND'; ?></li>
        </ul>
    </div>
    
    <div class="info">
        <h3>Instructions:</h3>
        <ol>
            <li>Check if the BASE_URL looks correct for your installation</li>
            <li>Verify that CSS and JS file paths resolve correctly</li>
            <li>If BASE_URL is incorrect, you may need to manually set it in config/config.php</li>
            <li><strong>Delete this file after testing for security</strong></li>
        </ol>
    </div>
</body>
</html>

