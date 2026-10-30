<?php
// backend/get_profile.php

// Handle CORS for Cross-Origin Requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$email = $_GET['email'] ?? '';
$role = $_GET['role'] ?? 'citizen';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing email payload']);
    exit;
}

// Map the role string to the exact Supabase table name
$table = ($role === 'admin' || $role === 'officers') ? 'officers' : 'citizens';

// Create endpoint targeting Supabase via email filter
$endpoint = "/rest/v1/" . $table . "?email=eq." . urlencode($email) . "&select=*";
$res = supabase_request($endpoint, 'GET');

if ($res['status'] == 200 && count($res['data']) > 0) {
    $user = $res['data'][0];
    
    // CRITICAL SECURITY: Strip the password hash before sending JSON back to the browser
    unset($user['password_hash']);
    
    echo json_encode(['success' => true, 'data' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'Profile Data Not Found']);
}
?>