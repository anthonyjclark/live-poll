<?php
header('Content-Type: application/json');
$poll_lines = file('poll.txt', FILE_IGNORE_NEW_LINES);
$votes_lines = file('votes.txt', FILE_IGNORE_NEW_LINES);

$question = array_shift($poll_lines);
$options = $poll_lines;
$votes = array_map('intval', $votes_lines);


$reset_version = file_exists('reset.txt') ? trim(file_get_contents('reset.txt')) : '';
$data = [
    'question' => $question,
    'options' => array_map(function($opt, $i) use ($votes) {
        return [
            'text' => $opt,
            'votes' => $votes[$i] ?? 0
        ];
    }, $options, array_keys($options)),
    'reset_version' => $reset_version
];

echo json_encode($data);
?>
