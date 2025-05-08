<?php

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$response = [
    'raw_input' => $raw,
    'json_decoded' => $input,
    'is_array' => is_array($input),
    'novo' => $input['novo'] ?? null,
    'sha' => $input['sha'] ?? null
];

echo json_encode($response, JSON_PRETTY_PRINT);