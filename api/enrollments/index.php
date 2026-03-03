<?php
// api/enrollments/index.php

$cookieParams = session_get_cookie_params();
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => $cookieParams["lifetime"],
    'path' => '/',
    'domain' => $cookieParams["domain"],
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start(); // Start session once for faculty access control

require_once '../db_connect.php';

header('Content-Type: application/json');

// --- Authentication Check (Faculty Only) ---
if (!isset($_SESSION['faculty_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required. Access Denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $searchTerm = $_GET['search'] ?? '';

    try {
        // SELECT ALL columns from your 'enrollments' table
        $sql = "SELECT
                    id, school_year, grade_level, returning_student,
                    lrn, has_lrn,
                    student_last_name, student_first_name, student_middle_name, student_extension_name,
                    student_birthdate, student_age, student_sex, student_place_of_birth, student_mother_tongue,
                    is_indigenous, ip_community, is_4ps_beneficiary, `4ps_household_id`,
                    has_disability, disability_types, disability_sub_types, /* Adjusted these from your original dashboard */

                    current_address_house_no_street, current_address_street_name,
                    current_address_barangay, current_address_city,
                    current_address_province, current_address_country, current_address_zip,

                    permanent_address_same_as_current,
                    permanent_address_house_no_street, permanent_address_street_name,
                    permanent_address_barangay, permanent_address_city,
                    permanent_address_province, permanent_address_country, permanent_address_zip,

                    father_last_name, father_first_name, father_middle_name, father_contact,
                    mother_last_name, mother_first_name, mother_middle_name, mother_contact,
                    guardian_last_name, guardian_first_name, guardian_middle_name, guardian_contact, guardian_relationship,
                    -- added emails so dashboard can display them
                    student_email, father_email, mother_email, guardian_email,

                    status, section, submission_timestamp, /* last_updated_timestamp, -- This was in your SELECT but not in dashboard.js data mapping */
                    psa_birth_cert_url, report_card_url, other_docs_urls_json
                FROM enrollments";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE (student_last_name LIKE :term
                        OR student_first_name LIKE :term
                        OR lrn LIKE :term
                        OR status LIKE :term
                        OR section LIKE :term
                        OR grade_level LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }
        $sql .= " ORDER BY submission_timestamp DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // The $enrollments array here matches the flat structure dashboard.js expects for `enrollmentsData`
        // No need for the complex re-formatting that was previously in this file,
        // as dashboard.php directly uses the $enrollments from the DB query for its `enrollmentsData`
        // and dashboard.js uses that flat structure.

        echo json_encode($enrollments);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Enrollments GET PDOException: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        echo json_encode(['success' => false, 'message' => 'Database error fetching enrollments. Check server logs.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method for this endpoint.']);
}
?>