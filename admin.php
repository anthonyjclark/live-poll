<?php

//
// Handle admin login
//

$PASSCODE = '041';

// Start the session so that we can store an authentication state
session_start();

// POST from login form (must come first)
if (isset($_POST['admin_passcode'])) {
    if ($_POST['admin_passcode'] === $PASSCODE) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $login_error = 'Incorrect passcode.';
    }
}

// Display the login page if not authenticated
if (empty($_SESSION['admin_authenticated'])) {
    include 'login.php';
    exit;
}

// GET from logout form
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}


//
// Admin panel logic
//

// TODO: share configuration with state.php
$state_file = 'state.json';

// timestamp
// question
// num_questions
// questions
// votes
// timer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    $state = json_decode(file_get_contents($state_file), true);

    if (isset($input['reset'])) {
        $state['timestamp'] = time();
        $state['votes'] = array_fill(0, $state['num_questions'], 0);
        file_put_contents($state_file, json_encode($state));
        echo json_encode(['success' => true]);
        exit;
    }

    if (isset($input['set_num_options'])) {
        $num_options = intval($input['set_num_options']);
        $state['timestamp'] = time();
        $state['num_questions'] = $num_options;
        $state['questions'] = array_map(function($i) { return "Option $i"; }, range(1, $num_options));
        $state['votes'] = array_fill(0, $num_options, 0);
        file_put_contents($state_file, json_encode($state));
        echo json_encode(['success' => true]);
        exit;
    }
}

include 'ui.html';
?>
