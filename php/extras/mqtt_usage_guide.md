# MQTT Message Queue System - Usage Guide

## Overview

This system provides a robust, production-ready MQTT message processing architecture that decouples message reception from processing. It uses SQLite as a lightweight message buffer to prevent message loss and handle processing bottlenecks.

## Architecture

```
MQTT Broker → fmq_listen.php → SQLite Queue → fmq_process.php → Your Processing Logic
```

### Components

- **fmq_listen.php**: Fast MQTT message capture daemon
- **fmq_process.php**: Message processing worker
- **SQLite Database**: Reliable message queue with per-topic tables

## Features

- **Fault Tolerant**: Messages persist across crashes/restarts
- **Scalable**: Multiple listeners and processors per topic
- **Monitoring**: Built-in logging and statistics
- **Configurable**: Adjustable batch sizes and processing intervals
- **Memory Efficient**: Optimized for high-throughput scenarios

## Quick Start

### 1. Start Message Listener

```bash
# Listen to a specific MQTT topic
php fmq_listen.php <topic>

# Example: Listen to database change events
php fmq_listen.php dstdb
```

### 2. Start Message Processor

```bash
# Process messages with default settings (10 msgs/batch, 1s sleep)
php fmq_process.php <topic>

# Example: Process database changes
php fmq_process.php dstdb

# Custom batch size and sleep interval
php fmq_process.php <topic> <batch_size> <sleep_seconds>

# Example: Process 50 messages per batch, sleep 2 seconds between batches
php fmq_process.php dstdb 50 2
```

## Advanced Usage

### Multiple Topics

Run separate processes for different topics:

```bash
# Terminal 1: Listen to database events
php fmq_listen.php dstdb

# Terminal 2: Listen to sensor data  
php fmq_listen.php sensor/temperature

# Terminal 3: Listen to alerts
php fmq_listen.php alerts/critical

# Process each topic separately
php fmq_process.php dstdb 20 1
php fmq_process.php sensor/temperature 100 0.5  
php fmq_process.php alerts/critical 5 0
```

### High-Volume Processing

For high-throughput scenarios:

```bash
# Fast listener (no processing delay)
php fmq_listen.php high_volume_topic

# Multiple processors for parallel processing
php fmq_process.php high_volume_topic 100 0 &  # Process 1
php fmq_process.php high_volume_topic 100 0 &  # Process 2  
php fmq_process.php high_volume_topic 100 0 &  # Process 3
```

### Production Deployment

Use process managers like systemd or supervisor:

```ini
# /etc/supervisor/conf.d/mqtt_listener.conf
[program:mqtt_dstdb_listener]
command=php /path/to/fmq_listen.php dstdb
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
stderr_logfile=/var/log/mqtt_dstdb_listener.err.log
stdout_logfile=/var/log/mqtt_dstdb_listener.out.log

[program:mqtt_dstdb_processor]
command=php /path/to/fmq_process.php dstdb 25 1
directory=/path/to/project  
user=www-data
autostart=true
autorestart=true
stderr_logfile=/var/log/mqtt_dstdb_processor.err.log
stdout_logfile=/var/log/mqtt_dstdb_processor.out.log
```

## Configuration

### MQTT Connection

Edit connection details in `fmq_listen.php`:

```php
$mqtt->setConfig('your-mqtt-broker.com', 'username', 'password', 1883);
```

### Database Location

The SQLite database is created as `mqtt_queue.db` in the script directory. Each topic gets its own table:

- Topic: `dstdb` → Table: `msg_dstdb`
- Topic: `sensor/temperature` → Table: `msg_sensor_temperature`
- Topic: `alerts/critical` → Table: `msg_alerts_critical`

### Processing Logic

Customize the `processMessage()` function in `fmq_process.php` for your specific needs:

```php
function processMessage($msg, $logPrefix) {
    // Your custom processing logic here
    // Examples:
    // - Insert into main database
    // - Send HTTP webhooks  
    // - Write to files
    // - Forward to another queue
    
    return true; // Return true for success, false for retry
}
```

## Monitoring

### Built-in Logging

Both scripts provide timestamped logging:

```
[2025-01-15 10:30:45] [LISTEN-dstdb] Starting listener for topic: dstdb
[2025-01-15 10:30:45] [LISTEN-dstdb] Connected and subscribed successfully
[2025-01-15 10:30:46] [LISTEN-dstdb] Processed 100 messages
```

### Database Monitoring

Check queue status directly:

```sql
-- Check unprocessed messages by topic
SELECT 'msg_dstdb' as topic, COUNT(*) as pending FROM msg_dstdb WHERE processed = 0
UNION ALL  
SELECT 'msg_sensor_temperature' as topic, COUNT(*) as pending FROM msg_sensor_temperature WHERE processed = 0;

-- Check processing rate (messages per minute)
SELECT 
    datetime(received_at, 'unixepoch') as minute,
    COUNT(*) as messages
FROM msg_dstdb 
WHERE received_at > (strftime('%s', 'now') - 3600)  -- Last hour
GROUP BY datetime(received_at, 'unixepoch', 'start of minute')
ORDER BY minute DESC;
```

### Health Check Script

```bash
#!/bin/bash
# check_queue_health.sh

DB_FILE="mqtt_queue.db"
MAX_PENDING=1000

for table in $(sqlite3 $DB_FILE "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'msg_%'"); do
    pending=$(sqlite3 $DB_FILE "SELECT COUNT(*) FROM $table WHERE processed = 0")
    echo "Table: $table, Pending: $pending"
    
    if [ $pending -gt $MAX_PENDING ]; then
        echo "WARNING: High pending count for $table"
        # Send alert, restart processor, etc.
    fi
done
```

## Performance Tuning

### Listener Optimization

- **Single Purpose**: One listener per topic for isolation
- **Fast Processing**: Listener only writes to SQLite, no business logic
- **Memory Management**: Auto-cleanup of old processed messages

### Processor Optimization  

- **Batch Size**: Larger batches = higher throughput, higher memory usage
- **Sleep Interval**: Lower sleep = more responsive, higher CPU usage
- **Multiple Workers**: Run multiple processors for CPU-intensive work

### SQLite Optimization

The system automatically applies these optimizations:

- **WAL Mode**: Better concurrency for multiple readers/writers
- **Memory Mapping**: Faster I/O for large databases  
- **Synchronous=NORMAL**: Balance between speed and safety

## Error Handling

### Graceful Shutdown

Both scripts handle shutdown signals:

```bash
# Graceful shutdown
kill -TERM <pid>
kill -INT <pid>  # Ctrl+C

# View shutdown in logs
[2025-01-15 10:35:20] [LISTEN-dstdb] Received signal 15, shutting down gracefully...
[2025-01-15 10:35:21] [LISTEN-dstdb] Disconnected from MQTT broker
```

### Message Recovery

Failed messages are logged but marked as processed to prevent infinite retry. Implement custom retry logic in `processMessage()` if needed:

```php
function processMessage($msg, $logPrefix) {
    $maxRetries = 3;
    $retryCount = $msg['retry_count'] ?? 0;
    
    if ($retryCount >= $maxRetries) {
        logMessage($logPrefix, "Max retries reached for message, skipping");
        return true; // Skip message
    }
    
    // Your processing logic...
    // Return false to increment retry counter
}
```

## Troubleshooting

### Common Issues

**Queue Growing Too Fast**
- Increase processor batch size
- Add more processor workers  
- Optimize processing logic

**Messages Not Being Processed**
- Check if processor is running
- Verify table exists (run listener first)
- Check database permissions

**High Memory Usage**
- Reduce batch size in processor
- Enable SQLite auto-cleanup
- Monitor payload sizes

**Connection Issues**
- Verify MQTT broker credentials
- Check network connectivity
- Review firewall settings

### Debug Mode

Enable debug logging in the MQTT client:

```php
$mqtt = new MQTTClient(true); // Enable debug
```

This will show detailed MQTT protocol messages for troubleshooting connection issues.

## Integration Examples

### Web Dashboard

Create a simple monitoring dashboard:

```php
// dashboard.php
$db = new PDO('sqlite:mqtt_queue.db');

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'msg_%'")->fetchAll();

foreach ($tables as $table) {
    $topic = str_replace('msg_', '', $table['name']);
    $stats = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN processed = 0 THEN 1 ELSE 0 END) as pending,
        MAX(received_at) as last_message
    FROM {$table['name']}")->fetch();
    
    echo "<h3>Topic: {$topic}</h3>";
    echo "<p>Total: {$stats['total']}, Pending: {$stats['pending']}</p>";
    echo "<p>Last Message: " . date('Y-m-d H:i:s', $stats['last_message']) . "</p>";
}
```

### Webhook Integration

Forward messages to external services:

```php
function processMessage($msg, $logPrefix) {
    $webhook_url = "https://your-service.com/webhook";
    
    $payload = json_encode($msg);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $payload
        ]
    ]);
    
    $result = file_get_contents($webhook_url, false, $context);
    return $result !== false;
}
```

This system provides a solid foundation for reliable MQTT message processing in production environments.