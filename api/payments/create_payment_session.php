<?php
require_once '../db_connect.php';

// Your correct Test Secret Key from your dashboard.
$paymongo_secret_key = 'sk_test_TL3VwLVy3L8RU93pyM1CJ9Nj';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $enrollment_id = $input['enrollment_id'] ?? null;
    $lrn = $input['lrn'] ?? null;
    $amount = $input['amount'] ?? null;

    if (empty($enrollment_id) || empty($lrn) || empty($amount)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required information.']);
        exit;
    }
    
    $project_folder = rawurlencode('Final-School-Web'); 
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
    $base_url = rtrim($protocol . $host, '/');

    $success_url = "$base_url/receipt.php?lrn=$lrn";
    $cancel_url = "$base_url/results.php?lrn=$lrn&payment=cancelled";
    
    $amount_in_centavos = round($amount * 100);

    $payload = [
        'data' => [
            'attributes' => [
                'billing' => [
                    'name' => 'Student LRN: ' . $lrn,
                    'email' => 'student-payment@mcrei.edu',
                ],
                'line_items' => [
                    [
                        'currency' => 'PHP',
                        'amount' => $amount_in_centavos,
                        'name' => 'Tuition Fee Payment (Enrollment ID: ' . $enrollment_id . ')',
                        'quantity' => 1,
                    ]
                ],
                'payment_method_types' => ['card', 'gcash', 'paymaya'],
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'description' => 'Enrollment Payment',
                // *** THIS IS THE CRITICAL ADDITION ***
                'metadata' => [
                    'enrollment_id' => $enrollment_id,
                    'lrn' => $lrn
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_USERPWD, $paymongo_secret_key . ':');
    $headers = ['Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $response) {
        $result = json_decode($response, true);
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (enrollment_id, amount_paid, payment_method, reference_number, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $enrollment_id,
                $amount,
                'Online',
                $result['data']['id'],
                'Pending'
            ]);
        } catch (PDOException $e) {
            error_log("DB error after creating PayMongo session: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'checkout_url' => $result['data']['attributes']['checkout_url']]);
    } else {
        http_response_code(500);
        $error_details = json_decode($response, true);
        error_log("PayMongo Error. Response: " . print_r($error_details, true));
        echo json_encode(['success' => false, 'message' => 'Failed to create payment session.', 'details' => $error_details]);
    }
}
?>