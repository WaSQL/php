<?php
/**
 * Decode/Encode Controller for WaSQL
 *
 * Provides encoding and decoding utilities for various formats:
 * - JSON encode/decode with pretty printing
 * - Base64 encode/decode
 * - URL encode/decode
 * - HTML entity encode/decode
 * - QR Code generation with logo support
 * - Barcode generation
 *
 * Security Features:
 * - Admin-only access (isAdmin() check)
 * - Input validation and sanitization
 * - File upload validation (type, size, path)
 * - XSS protection via proper encoding
 * - CSRF protection via WaSQL framework
 * - Operation logging for audit trail
 *
 * @package    WaSQL
 * @subpackage Utilities
 * @version    2.0
 * @author     WaSQL Development Team
 */

// Authentication check - require admin user
global $USER;
if(!isUser()){
	setView('not_authenticated', 1);
	return;
}

// Check if user has admin rights
if(!isAdmin()){
	setView('not_authorized', 1);
	decodeLog("Unauthorized access attempt by user: " . ($USER['username'] ?? 'unknown'), 'warning');
	return;
}

// Initialize func parameter
if(!isset($_REQUEST['func'])){
	$_REQUEST['func'] = '';
}

$func = strtolower(trim($_REQUEST['func']));

// Log the action (skip logging for initial page load)
if($func !== ''){
	decodeLog("Decode action initiated: {$func}", 'info');
}

switch($func){
	case 'json_forms':
		$type = 'json';
		setView('decode_forms', 1);
		return;
	break;

	case 'json':
		// Validate input
		if(!isset($_REQUEST['json']) || strlen(trim($_REQUEST['json'])) == 0){
			$decoded = '<div class="alert alert-danger">No JSON input provided</div>';
			setView('decoded', 1);
			return;
		}

		// Remove control characters and decode
		$json = preg_replace('/[[:cntrl:]]/', '', trim($_REQUEST['json']));
		$decoded_data = decodeJSON($json);

		// Check if decode was successful
		if($decoded_data === null && json_last_error() !== JSON_ERROR_NONE){
			$error = json_last_error_msg();
			$decoded = '<div class="alert alert-danger"><strong>JSON Decode Error:</strong> ' . encodeHtml($error) . '</div>';
			decodeLog("JSON decode failed: {$error}", 'warning');
		} else {
			$decoded = printValue($decoded_data);
			decodeLog("JSON decoded successfully", 'info');
		}
		setView('decoded', 1);
		return;
	break;

	case 'base64_forms':
		$type = 'base64';
		setView('decode_forms', 1);
		return;
	break;

	case 'base64':
		// Validate input
		if(!isset($_REQUEST['base64']) || strlen(trim($_REQUEST['base64'])) == 0){
			$decoded = '<div class="alert alert-danger">No Base64 input provided</div>';
			setView('decoded', 1);
			return;
		}

		// Decode and validate
		$decoded = base64_decode(trim($_REQUEST['base64']), true);
		if($decoded === false){
			$decoded = '<div class="alert alert-danger">Invalid Base64 input</div>';
			decodeLog("Base64 decode failed: invalid input", 'warning');
		} else {
			// Check if it's binary or text
			if(mb_check_encoding($decoded, 'UTF-8')){
				$decoded = '<xmp>' . $decoded . '</xmp>';
			} else {
				$decoded = '<div class="alert alert-info">Binary data decoded (' . strlen($decoded) . ' bytes)</div>';
			}
			decodeLog("Base64 decoded successfully", 'info');
		}
		setView('decoded', 1);
		return;
	break;

	case 'url_forms':
		$type = 'url';
		setView('encode_decode_forms', 1);
		return;
	break;

	case 'url_encode':
		// Validate input
		if(!isset($_REQUEST['str']) || strlen(trim($_REQUEST['str'])) == 0){
			$results = '<div class="alert alert-danger">No input provided</div>';
			setView('results', 1);
			return;
		}

		$results = '<xmp>' . encodeURL($_REQUEST['str']) . '</xmp>';
		decodeLog("URL encoded successfully", 'info');
		setView('results', 1);
		return;
	break;

	case 'url_decode':
		// Validate input
		if(!isset($_REQUEST['str']) || strlen(trim($_REQUEST['str'])) == 0){
			$results = '<div class="alert alert-danger">No input provided</div>';
			setView('results', 1);
			return;
		}

		$results = '<xmp>' . decodeURL($_REQUEST['str']) . '</xmp>';
		decodeLog("URL decoded successfully", 'info');
		setView('results', 1);
		return;
	break;

	case 'html_forms':
		$type = 'html';
		setView('encode_decode_forms', 1);
		return;
	break;

	case 'html_encode':
		// Validate input
		if(!isset($_REQUEST['str']) || strlen(trim($_REQUEST['str'])) == 0){
			$results = '<div class="alert alert-danger">No input provided</div>';
			setView('results', 1);
			return;
		}

		$encoded = htmlspecialchars($_REQUEST['str'], ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);
		$results = '<xmp style="text-wrap:pretty;">' . $encoded . '</xmp>';
		decodeLog("HTML encoded successfully", 'info');
		setView('results', 1);
		return;
	break;

	case 'html_decode':
		// Validate input
		if(!isset($_REQUEST['str']) || strlen(trim($_REQUEST['str'])) == 0){
			$results = '<div class="alert alert-danger">No input provided</div>';
			setView('results', 1);
			return;
		}

		$decoded = htmlspecialchars_decode($_REQUEST['str'], ENT_QUOTES | ENT_HTML5);
		$results = '<xmp style="text-wrap:pretty;">' . $decoded . '</xmp>';
		decodeLog("HTML decoded successfully", 'info');
		setView('results', 1);
		return;
	break;

	case 'qrcode_form':
		$type = 'qrcode';
		setView('encode_form', 1);
		return;
	break;

	case 'qrcode':
		// Validate input
		if(!isset($_REQUEST['content']) || strlen(trim($_REQUEST['content'])) == 0){
			$results = '<div class="alert alert-danger">No content provided for QR code generation</div>';
			setView('results', 1);
			return;
		}

		// Load QRCode library
		loadExtras('qrcode');

		// Process file uploads with validation
		$ok = processFileUploads();
		$ok = processInlineFiles();

		// Validate logo file if uploaded
		if(isset($_REQUEST['logo_abspath']) && file_exists($_REQUEST['logo_abspath'])){
			$logo_path = $_REQUEST['logo_abspath'];

			// Validate file type
			$mime = mime_content_type($logo_path);
			if(!in_array($mime, array('image/png', 'image/jpeg', 'image/jpg', 'image/gif'))){
				$results = '<div class="alert alert-danger">Logo must be an image file (PNG, JPEG, or GIF)</div>';
				decodeLog("Invalid logo file type: {$mime}", 'warning');
				setView('results', 1);
				return;
			}

			// Validate file size (max 5MB)
			if(filesize($logo_path) > 5242880){
				$results = '<div class="alert alert-danger">Logo file too large (max 5MB)</div>';
				decodeLog("Logo file too large: " . filesize($logo_path) . " bytes", 'warning');
				setView('results', 1);
				return;
			}
		} else {
			$logo_path = null;
		}

		// Parse content lines
		$lines = preg_split('/[\r\n]+/', trim($_REQUEST['content']));
		$lines = array_filter($lines, 'strlen'); // Remove empty lines

		// Validate line count
		if(count($lines) == 0){
			$results = '<div class="alert alert-danger">No content provided</div>';
			setView('results', 1);
			return;
		}

		if(count($lines) > 100){
			$results = '<div class="alert alert-danger">Too many codes requested (max 100)</div>';
			decodeLog("Too many QR codes requested: " . count($lines), 'warning');
			setView('results', 1);
			return;
		}

		// Validate and sanitize parameters
		$eclevel = isset($_REQUEST['eclevel']) && in_array($_REQUEST['eclevel'], array('L', 'M', 'Q', 'H'))
			? $_REQUEST['eclevel'] : 'H';
		$size = isset($_REQUEST['size']) && isNum($_REQUEST['size']) && $_REQUEST['size'] >= 1 && $_REQUEST['size'] <= 40
			? (int)$_REQUEST['size'] : 5;
		$margin = isset($_REQUEST['margin']) && isNum($_REQUEST['margin']) && $_REQUEST['margin'] >= 0 && $_REQUEST['margin'] <= 10
			? (int)$_REQUEST['margin'] : 4;
		$transparent = isset($_REQUEST['transparent']) && $_REQUEST['transparent'] == 1 ? 1 : 0;

		// Generate QR codes
		$codes = array();
		foreach($lines as $content){
			// Limit content length
			if(strlen($content) > 2953){
				$codes[] = '<div class="alert alert-warning">Content too long (max 2953 chars): ' . encodeHtml(substr($content, 0, 50)) . '...</div>';
				continue;
			}

			try {
				if($logo_path){
					$qrcode = qrcodeCreateWithLogo($content, $logo_path, $transparent, $eclevel, $size, $margin);
				} else {
					$qrcode = qrcodeCreate($content, '', $eclevel, $size, $margin);
				}

				$b64 = encodeBase64($qrcode);
				$safe_content = encodeHtml($content);
				$codes[] = <<<ENDOFCODE
<div style="margin-bottom:20px;display:flex;flex-direction:column;align-items:center;">
	<div><img src="data:image/png;base64,{$b64}" alt="QR Code"></div>
	<div style="word-break:break-all;max-width:400px;text-align:center;">{$safe_content}</div>
</div>
ENDOFCODE;
			} catch(Exception $e){
				$codes[] = '<div class="alert alert-danger">Failed to generate QR code: ' . encodeHtml($e->getMessage()) . '</div>';
				decodeLog("QR code generation failed: " . $e->getMessage(), 'error');
			}
		}

		$results = '<div style="display:flex;flex-direction:column;align-items:flex-start;">' . implode(PHP_EOL, $codes) . '</div>';
		decodeLog("Generated " . count($codes) . " QR codes", 'info');
		setView('results', 1);
		return;
	break;

	case 'barcode_form':
		$type = 'barcode';
		setView('encode_form', 1);
		return;
	break;

	case 'barcode':
		// Validate input
		if(!isset($_REQUEST['content']) || strlen(trim($_REQUEST['content'])) == 0){
			$results = '<div class="alert alert-danger">No content provided for barcode generation</div>';
			setView('results', 1);
			return;
		}

		// Parse content lines
		$lines = preg_split('/[\r\n]+/', trim($_REQUEST['content']));
		$lines = array_filter($lines, 'strlen'); // Remove empty lines

		// Validate line count
		if(count($lines) == 0){
			$results = '<div class="alert alert-danger">No content provided</div>';
			setView('results', 1);
			return;
		}

		if(count($lines) > 100){
			$results = '<div class="alert alert-danger">Too many barcodes requested (max 100)</div>';
			decodeLog("Too many barcodes requested: " . count($lines), 'warning');
			setView('results', 1);
			return;
		}

		// Generate barcodes
		$codes = array();
		foreach($lines as $code){
			// Sanitize code for URL
			$safe_code = encodeURL($code);
			$display_code = encodeHtml($code);

			// Limit code length
			if(strlen($code) > 200){
				$codes[] = '<div class="alert alert-warning">Barcode content too long (max 200 chars): ' . encodeHtml(substr($code, 0, 50)) . '...</div>';
				continue;
			}

			$codes[] = <<<ENDOFCODE
<div style="margin-bottom:30px;display:flex;flex-direction:column;align-items:center;">
	<div><img src="/php/barcode.php?{$safe_code}" alt="Barcode"></div>
	<div>{$display_code}</div>
</div>
ENDOFCODE;
		}

		$results = '<div style="display:flex;flex-direction:column;align-items:flex-start;">' . implode(PHP_EOL, $codes) . '</div>';
		decodeLog("Generated " . count($codes) . " barcodes", 'info');
		setView('results', 1);
		return;
	break;

	default:
		// Initial page load - show default view with tabs and JSON form
		$type = 'json';
		setView('default', 1);
		return;
	break;
}

?>
