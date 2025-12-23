<?php
/**
 * Decode/Encode Utility Functions for WaSQL
 *
 * Helper functions for the decode/encode controller
 *
 * @package    WaSQL
 * @subpackage Utilities
 * @version    2.0
 * @author     WaSQL Development Team
 */

/**
 * Logs decode/encode operations for audit trail and security monitoring
 *
 * Creates audit log entries and logs critical errors to PHP error log.
 *
 * @global array $USER Current user information array
 *
 * @param string $message Log message describing the operation or event
 * @param string $level   Log level: 'info', 'warning', or 'error' (default: 'info')
 *
 * @return void
 *
 * @since 2.0
 */
function decodeLog($message, $level = 'info'){
	global $USER;

	$username = isset($USER['username']) ? $USER['username'] : 'unknown';

	// Always log to file
	$tpath = getWaSQLPath('php/admin');
	$logfile = "{$tpath}/decode.log";
	$log_entry = array(
		'timestamp' => date('Y-m-d H:i:s'),
		'unixtime' => time(),
		'username' => $username,
		'message' => $message,
		'level' => $level,
		'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI'
	);
	$log_entry_json = encodeJSON($log_entry);
	appendFileContents($logfile, $log_entry_json . PHP_EOL);

	// Also log to PHP error log for critical errors
	if($level === 'error'){
		error_log("WaSQL Decode Utilities [{$username}]: {$message}");
	}
}

/**
 * Validates that uploaded file is within allowed size and type
 *
 * @param string $file_path Path to uploaded file
 * @param array $allowed_types Array of allowed MIME types
 * @param int $max_size Maximum file size in bytes (default: 5MB)
 *
 * @return array Result with 'valid' boolean and 'error' message if invalid
 *
 * @since 2.0
 */
function decodeValidateUpload($file_path, $allowed_types = array('image/png', 'image/jpeg', 'image/jpg', 'image/gif'), $max_size = 5242880){
	if(!file_exists($file_path)){
		return array('valid' => false, 'error' => 'File not found');
	}

	// Check file size
	$filesize = filesize($file_path);
	if($filesize > $max_size){
		return array('valid' => false, 'error' => 'File too large (max ' . number_format($max_size / 1048576, 1) . 'MB)');
	}

	// Check MIME type
	$mime = mime_content_type($file_path);
	if(!in_array($mime, $allowed_types)){
		return array('valid' => false, 'error' => 'Invalid file type: ' . $mime);
	}

	return array('valid' => true, 'error' => null);
}

/**
 * Safely truncate string for display with ellipsis
 *
 * @param string $str String to truncate
 * @param int $length Maximum length (default: 50)
 * @param string $suffix Suffix to append (default: '...')
 *
 * @return string Truncated string
 *
 * @since 2.0
 */
function decodeTruncate($str, $length = 50, $suffix = '...'){
	if(strlen($str) <= $length){
		return $str;
	}
	return substr($str, 0, $length) . $suffix;
}

/**
 * Format bytes to human readable size
 *
 * @param int $bytes Bytes to format
 * @param int $precision Decimal precision (default: 2)
 *
 * @return string Formatted size (e.g., "1.5 MB")
 *
 * @since 2.0
 */
function decodeFormatBytes($bytes, $precision = 2){
	$units = array('B', 'KB', 'MB', 'GB', 'TB');

	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);

	$bytes /= pow(1024, $pow);

	return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
