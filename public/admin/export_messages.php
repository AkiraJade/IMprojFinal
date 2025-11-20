<?php
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=customer_messages_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Date']);

// Get messages
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['phone'],
        $row['subject'],
        $row['message'],
        ucfirst($row['status']),
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>
