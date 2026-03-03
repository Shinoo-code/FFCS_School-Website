<?php
// School Website 1/api/enrollments/submit.php

// --- TIMEZONE FIX ---
// Set the default timezone to Philippine time
date_default_timezone_set('Asia/Manila');
// --- END TIMEZONE FIX ---

// Error Reporting for Development - REMOVE OR ADJUST FOR PRODUCTION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- BEGIN CORS HEADERS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
// --- END CORS HEADERS ---

require_once '../db_connect.php'; // $pdo variable should be available from this include

// Set header to application/json AFTER potential exit for OPTIONS
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- File Upload Configuration ---
    $webAccessibleUploadPath = 'uploads/'; 
    $fileSystemUploadDir = __DIR__ . '/../../uploads/'; 

    if (!is_dir($fileSystemUploadDir)) {
        if (!mkdir($fileSystemUploadDir, 0775, true)) { 
             echo json_encode(['success' => false, 'message' => 'Critical Error: Failed to create uploads directory. Check server permissions. Path attempted: ' . realpath($fileSystemUploadDir) ]);
             exit;
        }
    }
    if (!is_writable($fileSystemUploadDir)) {
        echo json_encode(['success' => false, 'message' => 'Critical Error: Uploads directory is not writable by the server. Path: ' . realpath($fileSystemUploadDir)]);
        exit;
    }

    $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxFileSize = 10 * 1024 * 1024; // 10 MB
    $fileProcessingErrors = [];

    // Function to handle a single file upload
    function handleFileUpload($fileInputName, $fileSystemDir, $webPath, $allowedTypes, $maxSize, &$errors) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileInputName];
            $originalFileName = basename($file['name']);
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

            $safeFileNameBase = preg_replace("/[^a-zA-Z0-9._-]/", "_", pathinfo($originalFileName, PATHINFO_FILENAME));
            $uniqueFileName = $safeFileNameBase . '_' . uniqid() . '.' . $fileExtension;
            $destinationPathOnServer = $fileSystemDir . $uniqueFileName;

            if (!in_array($fileExtension, $allowedTypes)) {
                $errors[] = "File type not allowed for '" . htmlspecialchars($originalFileName) . "'. Allowed: " . implode(', ', $allowedTypes);
                return null;
            }
            if ($fileSize > $maxSize) {
                $errors[] = "File '" . htmlspecialchars($originalFileName) . "' is too large (Max: " . ($maxSize / 1024 / 1024) . "MB).";
                return null;
            }

            if (move_uploaded_file($fileTmpName, $destinationPathOnServer)) {
                return $webPath . $uniqueFileName; // Return the web accessible path
            } else {
                $errors[] = "Failed to move uploaded file '" . htmlspecialchars($originalFileName) . "'.";
                return null;
            }
        } elseif (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => "File exceeds server's upload_max_filesize.",
                UPLOAD_ERR_FORM_SIZE  => "File exceeds form's MAX_FILE_SIZE.",
                UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
            ];
            $errorCode = $_FILES[$fileInputName]['error'];
            $errors[] = "Upload error for input '" . htmlspecialchars($fileInputName) . "': " . ($uploadErrors[$errorCode] ?? "Unknown error code " . $errorCode);
        }
        return null;
    }

    // Function to handle multiple file uploads
    function handleMultipleFileUploads($fileInputName, $fileSystemDir, $webPath, $allowedTypes, $maxSize, &$errors) {
        $uploadedPaths = [];
        if (isset($_FILES[$fileInputName]['name']) && is_array($_FILES[$fileInputName]['name'])) {
            $numberOfFiles = count($_FILES[$fileInputName]['name']);
            for ($i = 0; $i < $numberOfFiles; $i++) {
                if ($_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_OK) {
                    $originalFileName = basename($_FILES[$fileInputName]['name'][$i]);
                    $fileTmpName = $_FILES[$fileInputName]['tmp_name'][$i];
                    $fileSize = $_FILES[$fileInputName]['size'][$i];
                    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

                    $safeFileNameBase = preg_replace("/[^a-zA-Z0-9._-]/", "_", pathinfo($originalFileName, PATHINFO_FILENAME));
                    $uniqueFileName = $safeFileNameBase . '_' . uniqid() . '.' . $fileExtension;
                    $destinationPathOnServer = $fileSystemDir . $uniqueFileName;

                    if (!in_array($fileExtension, $allowedTypes)) {
                        $errors[] = "File type not allowed for '" . htmlspecialchars($originalFileName) . "'."; continue;
                    }
                    if ($fileSize > $maxSize) {
                        $errors[] = "File '" . htmlspecialchars($originalFileName) . "' is too large."; continue;
                    }
                    if (move_uploaded_file($fileTmpName, $destinationPathOnServer)) {
                        $uploadedPaths[] = $webPath . $uniqueFileName;
                    } else {
                        $errors[] = "Failed to move file '" . htmlspecialchars($originalFileName) . "'.";
                    }
                } elseif ($_FILES[$fileInputName]['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Upload error for '" . htmlspecialchars($_FILES[$fileInputName]['name'][$i]) . "'. Code: " . $_FILES[$fileInputName]['error'][$i];
                }
            }
        }
        return count($uploadedPaths) > 0 ? json_encode($uploadedPaths) : null;
    }
    // --- END File Upload Logic ---

    // --- Sanitize and Collect Text Data from $_POST ---
    function sanitize_string($str) { return trim(htmlspecialchars($str ?? '')); }
    $validationErrors = []; 

    // Basic Info
    $schoolYear     = sanitize_string($_POST['school-year'] ?? null);
    $gradeLevel     = sanitize_string($_POST['grade-level'] ?? null);
    $returning      = sanitize_string($_POST['returning'] ?? 'no');
    
    // Learner Info
    $hasLrn         = sanitize_string($_POST['has-lrn'] ?? 'no');
    $isTransferee   = sanitize_string($_POST['is-transferee'] ?? 'no');
    
    // --- PREVIOUS SCHOOL FIX ---
    // Added this line to capture the previous school name
    // (Assuming your form field is named 'previous-school-name')
    $previousSchoolName = sanitize_string($_POST['previous_school_name'] ?? null);
    // --- END PREVIOUS SCHOOL FIX ---

    $lrn            = sanitize_string($_POST['lrn'] ?? null);
    $generatedLrn   = null;

    if ($hasLrn === 'no') {
        $temporaryLrn = 'T' . date('Ymd') . mt_rand(100000, 999999);
        $lrn = $temporaryLrn;
        $generatedLrn = $lrn;
    }

    // (All other text fields: $lastName, $firstName, ... $guardianRelationship)
    $lastName       = sanitize_string($_POST['last-name'] ?? null);
    $firstName      = sanitize_string($_POST['first-name'] ?? null);
    $middleName     = sanitize_string($_POST['middle-name'] ?? null);
    $extensionName  = sanitize_string($_POST['extension-name'] ?? null);
    $birthdate      = sanitize_string($_POST['birthdate'] ?? null);
    $age            = isset($_POST['age']) ? filter_var($_POST['age'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]) : null;
    $sex            = sanitize_string($_POST['sex'] ?? null);
    $placeOfBirth   = sanitize_string($_POST['place-of-birth'] ?? null);
    $motherTongue   = sanitize_string($_POST['mother-tongue'] ?? null);
    $indigenous     = sanitize_string($_POST['indigenous'] ?? 'no');
    $ipCommunity    = sanitize_string($_POST['ip-community'] ?? null);
    $is4ps          = sanitize_string($_POST['4ps'] ?? 'no');
    $householdId4ps = sanitize_string($_POST['4ps-id'] ?? null);
    $hasDisability      = sanitize_string($_POST['with-disability'] ?? 'no');
    $disabilityTypesIn  = $_POST['disability-type'] ?? [];
    $disabilityTypes    = is_array($disabilityTypesIn) ? implode(', ', array_map('sanitize_string', $disabilityTypesIn)) : sanitize_string($disabilityTypesIn);
    $disabilitySubTypesIn = $_POST['disability-subtype'] ?? [];
    $disabilitySubTypes = is_array($disabilitySubTypesIn) ? implode(', ', array_map('sanitize_string', $disabilitySubTypesIn)) : sanitize_string($disabilitySubTypesIn);
    $currentAddressHouseNoStreet = sanitize_string($_POST['current-house-no'] ?? null);
    $currentAddressStreetName    = sanitize_string($_POST['current-street'] ?? null);
    $currentAddressBarangay      = sanitize_string($_POST['current-barangay'] ?? null);
    $currentAddressCity          = sanitize_string($_POST['current-municipality'] ?? null);
    $currentAddressProvince      = sanitize_string($_POST['current-province'] ?? null);
    $currentAddressCountry       = sanitize_string($_POST['current-country'] ?? 'Philippines');
    $currentAddressZip           = sanitize_string($_POST['current-zipcode'] ?? null);
    $sameAddress = sanitize_string($_POST['same-address'] ?? 'yes');
    if ($sameAddress === 'yes') {
        $permanentAddressHouseNoStreet = $currentAddressHouseNoStreet;
        $permanentAddressStreetName    = $currentAddressStreetName;
        $permanentAddressBarangay      = $currentAddressBarangay;
        $permanentAddressCity          = $currentAddressCity;
        $permanentAddressProvince      = $currentAddressProvince;
        $permanentAddressCountry       = $currentAddressCountry;
        $permanentAddressZip           = $currentAddressZip;
    } else {
        $permanentAddressHouseNoStreet = sanitize_string($_POST['permanent-house-no'] ?? null);
        $permanentAddressStreetName    = sanitize_string($_POST['permanent-sitio'] ?? null);
        $permanentAddressBarangay      = sanitize_string($_POST['permanent-barangay'] ?? null);
        $permanentAddressCity          = sanitize_string($_POST['permanent-municipality'] ?? null);
        $permanentAddressProvince      = sanitize_string($_POST['permanent-province'] ?? null);
        $permanentAddressCountry       = sanitize_string($_POST['permanent-country'] ?? 'Philippines');
        $permanentAddressZip           = sanitize_string($_POST['permanent-zipcode'] ?? null);
    }
    $fatherLastName     = sanitize_string($_POST['father-last-name'] ?? null);
    $fatherFirstName    = sanitize_string($_POST['father-first-name'] ?? null);
    $fatherMiddleName   = sanitize_string($_POST['father-middle-name'] ?? null);
    $fatherContact      = sanitize_string($_POST['father-contact'] ?? null);
    $motherLastName     = sanitize_string($_POST['mother-last-name'] ?? null);
    $motherFirstName    = sanitize_string($_POST['mother-first-name'] ?? null);
    $motherMiddleName   = sanitize_string($_POST['mother-middle-name'] ?? null);
    $motherContact      = sanitize_string($_POST['mother-contact'] ?? null);
    $guardianLastName   = sanitize_string($_POST['guardian-last-name'] ?? null);
    $guardianFirstName  = sanitize_string($_POST['guardian-first-name'] ?? null);
    $guardianMiddleName = sanitize_string($_POST['guardian-middle-name'] ?? null);
    $guardianContact    = sanitize_string($_POST['guardian-contact'] ?? null);
    $guardianRelationship= sanitize_string($_POST['guardian-relationship'] ?? null);
    // --- New Email Fields ---
    $studentEmail = sanitize_string($_POST['student-email'] ?? null);
    $fatherEmail  = sanitize_string($_POST['father-email'] ?? null);
    $motherEmail  = sanitize_string($_POST['mother-email'] ?? null);
    $guardianEmail = sanitize_string($_POST['guardian-email'] ?? null);
    $emailConsent = sanitize_string($_POST['email-consent'] ?? null);

    // --- Validation (Text Fields) ---
    if (empty($lastName)) $validationErrors[] = "Student's Last Name is required.";
    if (empty($firstName)) $validationErrors[] = "Student's First Name is required.";
    // ... add ALL other required text field validations ...

    // --- Email validation (optional) ---
    if (!empty($studentEmail) && !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) $validationErrors[] = "Learner email is invalid.";
    if (!empty($fatherEmail) && !filter_var($fatherEmail, FILTER_VALIDATE_EMAIL)) $validationErrors[] = "Father's email is invalid.";
    if (!empty($motherEmail) && !filter_var($motherEmail, FILTER_VALIDATE_EMAIL)) $validationErrors[] = "Mother's email is invalid.";
    if (!empty($guardianEmail) && !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) $validationErrors[] = "Guardian's email is invalid.";

    // If any email provided, require explicit consent
    $anyEmailProvided = !empty($studentEmail) || !empty($fatherEmail) || !empty($motherEmail) || !empty($guardianEmail);
    $consentGiven = ($emailConsent === '1' || $emailConsent === 'on' || $emailConsent === 'true');
    if ($anyEmailProvided && !$consentGiven) {
        $validationErrors[] = 'Consent is required to receive enrollment-related emails when email addresses are provided.';
    }

    // --- *** SERVER-SIDE FILE VALIDATION *** ---
    $isPSARequired = in_array($gradeLevel, ['Kindergarten', '1', '7', '11']);
    $isReportCardRequired = in_array($gradeLevel, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);
    $isTransfereeDocsRequired = ($isTransferee === 'yes');

    if ($isPSARequired && (!isset($_FILES['file_psa']) || $_FILES['file_psa']['error'] === UPLOAD_ERR_NO_FILE)) {
        $validationErrors[] = "PSA Birth Certificate is required for this grade level.";
    }
    if ($isReportCardRequired && (!isset($_FILES['file_report_card']) || $_FILES['file_report_card']['error'] === UPLOAD_ERR_NO_FILE)) {
        $validationErrors[] = "Previous Report Card is required for this grade level.";
    }
    if ($isTransfereeDocsRequired && (!isset($_FILES['file_transferee_docs']) || !isset($_FILES['file_transferee_docs']['error']) || $_FILES['file_transferee_docs']['error'][0] === UPLOAD_ERR_NO_FILE)) {
        $validationErrors[] = "Transferee Documents are required.";
    }
    // --- *** END: SERVER-SIDE FILE VALIDATION *** ---

    // Process file uploads *after* checking for missing files
    $psaBirthCertUrl = handleFileUpload('file_psa', $fileSystemUploadDir, $webAccessibleUploadPath, $allowedFileTypes, $maxFileSize, $fileProcessingErrors);
    $reportCardUrl = handleFileUpload('file_report_card', $fileSystemUploadDir, $webAccessibleUploadPath, $allowedFileTypes, $maxFileSize, $fileProcessingErrors);
    $otherDocsUrlsJson = handleMultipleFileUploads('file_transferee_docs', $fileSystemUploadDir, $webAccessibleUploadPath, $allowedFileTypes, $maxFileSize, $fileProcessingErrors);


    $allErrors = array_merge($fileProcessingErrors, $validationErrors);

    if (!empty($allErrors)) {
        http_response_code(400);
        // Clean up any files that *did* upload if validation failed
        if ($psaBirthCertUrl) @unlink($fileSystemUploadDir . basename($psaBirthCertUrl));
        if ($reportCardUrl) @unlink($fileSystemUploadDir . basename($reportCardUrl));
        if ($otherDocsUrlsJson) {
            $otherDocs = json_decode($otherDocsUrlsJson, true);
            if (is_array($otherDocs)) {
                foreach ($otherDocs as $file) @unlink($fileSystemUploadDir . basename($file));
            }
        }
        echo json_encode(['success' => false, 'message' => 'Validation failed or file error(s) occurred.', 'errors' => $allErrors]);
        exit;
    }

    $status = "Pending";
    $submissionTimestamp = date('Y-m-d H:i:s'); // This will now use the 'Asia/Manila' timezone
    $section = null; 

    // --- PREVIOUS SCHOOL FIX ---
    // Updated SQL statement to include `previous_school_name`
    $sql = "INSERT INTO enrollments (
                school_year, grade_level, returning_student, has_lrn, is_transferee, previous_school_name, lrn,
                student_last_name, student_first_name, student_middle_name, student_extension_name,
                student_birthdate, student_age, student_sex, student_place_of_birth, student_mother_tongue,
                is_indigenous, ip_community, is_4ps_beneficiary, `4ps_household_id`,
                has_disability, disability_types, disability_sub_types,
                current_address_house_no_street, current_address_street_name, current_address_barangay,
                current_address_city, current_address_province, current_address_country, current_address_zip,
                permanent_address_same_as_current,
                permanent_address_house_no_street, permanent_address_street_name, permanent_address_barangay,
                permanent_address_city, permanent_address_province, permanent_address_country, permanent_address_zip,
                father_last_name, father_first_name, father_middle_name, father_contact,
                mother_last_name, mother_first_name, mother_middle_name, mother_contact,
                guardian_last_name, guardian_first_name, guardian_middle_name, guardian_contact, guardian_relationship,
                student_email, father_email, mother_email, guardian_email,
                status, submission_timestamp, section,
                psa_birth_cert_url, report_card_url, other_docs_urls_json
            ) VALUES (
                :school_year, :grade_level, :returning_student, :has_lrn, :is_transferee, :previous_school_name, :lrn,
                :student_last_name, :student_first_name, :student_middle_name, :student_extension_name,
                :student_birthdate, :student_age, :student_sex, :student_place_of_birth, :student_mother_tongue,
                :is_indigenous, :ip_community, :is_4ps_beneficiary, :4ps_household_id,
                :has_disability, :disability_types, :disability_sub_types,
                :current_address_house_no_street, :current_address_street_name, :current_address_barangay,
                :current_address_city, :current_address_province, :current_address_country, :current_address_zip,
                :permanent_address_same_as_current,
                :permanent_address_house_no_street, :permanent_address_street_name, :permanent_address_barangay,
                :permanent_address_city, :permanent_address_province, :permanent_address_country, :permanent_address_zip,
                :father_last_name, :father_first_name, :father_middle_name, :father_contact,
                :mother_last_name, :mother_first_name, :mother_middle_name, :mother_contact,
                :guardian_last_name, :guardian_first_name, :guardian_middle_name, :guardian_contact, :guardian_relationship,
                :student_email, :father_email, :mother_email, :guardian_email,
                :status, :submission_timestamp, :section,
                :psa_birth_cert_url, :report_card_url, :other_docs_urls_json
            )";
    // --- END PREVIOUS SCHOOL FIX ---

    try {
        $stmt = $pdo->prepare($sql);

        $lrnValue = ($hasLrn === 'yes' && !empty($lrn)) ? $lrn : $generatedLrn;

        // --- Bind all parameters ---
        $stmt->bindParam(':school_year', $schoolYear);
        $stmt->bindParam(':grade_level', $gradeLevel);
        $stmt->bindParam(':returning_student', $returning);
        $stmt->bindParam(':has_lrn', $hasLrn);
        $stmt->bindParam(':is_transferee', $isTransferee); 
        
        // Added binding for the new parameter
        $stmt->bindParam(':previous_school_name', $previousSchoolName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':lrn', $lrnValue, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':student_last_name', $lastName);
        $stmt->bindParam(':student_first_name', $firstName);
        $stmt->bindParam(':student_middle_name', $middleName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':student_extension_name', $extensionName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':student_birthdate', $birthdate);
        $stmt->bindParam(':student_age', $age, PDO::PARAM_INT|PDO::PARAM_NULL);
        $stmt->bindParam(':student_sex', $sex);
        $stmt->bindParam(':student_place_of_birth', $placeOfBirth, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':student_mother_tongue', $motherTongue, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':is_indigenous', $indigenous);
        $stmt->bindParam(':ip_community', $ipCommunity, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':is_4ps_beneficiary', $is4ps);
        $stmt->bindParam(':4ps_household_id', $householdId4ps, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':has_disability', $hasDisability);
        $stmt->bindParam(':disability_types', $disabilityTypes, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':disability_sub_types', $disabilitySubTypes, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_house_no_street', $currentAddressHouseNoStreet, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_street_name', $currentAddressStreetName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_barangay', $currentAddressBarangay, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_city', $currentAddressCity, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_province', $currentAddressProvince, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_country', $currentAddressCountry, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':current_address_zip', $currentAddressZip, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_same_as_current', $sameAddress);
        $stmt->bindParam(':permanent_address_house_no_street', $permanentAddressHouseNoStreet, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_street_name', $permanentAddressStreetName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_barangay', $permanentAddressBarangay, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_city', $permanentAddressCity, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_province', $permanentAddressProvince, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_country', $permanentAddressCountry, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':permanent_address_zip', $permanentAddressZip, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':father_last_name', $fatherLastName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':father_first_name', $fatherFirstName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':father_middle_name', $fatherMiddleName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':father_contact', $fatherContact, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':mother_last_name', $motherLastName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':mother_first_name', $motherFirstName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':mother_middle_name', $motherMiddleName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':mother_contact', $motherContact, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':guardian_last_name', $guardianLastName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':guardian_first_name', $guardianFirstName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':guardian_middle_name', $guardianMiddleName, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':guardian_contact', $guardianContact, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':guardian_relationship', $guardianRelationship, PDO::PARAM_STR|PDO::PARAM_NULL);
    // --- Email binds ---
    $stmt->bindParam(':student_email', $studentEmail, PDO::PARAM_STR|PDO::PARAM_NULL);
    $stmt->bindParam(':father_email', $fatherEmail, PDO::PARAM_STR|PDO::PARAM_NULL);
    $stmt->bindParam(':mother_email', $motherEmail, PDO::PARAM_STR|PDO::PARAM_NULL);
    $stmt->bindParam(':guardian_email', $guardianEmail, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':submission_timestamp', $submissionTimestamp);
        $stmt->bindParam(':section', $section, PDO::PARAM_STR|PDO::PARAM_NULL);

        // Bind file URLs
        $stmt->bindParam(':psa_birth_cert_url', $psaBirthCertUrl, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':report_card_url', $reportCardUrl, PDO::PARAM_STR|PDO::PARAM_NULL);
        $stmt->bindParam(':other_docs_urls_json', $otherDocsUrlsJson, PDO::PARAM_STR|PDO::PARAM_NULL);
        // --- End Bind ---


        if ($stmt->execute()) {
            $newEnrollmentId = $pdo->lastInsertId();
            $responsePayload = [
                'success' => true,
                'message' => 'Enrollment application submitted successfully!',
                'file_upload_messages' => $fileProcessingErrors
            ];

            if ($generatedLrn) {
                $responsePayload['generated_lrn'] = $generatedLrn;
                $responsePayload['message'] = 'Enrollment application submitted successfully! Please save your Temporary Reference Number to check your status.';
            }
        
            echo json_encode($responsePayload);

        } else {
            http_response_code(500);
            $errorInfo = $stmt->errorInfo();
            error_log("DB Execute Failed: " . print_r($errorInfo, true) . " SQL: " . $sql);
            echo json_encode(['success' => false, 'message' => 'Database execution failed. Details: ' . $errorInfo[2], 'errors' => $fileProcessingErrors]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("DB Submission PDOException: " . $e->getMessage() . " SQL: " . $sql);
        echo json_encode(['success' => false, 'message' => 'A database error occurred: ' . $e->getMessage(), 'errors' => $fileProcessingErrors]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed for this endpoint.']);
}
?>