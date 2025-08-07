<?php
include_once('common.php');

// Get topic from CLI argument
if ($argc < 2) {
    echo "Usage: php mqtt_process.php <topic> [batch_size] [sleep_seconds]\n";
    echo "Example: php mqtt_process.php dstdb 10 1\n";
    exit(1);
}

$topic = $argv[1];
$batchSize = isset($argv[2]) ? (int)$argv[2] : 10;
$sleepSeconds = isset($argv[3]) ? (int)$argv[3] : 1;
$logPrefix = "[PROCESS-{$topic}]";

// Validation
if ($batchSize < 1 || $batchSize > 1000) {
    echo "ERROR: batch_size must be between 1 and 1000\n";
    exit(1);
}

if ($sleepSeconds < 0 || $sleepSeconds > 60) {
    echo "ERROR: sleep_seconds must be between 0 and 60\n";
    exit(1);
}

function getTableName($topic) {
    return 'msg_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $topic);
}

function logMessage($prefix, $message) {
    echo "[" . date('Y-m-d H:i:s') . "] {$prefix} {$message}\n";
}

function processMessage($msg, $logPrefix) {
    try {
        // Handle double-encoded JSON
        if (!isset($msg['payload'])) {
            $msg = decodeJSON($msg);
        }
        
        $msg['record'] = [];
        $msg['changes'] = [];
        
        // Handle nested JSON payload
        if (!isset($msg['payload']['fields'])) {
            if (is_string($msg['payload'])) {
                $msg['payload'] = decodeJSON($msg['payload']);
            }
        }
        
        // Validate payload structure
        if (!isset($msg['payload']['fields']) || !is_array($msg['payload']['fields'])) {
            logMessage($logPrefix, "WARNING: Invalid payload structure, skipping message");
            return false;
        }
        
        // Process fields
        foreach ($msg['payload']['fields'] as $info) {
            if (!isset($info['fieldName'], $info['afterValue'], $info['beforeValue'])) {
                logMessage($logPrefix, "WARNING: Incomplete field info, skipping field");
                continue;
            }
            
            $field = strtolower(trim($info['fieldName']));
            if (empty($field)) {
                continue;
            }
            
            // Track changes
            if ($info['afterValue'] != $info['beforeValue']) {
                $msg['changes'][$field] = [
                    'old_value' => $info['beforeValue'],
                    'new_value' => $info['afterValue']
                ];
            }
            
            $msg['record'][$field] = trim($info['afterValue']);
        }
        
        // Cleanup payload to save memory
        unset($msg['payload']);
        
        // TODO: Replace with your actual processing logic
        // Examples:
        // - Insert into main database
        // - Send to external API
        // - Write to file system
        // - Push to another queue
        
        // For now, just output (remove in production)
        echo printValue($msg) . PHP_EOL;
        
        return true;
        
    } catch (Exception $e) {
        logMessage($logPrefix, "ERROR processing message: " . $e->getMessage());
        return false;
    }
}

// Signal handling for graceful shutdown
$shutdown = false;
function signalHandler($signal) {
    global $shutdown, $logPrefix;
    logMessage($logPrefix, "Received signal {$signal}, finishing current batch then shutting down...");
    $shutdown = true;
}

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, 'signalHandler');
    pcntl_signal(SIGINT, 'signalHandler');
    pcntl_signal(SIGHUP, 'signalHandler');
}

logMessage($logPrefix, "Starting processor for topic: {$topic} (batch_size: {$batchSize}, sleep: {$sleepSeconds}s)");

try {
    $table = getTableName($topic);
    
    // Setup database connection
    $db = new PDO('sqlite:mqtt_queue.db', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Enable WAL mode for better concurrency
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA synchronous=NORMAL');
    
    // Verify table exists
    $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    if (!$stmt->fetch()) {
        logMessage($logPrefix, "ERROR: Table {$table} does not exist. Run listener first.");
        exit(1);
    }
    
    logMessage($logPrefix, "Using table: {$table}");
    
    // Prepare statements
    $selectStmt = $db->prepare("SELECT * FROM {$table} WHERE processed = 0 ORDER BY id LIMIT ?");
    $updateStmt = $db->prepare("UPDATE {$table} SET processed = 1 WHERE id = ?");
    $retryStmt = $db->prepare("UPDATE {$table} SET retry_count = retry_count + 1 WHERE id = ?");
    
    $processedCount = 0;
    $errorCount = 0;
    $lastStatsTime = time();
    $emptyLoops = 0;
    
    logMessage($logPrefix, "Starting processing loop...");
    
    while (!$shutdown) {
        // Handle signals
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
        
        $batchProcessed = 0;
        $batchErrors = 0;
        
        try {
            // Get batch of unprocessed messages
            $selectStmt->execute([$batchSize]);
            $messages = $selectStmt->fetchAll();
            
            if (empty($messages)) {
                $emptyLoops++;
                // Adaptive sleep - sleep longer when no messages
                $adaptiveSleep = min($sleepSeconds * (1 + $emptyLoops * 0.1), 10);
                sleep((int)$adaptiveSleep);
                continue;
            }
            
            $emptyLoops = 0; // Reset counter when we have messages
            
            // Process each message in the batch
            foreach ($messages as $row) {
                if ($shutdown) break;
                
                $msg = json_decode($row['payload'], true);
                if ($msg === null) {
                    logMessage($logPrefix, "ERROR: Invalid JSON in message ID {$row['id']}");
                    $updateStmt->execute([$row['id']]); // Mark as processed to avoid infinite retry
                    $batchErrors++;
                    continue;
                }
                
                // Process the message
                $success = processMessage($msg, $logPrefix);
                
                if ($success) {
                    // Mark as processed
                    $updateStmt->execute([$row['id']]);
                    $batchProcessed++;
                } else {
                    // Increment retry counter (for monitoring)
                    $retryStmt->execute([$row['id']]);
                    $batchErrors++;
                    
                    // For now, mark as processed to avoid infinite retry
                    // TODO: Implement proper retry logic with max attempts
                    $updateStmt->execute([$row['id']]);
                }
            }
            
            $processedCount += $batchProcessed;
            $errorCount += $batchErrors;
            
            // Log stats periodically
            if (time() - $lastStatsTime >= 60) { // Every minute
                logMessage($logPrefix, "Stats: Processed={$processedCount}, Errors={$errorCount}, Batch={$batchProcessed}");
                $lastStatsTime = time();
            }
            
        } catch (Exception $e) {
            logMessage($logPrefix, "ERROR in processing loop: " . $e->getMessage());
            $errorCount++;
        }
        
        // Sleep between batches only if we have time
        if ($sleepSeconds > 0 && !$shutdown) {
            sleep($sleepSeconds);
        }
    }
    
} catch (Exception $e) {
    logMessage($logPrefix, "FATAL ERROR: " . $e->getMessage());
    exit(1);
} finally {
    // Cleanup
    if (isset($db)) {
        $db = null;
    }
    
    logMessage($logPrefix, "Processor stopped. Total processed: {$processedCount}, Errors: {$errorCount}");
}
?>