<?php
include_once('common.php');
loadExtras('mqtt');

// Get topic from CLI argument
if ($argc < 2) {
    echo "Usage: php mqtt_listen.php <topic>\n";
    echo "Example: php mqtt_listen.php dstdb\n";
    exit(1);
}

$topic = $argv[1];
$logPrefix = "[LISTEN-{$topic}]";

// Dynamic table creation based on topic
function getTableName($topic) {
    return 'msg_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $topic);
}

function ensureTableExists($db, $topic) {
    $table = getTableName($topic);
    $db->exec("CREATE TABLE IF NOT EXISTS {$table} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        payload TEXT,
        received_at INTEGER,
        processed INTEGER DEFAULT 0,
        retry_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_processed ON {$table}(processed, id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_received ON {$table}(received_at)");
}

function logMessage($prefix, $message) {
    echo "[" . date('Y-m-d H:i:s') . "] {$prefix} {$message}\n";
}

function cleanupOldMessages($db, $table, $daysToKeep = 30) {
    $cutoff = time() - ($daysToKeep * 24 * 60 * 60);
    $stmt = $db->prepare("DELETE FROM {$table} WHERE processed = 1 AND received_at < ?");
    $deleted = $stmt->execute([$cutoff]);
    if ($deleted) {
        logMessage("[CLEANUP]", "Cleaned old processed messages from {$table}");
    }
}

// Signal handling for graceful shutdown
$shutdown = false;
function signalHandler($signal) {
    global $shutdown, $logPrefix;
    logMessage($logPrefix, "Received signal {$signal}, shutting down gracefully...");
    $shutdown = true;
}

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, 'signalHandler');
    pcntl_signal(SIGINT, 'signalHandler');
    pcntl_signal(SIGHUP, 'signalHandler');
}

logMessage($logPrefix, "Starting listener for topic: {$topic}");

try {
    // Setup SQLite with error handling
    $db = new PDO('sqlite:mqtt_queue.db', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Enable WAL mode for better concurrency
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA synchronous=NORMAL');
    $db->exec('PRAGMA temp_store=MEMORY');
    $db->exec('PRAGMA mmap_size=268435456'); // 256MB
    
    ensureTableExists($db, $topic);
    $table = getTableName($topic);
    
    logMessage($logPrefix, "Using table: {$table}");
    
    // Cleanup old messages on startup
    cleanupOldMessages($db, $table);
    
    // Prepare statement outside the loop for better performance
    $stmt = $db->prepare("INSERT INTO {$table} (payload, received_at) VALUES (?, ?)");
    
    // Setup MQTT
    $mqtt = new MQTTClient(false);
    $mqtt->setConfig('mydomain', 'myuser', 'mypass', 1883);
    $mqtt->connect();
    $mqtt->subscribe($topic);
    
    logMessage($logPrefix, "Connected and subscribed successfully");
    
    $messageCount = 0;
    $lastCleanup = time();
    
    // Message processing loop
    $mqtt->listen(function($msg) use ($stmt, $logPrefix, &$messageCount, &$shutdown) {
        if ($shutdown) {
            return false; // Stop processing
        }
        
        try {
            $payload = json_encode($msg, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                logMessage($logPrefix, "ERROR: Failed to encode message as JSON");
                return true;
            }
            
            $success = $stmt->execute([$payload, time()]);
            if ($success) {
                $messageCount++;
                if ($messageCount % 100 == 0) {
                    logMessage($logPrefix, "Processed {$messageCount} messages");
                }
            } else {
                logMessage($logPrefix, "ERROR: Failed to insert message into database");
            }
        } catch (Exception $e) {
            logMessage($logPrefix, "ERROR: Database error - " . $e->getMessage());
        }
        
        return true; // Continue processing
    });
    
} catch (Exception $e) {
    logMessage($logPrefix, "FATAL ERROR: " . $e->getMessage());
    exit(1);
} finally {
    // Cleanup
    if (isset($mqtt)) {
        $mqtt->disconnect();
        logMessage($logPrefix, "Disconnected from MQTT broker");
    }
    
    if (isset($db)) {
        $db = null;
    }
    
    logMessage($logPrefix, "Listener stopped. Total messages processed: " . ($messageCount ?? 0));
}
?>