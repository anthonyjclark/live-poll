<?php
header('Content-Type: application/json');

$poll_file = 'state.txt';

if (!file_exists($poll_file)) {
    $default = [
        'Make your selection',                     // Line 0: question
        '5',                                       // Line 1: number of options (2-5)
        'Option 1',                                // Line 2-6: options (always 5 lines)
        'Option 2',
        'Option 3',
        'Option 4',
        'Option 5',
        '0,0,0,0,0',                               // Line 7: votes (comma-separated, always 5 values)
        time(),                                    // Line 8: timestamp
        json_encode(['remaining'=>0,'running'=>0]) // Line 9: JSON: {"remaining":SECONDS,"running":0|1}
    ];
    file_put_contents($poll_file, implode("\n", $default));
}

$lines = file($poll_file, FILE_IGNORE_NEW_LINES);
$question = $lines[0];
$num_options = intval($lines[1]);
$options = array_slice($lines, 2, $num_options);
$votes = array_map('intval', explode(',', $lines[7]));
$timestamp = $lines[8];
$timer = json_decode($lines[9], true);

//
// Handle POST actions
//

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['vote'])) {
        $option_indexes = $input['vote'];
        foreach ($option_indexes as $idx) {
            if ($idx >= 0 && $idx < $num_options) {
                $votes[$idx]++;
            }
        }
        $lines[7] = implode(',', $votes);
        file_put_contents($poll_file, implode("\n", $lines));
        echo json_encode(['success'=>true]);
        exit;
    }

    if (isset($input['reset'])) {
        $lines[7] = implode(',', array_fill(0, 5, 0));
        $lines[8] = time();
        file_put_contents($poll_file, implode("\n", $lines));
        echo json_encode(['success'=>true]);
        exit;
    }

    if (isset($input['timer'])) {
        $timer = $input['timer'];
        $lines[9] = json_encode($timer);
        file_put_contents($poll_file, implode("\n", $lines));
        echo json_encode(['success'=>true]);
        exit;
    }
}

// GET: return poll state
$data = [
    'question' => $question,
    'options' => array_map(function($opt, $i) use ($votes) {
        return [
            'text' => $opt,
            'votes' => $votes[$i] ?? 0
        ];
    }, $options, array_keys($options)),
    'timestamp' => $timestamp,
    'timer' => $timer
];
echo json_encode($data);
