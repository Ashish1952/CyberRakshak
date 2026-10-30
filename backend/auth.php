<?php
// backend/auth.php

// 1. Handle CORS (Cross-Origin Resource Sharing)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight OPTIONS request from browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// 2. Read incoming JSON from Frontend
$raw_input = file_get_contents("php://input");
$request = json_decode($raw_input, true);

if (!$request || !isset($request['view'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit;
}

$view = $request['view']; // e.g., 'user-register', 'user-login', 'admin-login'
$email = $request['email'] ?? '';
$password = $request['password'] ?? '';

// ==========================================
// REGISTRATION LOGIC (CITIZENS ONLY)
// ==========================================
if ($view === 'user-register') {
    $full_name = $request['full_name'] ?? '';
    $mobile = $request['mobile'] ?? '';
    
    // Hash password securely using bcrypt
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $payload = [
        'full_name' => $full_name,
        'email' => $email,
        'mobile' => $mobile,
        'password_hash' => $password_hash
    ];

    // HARDCODED to citizens table. Admins cannot be registered from the frontend.
    $res = supabase_request("/rest/v1/citizens", 'POST', $payload);

    if ($res['status'] == 201) {
        echo json_encode(['success' => true, 'message' => 'Node Initialized (Registration Successful)!']);
    } else {
        // Handle duplicate emails or other Supabase errors
        $errorMsg = $res['data']['message'] ?? 'Registration failed. Email might already exist.';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
} 
// ==========================================
// LOGIN LOGIC (CITIZENS & ADMINS)
// ==========================================
else if (strpos($view, 'login') !== false) {
    // Determine target table based on view
    $table = ($view === 'admin-login') ? 'officers' : 'citizens';

    // Query Supabase for the user by email
    $endpoint = "/rest/v1/" . $table . "?email=eq." . urlencode($email) . "&select=*";
    $res = supabase_request($endpoint, 'GET');

    if ($res['status'] == 200 && count($res['data']) > 0) {
        $user = $res['data'][0];
        
        // Verify the bcrypt hash
        if (password_verify($password, $user['password_hash'])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Link Established (Login Successful)!',
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => ($table === 'officers') ? 'admin' : 'citizen'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Node Identity Not Found.']);
    }
}
?>