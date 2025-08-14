<?php

header('Content-Type: application/json');

//
// Define the default state
//

$state_file = 'state.json';

if (!file_exists($state_file)) {

    $max_questions = 5;

    $default = [
        'timestamp' => time(),
        'question' => 'Make your selection',
        'num_questions' => $max_questions,
        'questions' => array_map(function($i) { return "Option $i"; }, range(1, $max_questions)),
        'votes' => array_fill(0, $max_questions, 0),
        'timer' => [ 'duration' => 0, 'remaining' => 0, 'running' => 0 ]
    ];

    file_put_contents($state_file, json_encode($default));
}

//
// Load the current state
//

$state = json_decode(file_get_contents($state_file), true);

//
// Handle POST actions
//

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    // Handle a vote POST from the client
    if (isset($input['vote'])) foreach ($input['vote'] as $idx) $state['votes'][$idx]++;

    // Handle a reset POST from the admin
    else if (isset($input['reset'])) {
        // TODO
    }

    file_put_contents($state_file, json_encode($state));
    echo json_encode(['success'=>true]);
    exit;
}

//
// Handle GET (only get here if no POST was made)
//

echo json_encode($state);
