<?php
// backend/update_officer_profile.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$raw_input = file_get_contents("php://input");
$request = json_decode($raw_input, true);

if (!$request || empty($request['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing officer email']);
    exit;
}

$email = $request['email'];

$payload = [
    'full_name'  => $request['full_name'] ?? 'Cyber Officer',
    'badge_id'   => $request['badge_id'] ?? '',
    'rank'       => $request['rank'] ?? '',
    'mobile'     => $request['mobile'] ?? '',
    'nodal_unit' => $request['nodal_unit'] ?? '',
    'clearance'  => $request['clearance'] ?? 'LEVEL-3 (STATE COMMAND HQ)',
    'state'      => $request['state'] ?? '',
    'district'   => $request['district'] ?? ''
];

$endpoint = "/rest/v1/officers?email=eq." . urlencode($email);
$res = supabase_request($endpoint, 'PATCH', $payload);

if ($res['status'] == 200 || $res['status'] == 204) {
    echo json_encode([
        'success' => true,
        'message' => 'Officer service dossier updated in Supabase',
        'data'    => $res['data'][0] ?? $payload
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $res['data']['message'] ?? 'Failed to update officer profile'
    ]);
}
?>