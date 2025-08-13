<?php
// Get the image URL from the query parameter
$imageUrl = isset($_GET['fetch']) ? $_GET['fetch'] : '';

// Validate URL
if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid or missing image URL');
}

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Note: Use with caution
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute cURL request
$imageData = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    http_response_code(500);
    exit('cURL error: ' . curl_error($ch));
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get content type
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Close cURL session
curl_close($ch);

// Check if request was successful
if ($httpCode != 200) {
    http_response_code($httpCode);
    exit('Failed to fetch image: HTTP ' . $httpCode);
}

// Set content type header
header('Content-Type: ' . $contentType);

// Output the image data
echo $imageData;
?>