<?php
/**
 * Export Orders to CSV
 */

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$status = $_GET['status'] ?? '';

// Generate CSV
$csv = export_orders_to_csv($status);

// Set headers for file download
$filename = 'orders_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV
echo $csv;

log_message("CSV export by admin - Status: " . ($status ?: 'all'), 'INFO');

exit;
?>
