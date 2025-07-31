<?php
require_once 'phpMQTT.php';

// Configuration
$host = "127.0.0.1";        //  host
$port = 1883;               // Default MQTT port  
$username = "";             // Set if authentication required
$password = "";             // Set if authentication required
$client_id = "phpMQTTClient" . rand();

// Create MQTT client
$mqtt = new bluerhinos\phpMQTT($host, $port, $client_id);

// Connect to broker
if ($mqtt->connect(true, NULL, $username, $password)) {
    echo "Connected to  broker\n";
    
    // Set up subscription callback
    function procmsg($topic, $msg) {
        echo "Topic: $topic, Message: $msg\n";
    }
    
    // Subscribe to topic
    $topics['your/topic/name'] = array("qos" => 0, "function" => "procmsg");
    $mqtt->subscribe($topics, 0);
    
    // Keep listening for messages
    while($mqtt->proc()) {
        // Process incoming messages
    }
    
    $mqtt->close();
} else {
    echo "Connection failed\n";
}
?>