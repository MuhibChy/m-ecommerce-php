<?php
/**
 * Email Tracking Pixel
 * M-EcommerceCRM - Email Open Tracking
 */

require_once '../config/database.php';
require_once '../includes/email_manager.php';

$trackingId = $_GET['t'] ?? '';

if ($trackingId) {
    $emailManager = new EmailManager();
    $emailManager->trackOpen($trackingId);
}

// Return 1x1 transparent pixel
header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
?>
