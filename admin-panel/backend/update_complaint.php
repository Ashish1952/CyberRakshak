<?php
// backend/update_complaint.php

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

if (!$request || empty($request['reference_id']) || empty($request['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing reference_id or status']);
    exit;
}

$reference_id = $request['reference_id'];

// First attempt update with full officer remarks & SLA tier columns
$payload = [
    'status'          => $request['status'],
    'escalation_tier' => $request['escalation_tier'] ?? 'Tier 1: Local Police Station (IO)',
    'officer_remarks' => $request['officer_remarks'] ?? ''
];

$endpoint = "/rest/v1/complaints?reference_id=eq." . urlencode($reference_id);
$res = supabase_request($endpoint, 'PATCH', $payload);

// Fallback if escalation_tier / officer_remarks columns haven't been migrated yet
if ($res['status'] != 200 && $res['status'] != 204) {
    $res = supabase_request($endpoint, 'PATCH', ['status' => $request['status']]);
}

if ($res['status'] == 200 || $res['status'] == 204) {
    echo json_encode([
        'success' => true,
        'message' => 'Complaint status updated in Supabase',
        'data'    => $res['data'][0] ?? $payload
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $res['data']['message'] ?? 'Failed to update complaint status'
    ]);
}
?>