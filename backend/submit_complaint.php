<?php
// backend/submit_complaint.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$raw_input = file_get_contents("php://input");
$request = json_decode($raw_input, true);

if (!$request || empty($request['citizen_email']) || empty($request['category'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required complaint fields']);
    exit;
}

// Generate unique NCRP Reference ID (e.g., CR-2026-849201)
$reference_id = 'CR-' . date('Y') . '-' . rand(100000, 999999);

$payload = [
    'reference_id'       => $reference_id,
    'citizen_email'      => $request['citizen_email'] ?? '',
    'full_name'          => $request['full_name'] ?? '',
    'mobile'             => $request['mobile'] ?? '',
    'gender'             => $request['gender'] ?? '',
    'dob'                => !empty($request['dob']) ? $request['dob'] : null,
    'relationship'       => $request['relationship'] ?? 'Self (Victim)',
    'state'              => $request['state'] ?? '',
    'district'           => $request['district'] ?? '',
    'police_station'     => $request['police_station'] ?? '',
    'address'            => $request['address'] ?? '',
    'pincode'            => $request['pincode'] ?? '',
    'national_id_type'   => $request['national_id_type'] ?? '',
    'national_id_no'     => $request['national_id_no'] ?? '',
    'category'           => $request['category'] ?? '',
    'sub_category'       => $request['sub_category'] ?? '',
    'incident_date'      => $request['incident_date'] ?? '',
    'platform'           => $request['platform'] ?? '',
    'delay_reason'       => $request['delay_reason'] ?? 'No Delay',
    'bank_name'          => $request['bank_name'] ?? null,
    'financial_loss'     => !empty($request['financial_loss']) ? floatval($request['financial_loss']) : 0,
    'transaction_id'     => $request['transaction_id'] ?? null,
    'account_or_upi'     => $request['account_or_upi'] ?? null,
    'suspect_name'       => $request['suspect_name'] ?? null,
    'suspect_id_type'    => $request['suspect_id_type'] ?? null,
    'suspect_id_value'   => $request['suspect_id_value'] ?? null,
    'suspect_details'    => $request['suspect_details'] ?? null,
    'description'        => $request['description'] ?? '',
    'evidence_file_name' => $request['evidence_file_name'] ?? 'None',
    'evidence_hash'      => $request['evidence_hash'] ?? 'NOT_PROVIDED',
    'status'             => 'PENDING VERIFICATION'
];

$res = supabase_request("/rest/v1/complaints", 'POST', $payload);

if ($res['status'] == 201) {
    // Also update the logged-in citizen's own profile in the `citizens` table
    $citizen_update = [
        'mobile'           => $payload['mobile'],
        'gender'           => $payload['gender'],
        'dob'              => $payload['dob'],
        'national_id_type' => $payload['national_id_type'],
        'national_id_no'   => $payload['national_id_no'],
        'state'            => $payload['state'],
        'district'         => $payload['district'],
        'police_station'   => $payload['police_station'],
        'address'          => $payload['address'],
        'pincode'          => $payload['pincode']
    ];
    $citizen_endpoint = "/rest/v1/citizens?email=eq." . urlencode($payload['citizen_email']);
    supabase_request($citizen_endpoint, 'PATCH', $citizen_update);

    echo json_encode([
        'success'      => true,
        'message'      => 'Complaint registered in NCRP database!',
        'reference_id' => $reference_id,
        'data'         => $res['data'][0] ?? $payload
    ]);
} else {
    $errorMsg = $res['data']['message'] ?? 'Failed to insert complaint into Supabase.';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
}
?>