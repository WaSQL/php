<?php
/**
 * Production-ready MQTT Client for PHP
 * Implements MQTT 3.1.1 protocol with proper error handling and reliability features
 */

class MQTTClient {
    private $config = [];
    private $socket = null;
    private $connected = false;
    private $buffer = '';
    private $packet_id = 1;
    private $last_ping = 0;
    private $keepalive = 60;
    private $running = false;
    private $message_handlers = [];
    private $debug = false;
    
    public function __construct($debug = false) {
        $this->debug = $debug;
    }
    
    /**
     * Configure MQTT broker connection
     */
    public function setConfig($host, $username = '', $password = '', $port = 1883, $keepalive = 60) {
        $this->config = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'client_id' => "MQTTClient" . uniqid(),
            'keepalive' => $keepalive
        ];
        $this->keepalive = $keepalive;
        return true;
    }
    
    /**
     * Connect to MQTT broker
     */
    public function connect() {
        if (empty($this->config['host'])) {
            throw new Exception("No host configured");
        }
        
        // Create socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new Exception("Failed to create socket: " . socket_strerror(socket_last_error()));
        }
        
        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 30, "usec" => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ["sec" => 30, "usec" => 0]);
        
        // Connect to broker
        if (!socket_connect($this->socket, $this->config['host'], $this->config['port'])) {
            $error = socket_strerror(socket_last_error($this->socket));
            socket_close($this->socket);
            throw new Exception("Failed to connect to broker: {$error}");
        }
        
        // Send CONNECT packet
        $this->sendConnectPacket();
        
        // Read CONNACK
        $connack = $this->readPacket(4);
        if (!$connack || strlen($connack) < 4) {
            throw new Exception("Failed to receive CONNACK");
        }
        
        $data = unpack('C4', $connack);
        if ($data[1] !== 0x20) {
            throw new Exception("Invalid CONNACK packet");
        }
        
        if ($data[4] !== 0) {
            $errors = [
                0x01 => "Connection refused - unacceptable protocol version",
                0x02 => "Connection refused - identifier rejected",
                0x03 => "Connection refused - server unavailable",
                0x04 => "Connection refused - bad username or password",
                0x05 => "Connection refused - not authorized"
            ];
            $error = $errors[$data[4]] ?? "Connection refused - unknown error ({$data[4]})";
            throw new Exception($error);
        }
        
        $this->connected = true;
        $this->last_ping = time();
        
        $this->log("Connected to MQTT broker");
        return true;
    }
    
    /**
     * Subscribe to a topic
     */
    public function subscribe($topic, $qos = 0) {
        if (!$this->connected) {
            throw new Exception("Not connected to broker");
        }
        
        $packet_id = $this->getNextPacketId();
        $payload = pack('n', $packet_id) . 
                   pack('n', strlen($topic)) . 
                   $topic . 
                   pack('C', $qos);
        
        $packet = pack('C', 0x82) . $this->encodeRemainingLength(strlen($payload)) . $payload;
        
        if (!$this->writeToSocket($packet)) {
            throw new Exception("Failed to send SUBSCRIBE packet");
        }
        
        // Read SUBACK
        $suback = $this->readPacket(5);
        if (!$suback || strlen($suback) < 5) {
            throw new Exception("Failed to receive SUBACK");
        }
        
        $data = unpack('C*', $suback);
        if ($data[1] !== 0x90) {
            throw new Exception("Invalid SUBACK packet");
        }
        
        if (end($data) === 0x80) {
            throw new Exception("Subscription failed - access denied");
        }
        
        $this->log("Subscribed to topic: {$topic}");
        return true;
    }
    
    /**
     * Publish a message to a topic
     */
    public function publish($topic, $message, $qos = 0, $retain = false) {
        if (!$this->connected) {
            throw new Exception("Not connected to broker");
        }
        
        $flags = 0x30; // PUBLISH packet type
        if ($retain) $flags |= 0x01;
        $flags |= ($qos << 1);
        
        $payload = pack('n', strlen($topic)) . $topic;
        
        if ($qos > 0) {
            $payload .= pack('n', $this->getNextPacketId());
        }
        
        $payload .= $message;
        
        $packet = pack('C', $flags) . $this->encodeRemainingLength(strlen($payload)) . $payload;
        
        if (!$this->writeToSocket($packet)) {
            throw new Exception("Failed to send PUBLISH packet");
        }
        
        $this->log("Published to topic: {$topic}");
        return true;
    }
    
    /**
     * Listen for messages with callback handling
     */
    public function listen($messageCallback = null, $timeout = null) {
        if (!$this->connected) {
            throw new Exception("Not connected to broker");
        }
        
        $this->running = true;
        $start_time = time();
        $message_count = 0;
        
        // Set socket to non-blocking for message loop
        socket_set_nonblock($this->socket);
        
        // Set up signal handlers for graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'stop']);
            pcntl_signal(SIGINT, [$this, 'stop']);
        }
        
        $this->log("Starting message listener...");
        
        while ($this->running && $this->connected) {
            // Check timeout
            if ($timeout && (time() - $start_time) >= $timeout) {
                $this->log("Listener timeout reached");
                break;
            }
            
            // Handle keepalive
            if ((time() - $this->last_ping) >= ($this->keepalive - 10)) {
                $this->sendPingReq();
            }
            
            // Process signals
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            // Read data from socket
            $data = socket_read($this->socket, 1024);
            
            if ($data === false) {
                $error = socket_last_error($this->socket);
                if ($error === SOCKET_EWOULDBLOCK || $error === SOCKET_EAGAIN) {
                    usleep(10000); // 10ms sleep
                    continue;
                } else {
                    $this->log("Socket error: " . socket_strerror($error));
                    $this->connected = false;
                    break;
                }
            }
            
            if (strlen($data) === 0) {
                usleep(10000);
                continue;
            }
            
            $this->buffer .= $data;
            
            // Process complete packets
            while ($this->processBuffer($messageCallback)) {
                $message_count++;
            }
        }
        
        $this->log("Message listener stopped. Processed {$message_count} messages.");
        return $message_count;
    }
    
    /**
     * Stop the listener
     */
    public function stop() {
        $this->running = false;
        $this->log("Stop signal received");
    }
    
    /**
     * Disconnect from broker
     */
    public function disconnect() {
        if ($this->connected && $this->socket) {
            // Send DISCONNECT packet
            $disconnect = pack('CC', 0xE0, 0x00);
            $this->writeToSocket($disconnect);
            $this->log("Sent DISCONNECT packet");
        }
        
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
        
        $this->connected = false;
        $this->buffer = '';
        $this->log("Disconnected from broker");
    }
    
    /**
     * Process incoming packets from buffer
     */
    private function processBuffer($messageCallback) {
        if (strlen($this->buffer) < 2) {
            return false;
        }
        
        $packet_type = ord($this->buffer[0]) >> 4;
        $flags = ord($this->buffer[0]) & 0x0F;
        
        // Parse remaining length
        list($remaining_length, $pos) = $this->decodeRemainingLength($this->buffer, 1);
        
        if ($remaining_length === false) {
            return false; // Need more data
        }
        
        $total_length = $pos + $remaining_length;
        
        if (strlen($this->buffer) < $total_length) {
            return false; // Wait for complete packet
        }
        
        // Extract packet
        $packet = substr($this->buffer, 0, $total_length);
        $this->buffer = substr($this->buffer, $total_length);
        
        // Process packet based on type
        switch ($packet_type) {
            case 3: // PUBLISH
                $this->handlePublish($packet, $pos, $flags, $messageCallback);
                break;
                
            case 12: // PINGREQ
                $this->sendPingResp();
                break;
                
            case 13: // PINGRESP
                $this->log("PINGRESP received");
                break;
                
            default:
                $this->log("Unhandled packet type: {$packet_type}");
        }
        
        return true;
    }
    
    /**
     * Handle PUBLISH packet
     */
    private function handlePublish($packet, $data_pos, $flags, $messageCallback) {
        if ($data_pos + 1 >= strlen($packet)) {
            return;
        }
        
        // Extract topic
        $topic_length = (ord($packet[$data_pos]) << 8) | ord($packet[$data_pos + 1]);
        $data_pos += 2;
        
        if ($data_pos + $topic_length >= strlen($packet)) {
            return;
        }
        
        $topic = substr($packet, $data_pos, $topic_length);
        $data_pos += $topic_length;
        
        // Handle packet ID for QoS > 0
        $qos = ($flags >> 1) & 0x03;
        $packet_id = 0;
        
        if ($qos > 0) {
            if ($data_pos + 1 >= strlen($packet)) {
                return;
            }
            $packet_id = (ord($packet[$data_pos]) << 8) | ord($packet[$data_pos + 1]);
            $data_pos += 2;
            
            // Send PUBACK for QoS 1
            if ($qos === 1) {
                $puback = pack('CCn', 0x40, 0x02, $packet_id);
                $this->writeToSocket($puback);
            }
        }
        
        // Extract payload
        $payload = substr($packet, $data_pos);
        
        $message = [
            'topic' => $topic,
            'payload' => $payload,
            'qos' => $qos,
            'retain' => ($flags & 0x01) ? true : false,
            'packet_id' => $packet_id,
            'timestamp' => time()
        ];
        
        $this->log("Received message on topic: {$topic}");
        
        // Call message callback if provided
        if ($messageCallback && is_callable($messageCallback)) {
            try {
                call_user_func($messageCallback, $message);
            } catch (Exception $e) {
                $this->log("Error in message callback: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Send CONNECT packet
     */
    private function sendConnectPacket() {
        $client_id = $this->config['client_id'];
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        
        // Calculate connect flags
        $flags = 0x02; // Clean session
        if (!empty($username)) {
            $flags |= 0x80; // Username flag
            if (!empty($password)) {
                $flags |= 0x40; // Password flag
            }
        }
        
        // Build payload
        $payload = pack('n', 4) . 'MQTT' .                    // Protocol name
                   pack('C', 4) .                             // Protocol level
                   pack('C', $flags) .                        // Connect flags
                   pack('n', $this->keepalive) .              // Keep alive
                   pack('n', strlen($client_id)) . $client_id; // Client ID
        
        if (!empty($username)) {
            $payload .= pack('n', strlen($username)) . $username;
            if (!empty($password)) {
                $payload .= pack('n', strlen($password)) . $password;
            }
        }
        
        $packet = pack('C', 0x10) . $this->encodeRemainingLength(strlen($payload)) . $payload;
        
        if (!$this->writeToSocket($packet)) {
            throw new Exception("Failed to send CONNECT packet");
        }
    }
    
    /**
     * Send PINGREQ packet
     */
    private function sendPingReq() {
        $packet = pack('CC', 0xC0, 0x00);
        if ($this->writeToSocket($packet)) {
            $this->last_ping = time();
            $this->log("Sent PINGREQ");
        }
    }
    
    /**
     * Send PINGRESP packet
     */
    private function sendPingResp() {
        $packet = pack('CC', 0xD0, 0x00);
        $this->writeToSocket($packet);
        $this->log("Sent PINGRESP");
    }
    
    /**
     * Encode remaining length according to MQTT spec
     */
    private function encodeRemainingLength($length) {
        $encoded = '';
        do {
            $byte = $length % 128;
            $length = intval($length / 128);
            if ($length > 0) {
                $byte |= 0x80;
            }
            $encoded .= chr($byte);
        } while ($length > 0);
        return $encoded;
    }
    
    /**
     * Decode remaining length from buffer
     */
    private function decodeRemainingLength($buffer, $offset) {
        $length = 0;
        $multiplier = 1;
        $pos = $offset;
        
        do {
            if ($pos >= strlen($buffer)) {
                return [false, $pos]; // Need more data
            }
            
            $byte = ord($buffer[$pos]);
            $length += ($byte & 0x7F) * $multiplier;
            $multiplier *= 128;
            $pos++;
            
            if ($multiplier > 128 * 128 * 128) {
                throw new Exception("Malformed remaining length");
            }
        } while (($byte & 0x80) !== 0);
        
        return [$length, $pos];
    }
    
    /**
     * Get next packet ID
     */
    private function getNextPacketId() {
        $id = $this->packet_id++;
        if ($this->packet_id > 65535) {
            $this->packet_id = 1;
        }
        return $id;
    }
    
    /**
     * Write data to socket with error handling
     */
    private function writeToSocket($data) {
        $written = socket_write($this->socket, $data);
        if ($written === false) {
            $this->log("Socket write error: " . socket_strerror(socket_last_error($this->socket)));
            $this->connected = false;
            return false;
        }
        return $written === strlen($data);
    }
    
    /**
     * Read exact number of bytes from socket
     */
    private function readPacket($bytes) {
        $data = '';
        $remaining = $bytes;
        $attempts = 0;
        
        while ($remaining > 0 && $attempts < 100) {
            $chunk = socket_read($this->socket, $remaining);
            if ($chunk === false) {
                return false;
            }
            if (strlen($chunk) === 0) {
                usleep(10000);
                $attempts++;
                continue;
            }
            $data .= $chunk;
            $remaining -= strlen($chunk);
        }
        
        return strlen($data) === $bytes ? $data : false;
    }
    
    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->debug) {
            echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        }
    }
    
    /**
     * Destructor - ensure clean disconnect
     */
    public function __destruct() {
        $this->disconnect();
    }
}

// Usage example and backward compatibility functions
function processMessage($message) {
    echo "Topic: {$message['topic']}" . PHP_EOL;
    echo "QoS: {$message['qos']}" . PHP_EOL;
    echo "Payload: {$message['payload']}" . PHP_EOL;
    echo "---" . PHP_EOL;
    return true;
}

// Backward compatibility wrapper functions
function mqttSetConfig($host, $username, $password, $port = 1883) {
    global $mqttClient;
    $mqttClient = new MQTTClient(true); // Enable debug
    return $mqttClient->setConfig($host, $username, $password, $port);
}

function mqttListen2Topic($topic, $processfunc) {
    global $mqttClient;
    
    if (!$mqttClient) {
        return "ERROR: MQTT client not configured";
    }
    
    try {
        $mqttClient->connect();
        $mqttClient->subscribe($topic);
        
        $callback = function($message) use ($processfunc) {
            if (function_exists($processfunc)) {
                call_user_func($processfunc, $message);
            }
        };
        
        return $mqttClient->listen($callback);
        
    } catch (Exception $e) {
        return "ERROR: " . $e->getMessage();
    } finally {
        $mqttClient->disconnect();
    }
}
?>