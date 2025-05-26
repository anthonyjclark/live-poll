<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$option_indexes = isset($data['option_indexes']) && is_array($data['option_indexes']) ? $data['option_indexes'] : null;

if ($option_indexes === null || count($option_indexes) === 0) {
    echo json_encode(['success' => false, 'error' => 'No option_indexes provided']);
    exit;
}

$votes_file = 'votes.txt';
$votes = file($votes_file, FILE_IGNORE_NEW_LINES);
foreach ($option_indexes as $idx) {
    if (isset($votes[$idx])) {
        $votes[$idx] = (string)(((int)$votes[$idx]) + 1);
    }
}
file_put_contents($votes_file, implode("\n", $votes) . "\n");

echo json_encode(['success' => true]);
?>
