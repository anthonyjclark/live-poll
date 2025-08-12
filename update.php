<?php
// update.php: Handles poll data, voting, resets, and timer using a single txt file (data.txt)
header('Content-Type: application/json');

$poll_file = 'data.txt';

// File format:
// Line 0: question
// Line 1-6: options (6 lines)
// Line 7: votes (comma-separated, e.g. 0,0,0,0,0,0)
// Line 8: reset_version (timestamp)
// Line 9: timer (JSON: {"duration":60,"remaining":30,"state":"running","end_time":1234567890})

// Initialize file if missing
if (!file_exists($poll_file)) {
    $default = [
        'What is your favorite color?',
        'Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Orange',
        '0,0,0,0,0,0',
        time(),
        json_encode(['duration'=>0,'remaining'=>0,'state'=>'stopped','end_time'=>null])
    ];
    file_put_contents($poll_file, implode("\n", $default));
}

$lines = file($poll_file, FILE_IGNORE_NEW_LINES);
$question = $lines[0];
$options = array_slice($lines, 1, 6);
$votes = array_map('intval', explode(',', $lines[7]));
$reset_version = $lines[8];
$timer = json_decode($lines[9], true);

// Timer update logic
if ($timer['state'] === 'running' && $timer['end_time']) {
    $new_remaining = max(0, $timer['end_time'] - time());
    if ($new_remaining !== $timer['remaining']) {
        $timer['remaining'] = $new_remaining;
        if ($timer['remaining'] <= 0) {
            $timer['state'] = 'stopped';
            $timer['remaining'] = 0;
            $timer['end_time'] = null;
        }
        $lines[9] = json_encode($timer);
        file_put_contents($poll_file, implode("\n", $lines));
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['vote'])) {
        $option_indexes = $input['vote'];
        foreach ($option_indexes as $idx) {
            if (isset($votes[$idx])) {
                $votes[$idx]++;
            }
        }
        $lines[7] = implode(',', $votes);
        file_put_contents($poll_file, implode("\n", $lines));
        echo json_encode(['success'=>true]);
        exit;
    }
    if (isset($input['reset'])) {
        $lines[7] = '0,0,0,0,0,0';
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
    'reset_version' => $reset_version,
    'timer' => $timer
];
echo json_encode($data);
