<?php
// backend/get_complaints.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$email = $_GET['email'] ?? '';
$ref   = $_GET['ref'] ?? '';

if (!empty($ref)) {
    // Track specific complaint by Reference ID
    $endpoint = "/rest/v1/complaints?reference_id=eq." . urlencode($ref) . "&select=*";
} else if (!empty($email)) {
    // Fetch all complaints for the logged-in user, newest first
    $endpoint = "/rest/v1/complaints?citizen_email=eq." . urlencode($email) . "&order=created_at.desc&select=*";
} else {
    echo json_encode(['success' => false, 'message' => 'Provide email or reference_id']);
    exit;
}

$res = supabase_request($endpoint, 'GET');

if ($res['status'] == 200 && is_array($res['data'])) {
    echo json_encode(['success' => true, 'data' => $res['data']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not fetch complaint records', 'data' => []]);
}
?>