<?php
// dashboard.php
require 'api/db_connect.php'; // Ensures PDO is available and error reporting is set

// Initialize session with hardened defaults
require_once __DIR__ . '/api/session.php';

// Session Check: Redirect if not logged in
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

$faculty_role = $_SESSION['faculty_role']; // Get the role from the session
$is_admin = ($faculty_role === 'admin');   // Boolean flag for admin

// CSRF helper available for forms rendered on this page
require_once 'api/csrf.php';

// Expose CSRF token to client-side code (used by JS-generated forms)
echo "\n<script>window.APP_CSRF_TOKEN = '" . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . "';</script>\n";


// --- Handler for Status Update ---
// (This logic should ideally be in a separate API file, but keeping it here as per original structure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Permission check inside the handler is better
    if (!$is_admin) {
        header("Location: dashboard.php?error=permission_denied_status_update" . (isset($_SERVER['HTTP_REFERER']) ? '#' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_FRAGMENT) : ''));
        exit;
    }

    $id = $_POST['enrollment_id'];
    $status = $_POST['status'];
    $section = null; // Default to null

    // Only assign section if status is 'Enrolled' and a section is provided
    if ($status === 'Enrolled' && isset($_POST['section']) && !empty(trim($_POST['section']))) {
        $section = trim($_POST['section']); // Sanitize section input
    }

    try {
        // Use parameterized query
        $stmt = $pdo->prepare("UPDATE enrollments SET status = :status, section = :section WHERE id = :id");
        $stmt->execute(['status' => $status, 'section' => $section, 'id' => $id]);


        // Redirect logic (preserving hash)
        $redirect_url = 'dashboard.php';
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
            if(isset($referer_parts['fragment'])) {
                $redirect_url .= '#' . $referer_parts['fragment'];
            }
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=updated';
        header("Location: " . $redirect_url);
        exit;

    } catch (PDOException $e) {
        error_log("Status Update PDOException: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        // Redirect logic with error (preserving hash)
        $redirect_url = 'dashboard.php';
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
            if(isset($referer_parts['fragment'])) {
                $redirect_url .= '#' . $referer_parts['fragment'];
            }
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=update_status_error&message=' . urlencode("Database error updating status.");
        header("Location: " . $redirect_url);
        exit;
    }
}

// --- Handler for Manual Enrollment Submission ---
// (This logic should also ideally be in a separate API file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual_enrollment'])) {
    if (!$is_admin) {
        header("Location: dashboard.php?error=permission_denied_manual_add" . (isset($_SERVER['HTTP_REFERER']) ? '#' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_FRAGMENT) : ''));
        exit;
    }
    // Helper function for sanitization
    function sanitize_input_dashboard($data) {
        return htmlspecialchars(trim($data ?? ''));
    }
    // Function to handle disability sub-types
    function map_disability_subtypes($disability_types_posted, $disability_subtypes_posted) {
        $subtypes = [];
        if (is_array($disability_types_posted)) {
            foreach ($disability_types_posted as $type_value) {
                $safe_type_value = sanitize_input_dashboard($type_value);
                if (!empty($safe_type_value)) {
                    $subtype_key = strtolower(str_replace([' ', '/', '-', '.'], '_', $safe_type_value));
                    if (isset($disability_subtypes_posted[$subtype_key]) && !empty(trim($disability_subtypes_posted[$subtype_key]))) {
                        $subtypes[] = sanitize_input_dashboard($disability_subtypes_posted[$subtype_key]);
                    }
                }
            }
        }
        return $subtypes;
    }

    try {
        // Collect all fields from the manual form
        $schoolYear     = sanitize_input_dashboard($_POST['manual_school_year'] ?? null);
        $gradeLevel     = sanitize_input_dashboard($_POST['manual_grade_level'] ?? null);
        $returning      = sanitize_input_dashboard($_POST['manual_returning_student'] ?? 'no');
        $hasLrn         = sanitize_input_dashboard($_POST['manual_has_lrn'] ?? 'no');
        $lrn            = ($hasLrn === 'yes' && !empty($_POST['manual_lrn'])) ? sanitize_input_dashboard($_POST['manual_lrn']) : null;
        $lastName       = sanitize_input_dashboard($_POST['manual_student_last_name'] ?? null);
        $firstName      = sanitize_input_dashboard($_POST['manual_student_first_name'] ?? null);
        $middleName     = sanitize_input_dashboard($_POST['manual_student_middle_name'] ?? null);
        $extensionName  = sanitize_input_dashboard($_POST['manual_student_extension_name'] ?? null);
        $birthdate      = sanitize_input_dashboard($_POST['manual_student_birthdate'] ?? null);
        $age            = isset($_POST['manual_student_age']) ? filter_var($_POST['manual_student_age'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>0]]) : null;
        $sex            = sanitize_input_dashboard($_POST['manual_student_sex'] ?? null);
        $placeOfBirth   = sanitize_input_dashboard($_POST['manual_student_place_of_birth'] ?? null);
        $motherTongue   = sanitize_input_dashboard($_POST['manual_student_mother_tongue'] ?? null);
        $indigenous     = sanitize_input_dashboard($_POST['manual_is_indigenous'] ?? 'no');
        $ipCommunity    = ($indigenous === 'yes' && !empty($_POST['manual_ip_community'])) ? sanitize_input_dashboard($_POST['manual_ip_community']) : null;
        $is4ps          = sanitize_input_dashboard($_POST['manual_is_4ps_beneficiary'] ?? 'no');
        $householdId4ps = ($is4ps === 'yes' && !empty($_POST['manual_4ps_household_id'])) ? sanitize_input_dashboard($_POST['manual_4ps_household_id']) : null;
        $hasDisability  = sanitize_input_dashboard($_POST['manual_has_disability'] ?? 'no');

        $disabilityTypesPosted = $_POST['manual_disability_types'] ?? [];
        $disabilityTypesArray = is_array($disabilityTypesPosted) ? array_map('sanitize_input_dashboard', array_filter($disabilityTypesPosted)) : (empty($disabilityTypesPosted) ? [] : [sanitize_input_dashboard($disabilityTypesPosted)]);
        $disabilityTypesString = !empty($disabilityTypesArray) ? implode(', ', $disabilityTypesArray) : null;

        $disabilitySubTypesPosted = $_POST['manual_disability_sub_types'] ?? [];
        $disabilitySubTypesArray = map_disability_subtypes($disabilityTypesPosted, $disabilitySubTypesPosted);
        $disabilitySubTypesString = !empty($disabilitySubTypesArray) ? implode(', ', $disabilitySubTypesArray) : null;

        // Addresses
        $currentAddressHouseNoStreet = sanitize_input_dashboard($_POST['manual_current_address_house_no_street'] ?? null);
        $currentAddressStreetName    = sanitize_input_dashboard($_POST['manual_current_address_street_name'] ?? null);
        $currentAddressBarangay      = sanitize_input_dashboard($_POST['manual_current_address_barangay'] ?? null);
        $currentAddressCity          = sanitize_input_dashboard($_POST['manual_current_address_city'] ?? null);
        $currentAddressProvince      = sanitize_input_dashboard($_POST['manual_current_address_province'] ?? null);
        $currentAddressCountry       = sanitize_input_dashboard($_POST['manual_current_address_country'] ?? 'Philippines');
        $currentAddressZip           = sanitize_input_dashboard($_POST['manual_current_address_zip'] ?? null);
        $sameAddress                 = sanitize_input_dashboard($_POST['manual_permanent_address_same_as_current'] ?? 'yes');

        if ($sameAddress === 'yes') {
            $permanentAddressHouseNoStreet = $currentAddressHouseNoStreet;
            $permanentAddressStreetName    = $currentAddressStreetName;
            $permanentAddressBarangay      = $currentAddressBarangay;
            $permanentAddressCity          = $currentAddressCity;
            $permanentAddressProvince      = $currentAddressProvince;
            $permanentAddressCountry       = $currentAddressCountry;
            $permanentAddressZip           = $currentAddressZip;
        } else {
            $permanentAddressHouseNoStreet = sanitize_input_dashboard($_POST['manual_permanent_address_house_no_street'] ?? null);
            $permanentAddressStreetName    = sanitize_input_dashboard($_POST['manual_permanent_address_street_name'] ?? null);
            $permanentAddressBarangay      = sanitize_input_dashboard($_POST['manual_permanent_address_barangay'] ?? null);
            $permanentAddressCity          = sanitize_input_dashboard($_POST['manual_permanent_address_city'] ?? null);
            $permanentAddressProvince      = sanitize_input_dashboard($_POST['manual_permanent_address_province'] ?? null);
            $permanentAddressCountry       = sanitize_input_dashboard($_POST['manual_permanent_address_country'] ?? 'Philippines');
            $permanentAddressZip           = sanitize_input_dashboard($_POST['manual_permanent_address_zip'] ?? null);
        }

        // Parents/Guardians
        $fatherLastName     = sanitize_input_dashboard($_POST['manual_father_last_name'] ?? null);
        $fatherFirstName    = sanitize_input_dashboard($_POST['manual_father_first_name'] ?? null);
        $fatherMiddleName   = sanitize_input_dashboard($_POST['manual_father_middle_name'] ?? null);
        $fatherContact      = sanitize_input_dashboard($_POST['manual_father_contact'] ?? null);
        $motherLastName     = sanitize_input_dashboard($_POST['manual_mother_last_name'] ?? null);
        $motherFirstName    = sanitize_input_dashboard($_POST['manual_mother_first_name'] ?? null);
        $motherMiddleName   = sanitize_input_dashboard($_POST['manual_mother_middle_name'] ?? null);
        $motherContact      = sanitize_input_dashboard($_POST['manual_mother_contact'] ?? null);
        $guardianLastName   = sanitize_input_dashboard($_POST['manual_guardian_last_name'] ?? null);
        $guardianFirstName  = sanitize_input_dashboard($_POST['manual_guardian_first_name'] ?? null);
        $guardianMiddleName = sanitize_input_dashboard($_POST['manual_guardian_middle_name'] ?? null);
        $guardianContact    = sanitize_input_dashboard($_POST['manual_guardian_contact'] ?? null);
        $guardianRelationship= sanitize_input_dashboard($_POST['manual_guardian_relationship'] ?? null);

        $status = "Pending"; // Default status
        $submissionTimestamp = date('Y-m-d H:i:s');
        $section = null;

        // Basic Validation (Keep this robust)
        $validationErrors = [];
        if (empty($schoolYear)) $validationErrors[] = "School Year required.";
        if (empty($gradeLevel)) $validationErrors[] = "Grade Level required.";
        if (empty($lastName)) $validationErrors[] = "Student Last Name required.";
        if (empty($firstName)) $validationErrors[] = "Student First Name required.";
        if (empty($birthdate)) $validationErrors[] = "Birthdate required.";
        if ($age === null) $validationErrors[] = "Age required.";
        if (empty($sex)) $validationErrors[] = "Sex required.";
        // ... Add ALL other required field checks ...
         if (empty($placeOfBirth)) $validationErrors[] = "Student Place of Birth is required.";
         if (empty($motherTongue)) $validationErrors[] = "Student Mother Tongue is required.";
         if (empty($currentAddressHouseNoStreet)) $validationErrors[] = "Current Address House No/Street is required.";
         if (empty($currentAddressStreetName)) $validationErrors[] = "Current Address Street Name is required.";
         if (empty($currentAddressBarangay)) $validationErrors[] = "Current Address Barangay is required.";
         if (empty($currentAddressCity)) $validationErrors[] = "Current Address Municipality/City is required.";
         if (empty($currentAddressProvince)) $validationErrors[] = "Current Address Province is required.";
         if (empty($currentAddressCountry)) $validationErrors[] = "Current Address Country is required.";
         if (empty($currentAddressZip)) $validationErrors[] = "Current Address Zip Code is required.";
         if ($sameAddress === 'no') {
             if (empty($permanentAddressHouseNoStreet)) $validationErrors[] = "Permanent Address House No/Street is required.";
             if (empty($permanentAddressStreetName)) $validationErrors[] = "Permanent Address Street Name is required.";
             if (empty($permanentAddressBarangay)) $validationErrors[] = "Permanent Address Barangay is required.";
             if (empty($permanentAddressCity)) $validationErrors[] = "Permanent Address Municipality/City is required.";
             if (empty($permanentAddressProvince)) $validationErrors[] = "Permanent Address Province is required.";
             if (empty($permanentAddressCountry)) $validationErrors[] = "Permanent Address Country is required.";
             if (empty($permanentAddressZip)) $validationErrors[] = "Permanent Address Zip Code is required.";
         }
         if (empty($fatherLastName)) $validationErrors[] = "Father's Last Name is required.";
         if (empty($fatherFirstName)) $validationErrors[] = "Father's First Name is required.";
         if (empty($fatherContact)) $validationErrors[] = "Father's Contact Number is required.";
         if (empty($motherLastName)) $validationErrors[] = "Mother's Last Name is required.";
         if (empty($motherFirstName)) $validationErrors[] = "Mother's First Name is required.";
         if (empty($motherContact)) $validationErrors[] = "Mother's Contact Number is required.";


        if (!empty($validationErrors)) {
            // Redirect logic with validation errors (preserving hash)
            $redirect_url = 'dashboard.php';
            if(isset($_SERVER['HTTP_REFERER'])) {
                $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
                if(isset($referer_parts['fragment'])) { $redirect_url .= '#' . $referer_parts['fragment']; }
            }
            $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=add_manual_error&message=' . urlencode("Validation failed: " . implode(" ", $validationErrors));
            header("Location: " . $redirect_url);
            exit;
        }

        // SQL statement (ensure it matches your table structure)
        $sql = "INSERT INTO enrollments (
                    school_year, grade_level, returning_student, has_lrn, lrn,
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
                    status, submission_timestamp, section
                ) VALUES (
                    :school_year, :grade_level, :returning_student, :has_lrn, :lrn,
                    :student_last_name, :student_first_name, :student_middle_name, :student_extension_name,
                    :student_birthdate, :student_age, :student_sex, :student_place_of_birth, :student_mother_tongue,
                    :is_indigenous, :ip_community, :is_4ps_beneficiary, :household_id_4ps,
                    :has_disability, :disability_types, :disability_sub_types,
                    :current_address_house_no_street, :current_address_street_name, :current_address_barangay,
                    :current_address_city, :current_address_province, :current_address_country, :current_address_zip,
                    :permanent_address_same_as_current,
                    :permanent_address_house_no_street, :permanent_address_street_name, :permanent_address_barangay,
                    :permanent_address_city, :permanent_address_province, :permanent_address_country, :permanent_address_zip,
                    :father_last_name, :father_first_name, :father_middle_name, :father_contact,
                    :mother_last_name, :mother_first_name, :mother_middle_name, :mother_contact,
                    :guardian_last_name, :guardian_first_name, :guardian_middle_name, :guardian_contact, :guardian_relationship,
                    :status, :submission_timestamp, :section
                )";

        $stmt = $pdo->prepare($sql);
        // Bind parameters carefully, ensure all placeholders are matched
        $stmt->execute([
            ':school_year' => $schoolYear, ':grade_level' => $gradeLevel, ':returning_student' => $returning,
            ':has_lrn' => $hasLrn, ':lrn' => $lrn, // Use the potentially generated LRN
            ':student_last_name' => $lastName, ':student_first_name' => $firstName, ':student_middle_name' => $middleName, ':student_extension_name' => $extensionName,
            ':student_birthdate' => $birthdate, ':student_age' => $age, ':student_sex' => $sex, ':student_place_of_birth' => $placeOfBirth, ':student_mother_tongue' => $motherTongue,
            ':is_indigenous' => $indigenous, ':ip_community' => $ipCommunity, ':is_4ps_beneficiary' => $is4ps, ':household_id_4ps' => $householdId4ps,
            ':has_disability' => $hasDisability, ':disability_types' => $disabilityTypesString, ':disability_sub_types' => $disabilitySubTypesString,
            ':current_address_house_no_street' => $currentAddressHouseNoStreet, ':current_address_street_name' => $currentAddressStreetName, ':current_address_barangay' => $currentAddressBarangay,
            ':current_address_city' => $currentAddressCity, ':current_address_province' => $currentAddressProvince, ':current_address_country' => $currentAddressCountry, ':current_address_zip' => $currentAddressZip,
            ':permanent_address_same_as_current' => $sameAddress,
            ':permanent_address_house_no_street' => $permanentAddressHouseNoStreet, ':permanent_address_street_name' => $permanentAddressStreetName, ':permanent_address_barangay' => $permanentAddressBarangay,
            ':permanent_address_city' => $permanentAddressCity, ':permanent_address_province' => $permanentAddressProvince, ':permanent_address_country' => $permanentAddressCountry, ':permanent_address_zip' => $permanentAddressZip,
            ':father_last_name' => $fatherLastName, ':father_first_name' => $fatherFirstName, ':father_middle_name' => $fatherMiddleName, ':father_contact' => $fatherContact,
            ':mother_last_name' => $motherLastName, ':mother_first_name' => $motherFirstName, ':mother_middle_name' => $motherMiddleName, ':mother_contact' => $motherContact,
            ':guardian_last_name' => $guardianLastName, ':guardian_first_name' => $guardianFirstName, ':guardian_middle_name' => $guardianMiddleName, ':guardian_contact' => $guardianContact, ':guardian_relationship' => $guardianRelationship,
            ':status' => $status, ':submission_timestamp' => $submissionTimestamp, ':section' => $section
        ]);

        // Redirect logic on success (preserving hash)
        $redirect_url = 'dashboard.php';
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
            if(isset($referer_parts['fragment'])) { $redirect_url .= '#' . $referer_parts['fragment']; }
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=added_manually';
        header("Location: " . $redirect_url);
        exit;

    } catch (PDOException $e) {
        error_log("Manual Enrollment PDOException: " . $e->getMessage());
        // Redirect logic with DB error (preserving hash)
        $redirect_url = 'dashboard.php';
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
            if(isset($referer_parts['fragment'])) { $redirect_url .= '#' . $referer_parts['fragment']; }
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=add_manual_error&message=' . urlencode("Database error: Could not save enrollment.");
        header("Location: " . $redirect_url);
        exit;
    } catch (Exception $e) {
        error_log("Manual Enrollment Exception: " . $e->getMessage());
        // Redirect logic with general error (preserving hash)
        $redirect_url = 'dashboard.php';
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
            if(isset($referer_parts['fragment'])) { $redirect_url .= '#' . $referer_parts['fragment']; }
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'action=add_manual_error&message=' . urlencode("An unexpected error occurred.");
        header("Location: " . $redirect_url);
        exit;
    }
}

// --- Fetch enrollments for display ---
try {
    // Added section_id and installment_months for modal population
    $stmt = $pdo->query("SELECT *, section_id, installment_months FROM enrollments ORDER BY submission_timestamp DESC");
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching enrollments for dashboard: " . $e->getMessage());
    $enrollments = []; // Ensure $enrollments is an array even on error
    // Consider setting an error message to display on the page
}

// --- Calculate Counts ---
$total = count($enrollments);
$enrolled_count = count(array_filter($enrollments, fn($e) => ($e['status'] ?? '') === 'Enrolled'));
$pending_count = count(array_filter($enrollments, fn($e) => ($e['status'] ?? '') === 'Pending'));
$declined_count = count(array_filter($enrollments, fn($e) => in_array(($e['status'] ?? ''), ['Declined', 'For Verification'], true)));

// --- Get Action Feedback Messages ---
$action_feedback_message = '';
$action_feedback_type = ''; // 'success' or 'danger'
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'updated':
            $action_feedback_message = "Enrollment status updated successfully.";
            $action_feedback_type = 'success';
            break;
        case 'added_manually':
            $action_feedback_message = "Enrollment added manually successfully.";
            $action_feedback_type = 'success';
            break;
        case 'update_status_error':
        case 'add_manual_error':
            $action_feedback_message = $_GET['message'] ?? 'An error occurred.';
            $action_feedback_type = 'danger';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Faculty Dashboard - FFCS</title>
  <link rel="stylesheet" href="css/dashboard.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <!-- moved dashboard styles into css/dashboard.css -->
    <script>
        // Add .is-admin class to the root element when the current user is admin.
        (function(){ if (<?php echo $is_admin ? 'true' : 'false'; ?>) document.documentElement.classList.add('is-admin'); })();
    </script>
</head>
<body>
  <div class="header">
    <div class="header-left">
      <img src="FFCS Pics/FFCS_Logo(clean).png" alt="FFCS Logo" class="logo">
      <div class="school-name">Faith Family Christian School</div>
    </div>
    <div class="header-right">
      <div class="user-info">
        <div class="user-name" id="faculty-name">Faculty Name</div>
        <div class="user-role"><?php echo ucfirst(htmlspecialchars($faculty_role)); ?> Role</div>
      </div>
      <button class="logout-btn" id="logout-btn">Logout</button>
    </div>
  </div>

  <div class="main-container">
    <div class="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a class="nav-link active" data-section="dashboard-section">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <?php if ($is_admin): ?>

            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-cogs"></i></span>
                    <span class="nav-text">Administration</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item"><a class="nav-link" href="admin/manage_users.php">User Management</a></li>
                </ul>
            </li>

            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-desktop"></i></span>
                    <span class="nav-text">System Management</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item"><a class="nav-link" href="admin/manage_payments.php">Payment Management</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_notifications.php">Notification Logs</a></li>
                    <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                            <span class="nav-icon"><i class="fas fa-spinner"></i></span> 
                            <span class="nav-text">Loading</span> 
                            <i class="fas fa-chevron-right submenu-arrow"></i>
                        </a>
                        <ul class="submenu">
                            <li class="nav-item"><a class="nav-link" href="admin/manage_sections.php">Section & Subjects</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin/manage_teachers.php">Manage Teachers</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin/manage_schedules.php">Manage Schedules</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin/manage_time_slots.php">Manage Time Slots</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            
            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="nav-text">Enrollment Records</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    
                    <li class="nav-item"><a class="nav-link" href="admin/enrollment_history.php">Enrollment History</a></li>
                </ul>
            </li>
            
            <li class="nav-item"> <a class="nav-link" href="admin/manage_analytics.php">
                    <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                    <span class="nav-text">Enrollment Analytics</span>
                </a>
            </li>
            

            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
                    <span class="nav-text">Manage Content</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item"><a class="nav-link" href="admin/manage_hero.php">Manage Hero Section</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_welcome.php">Manage Welcome Section</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_enrollment_form.php">Manage Enrollment Form</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/edit_mission_vision.php">Manage Mission/Vision</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_learning_paths.php">Manage Learning Paths</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_programs.php">Manage Programs</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_news.php">Manage News & Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/edit_contact_info.php">Manage Contact Info</a></li>
                </ul>
            </li>

            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-running"></i></span>
                    <span class="nav-text">Activities</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item"><a class="nav-link" href="admin/manage_activities.php">Manage Activities</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_categories.php">Manage Categories</a></li>
                </ul>
            </li>

            <li class="nav-item has-submenu"> <a class="nav-link submenu-toggle">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-text">Community</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item"><a class="nav-link" href="admin/manage_feedback.php">Feedbacks</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_announcements.php">Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/manage_faqs.php">FAQs</a></li>
                </ul>
            </li>

            <?php endif; // End admin-only check ?>
        </ul>
    </div>

    <div class="content">
        <div id="dashboard-section" class="content-section active-section">
            <div class="dashboard-header-row" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <h1 class="page-title" style="margin:0;">Enrollment Dashboard</h1>
                <!-- header print buttons removed; cards trigger preview/print now -->
            </div>

            <?php if ($action_feedback_message): ?>
            <div class="alert alert-<?= htmlspecialchars($action_feedback_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($action_feedback_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

                                    <div class="dashboard-cards">
                                            <div class="card total-students" data-print-filter="all" tabindex="0" role="button" aria-label="Print Total Applications">
                                                <div class="card-title">Total Applications</div>
                                                <div class="card-value"><?= htmlspecialchars($total) ?></div>
                                            </div>
                                            <div class="card enrolled-applications" data-print-filter="Enrolled" tabindex="0" role="button" aria-label="Print Enrolled Applications">
                                                <div class="card-title">Enrolled Applications</div>
                                                <div class="card-value"><?= htmlspecialchars($enrolled_count) ?></div>
                                            </div>
                                            <div class="card pending-applications" data-print-filter="Pending" tabindex="0" role="button" aria-label="Print Pending Applications">
                                                <div class="card-title">Pending Applications</div>
                                                <div class="card-value"><?= htmlspecialchars($pending_count) ?></div>
                                            </div>
                                            <div class="card declined-applications" data-print-filter="For Verification" tabindex="0" role="button" aria-label="Print For Verification Applications">
                                                <div class="card-title">For Verification Applications</div>
                                                <div class="card-value"><?= htmlspecialchars($declined_count) ?></div>
                                            </div>
                                    </div>

            <div class="table-container table-scrollable">
                <div class="table-header">
                  <h2 class="table-title">Recent Applications</h2>
                  <div class="search-container">
                    <input type="text" id="search-input-dashboard" class="search-input" placeholder="Search applications...">
                    <?php if ($is_admin): ?>
                    <button id="add-enrollee-btn" class="btn btn-primary admin-only-button mb-3"><i class="fas fa-plus"></i> Add Manually</button>
                    <?php endif; ?>
                  </div>
                </div>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>LRN</th>
                      <th>Last Name</th>
                      <th>First Name</th>
                      <th>Grade Level</th>
                      <th>Submission Date</th>
                      <th>Status</th>
                      <th class="<?php echo $is_admin ? '' : 'admin-only-action-header'; ?>">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="applications-list-table-body">
                    <?php if (empty($enrollments)): ?>
                      <tr><td colspan="<?php echo $is_admin ? '7' : '6'; ?>">No enrollment applications found.</td></tr>
                    <?php else: ?>
                      <?php foreach ($enrollments as $e): ?>
                      <tr>
                        <td><?= htmlspecialchars($e['lrn'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($e['student_last_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['student_first_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['grade_level'] ?? '') ?></td>
                        <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($e['submission_timestamp'] ?? ''))) ?></td>
                        <td>
                          <?php
                            $rawStatus = strtolower($e['status'] ?? 'unknown');
                            // Treat legacy 'Declined' and new 'For Verification' as the same display category
                            $displayStatus = ($rawStatus === 'declined') ? 'For Verification' : ($e['status'] ?? 'Unknown');
                            $statusText = htmlspecialchars($displayStatus);
                            $statusClass = '';
                            switch ($rawStatus) {
                                case 'enrolled': $statusClass = 'status-enrolled'; break;
                                case 'declined':
                                case 'for verification': $statusClass = 'status-declined'; break;
                                case 'pending': $statusClass = 'status-pending'; break;
                                default: $statusClass = 'status-unknown';
                            }
                          ?>
                          <span class="status-badge <?php echo $statusClass; ?>">
                            <span class="status-dot"></span>
                            <?php echo $statusText; ?>
                          </span>
                        </td>
                        <td class="<?php echo $is_admin ? '' : 'admin-only-action-cell'; ?>">
                            <?php if ($is_admin): ?>
                            <button class="view-btn" data-id="<?= htmlspecialchars($e['id'] ?? '') ?>">View</button>
                            <button class="delete-btn" data-id="<?= htmlspecialchars($e['id'] ?? '') ?>" data-lrn="<?= htmlspecialchars($e['lrn'] ?? 'N/A') ?>">Delete</button>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
            </div>
            </div>
        </div>
        <div id="user-manage-section" class="content-section">...</div>
        <div id="manage-hero-section" class="content-section">...</div>
    </div>
  </div>

<div class="modal" id="viewModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header-custom d-flex align-items-center justify-content-between">
                <h2 class="modal-main-title">Enrollment Details</h2>
                <div style="display:flex;gap:0.5rem;align-items:center">
                    <button type="button" id="downloadInfoBtn" class="btn btn-sm btn-outline-primary" title="Download information (excludes uploaded files)"><i class="fas fa-download"></i> Download</button>
                    <button type="button" class="close-modal-button" id="closeViewModalBtn" aria-label="Close">&times;</button>
                </div>
            </div>
            <div id="modal-details-content" class="modal-body-scrollable">
                </div>
            <div id="modal-navigation" class="modal-navigation" style="display: none;">
                <button id="modal-prev-btn" class="btn btn-secondary">Previous</button>
                <span id="modal-page-indicator"></span>
                <button id="modal-next-btn" class="btn btn-primary">Next</button>
            </div>
            <form id="update-enrollment-form" method="POST" action="api/enrollments/update_financials.php" style="display: none;">
                <input type="hidden" name="enrollment_id" id="modal-enrollment-id">
                </form>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal" id="printPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h2 class="modal-main-title" id="printPreviewTitle">Preview Report</h2>
                <button type="button" class="close-modal-button" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body-scrollable" id="printPreviewBody" style="max-height:65vh; overflow:auto; padding:1rem">
                <!-- Preview content injected here -->
            </div>
            <div class="modal-footer" style="gap:0.5rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="printPreviewPrintBtn" class="btn btn-primary">Print</button>
            </div>
        </div>
    </div>
</div>

  <?php if ($is_admin): ?>
  <div class="modal" id="addManualModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header-custom">
          <h2 class="modal-main-title">Add Enrollment Manually</h2>
          <button type="button" class="close-modal-button" id="closeAddManualModalBtn" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="dashboard.php"> <div id="add-manual-modal-details-content" class="modal-body-scrollable">
                <div class="form-section">
                    <h3 class="form-section-title">Basic Enrollment Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="manual_school_year">School Year *</label>
                            <input type="text" id="manual_school_year" name="manual_school_year" class="form-control" placeholder="YYYY-YYYY" required>
                        </div>
                        <div class="form-group">
                            <label for="manual_grade_level">Grade Level Applying For *</label>
                            <select id="manual_grade_level" name="manual_grade_level" class="form-select" required>
                                <option value="">Select Grade Level</option>
                                <option value="Playschool">Playschool</option><option value="Kinder 1 & 2">Kinder 1 & 2</option><option value="Elementary">Elementary</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="manual_returning_student">Returning (Balik-Aral) *</label>
                            <select id="manual_returning_student" name="manual_returning_student" class="form-select" required>
                                 <option value="no" selected>No</option>
                                 <option value="yes">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Learner Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="manual_lrn">Learner Reference No. (LRN)</label>
                            <input type="text" id="manual_lrn" name="manual_lrn" class="form-control" pattern="\d{12}" title="LRN must be 12 digits" maxlength="12">
                        </div>
                        <div class="form-group">
                            <label>With LRN? *</label>
                            <div class="radio-group">
                                <label><input type="radio" name="manual_has_lrn" value="yes"> Yes</label>
                                <label><input type="radio" name="manual_has_lrn" value="no" checked> No</label>
                            </div>
                        </div>
                        <div class="form-group"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="manual_student_last_name">Last Name *</label><input type="text" id="manual_student_last_name" name="manual_student_last_name" class="form-control" required></div>
                        <div class="form-group"><label for="manual_student_first_name">First Name *</label><input type="text" id="manual_student_first_name" name="manual_student_first_name" class="form-control" required></div>
                        <div class="form-group"><label for="manual_student_middle_name">Middle Name</label><input type="text" id="manual_student_middle_name" name="manual_student_middle_name" class="form-control"></div>
                    </div>
                    <div class="form-row">
                         <div class="form-group"><label for="manual_student_extension_name">Extension Name</label><input type="text" id="manual_student_extension_name" name="manual_student_extension_name" class="form-control"></div>
                        <div class="form-group"><label for="manual_student_birthdate">Date of Birth *</label><input type="date" id="manual_student_birthdate" name="manual_student_birthdate" class="form-control" required></div>
                         <div class="form-group"><label for="manual_student_age">Age *</label><input type="number" id="manual_student_age" name="manual_student_age" class="form-control" min="3" max="99" required readonly></div>
                        <div class="form-group"><label for="manual_student_sex">Sex *</label><select id="manual_student_sex" name="manual_student_sex" class="form-select" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label for="manual_student_place_of_birth">Place of Birth *</label><input type="text" id="manual_student_place_of_birth" name="manual_student_place_of_birth" class="form-control" required></div>
                        <div class="form-group"><label for="manual_student_mother_tongue">Mother Tongue *</label><input type="text" id="manual_student_mother_tongue" name="manual_student_mother_tongue" class="form-control" required></div>
                    </div>
                     <div class="form-row">
                         <div class="form-group"><label>Indigenous Peoples (IP)? *</label><div class="radio-group"><label><input type="radio" name="manual_is_indigenous" value="yes"> Yes</label><label><input type="radio" name="manual_is_indigenous" value="no" checked> No</label></div></div>
                        <div class="form-group"><label for="manual_ip_community">If Yes, specify:</label><input type="text" id="manual_ip_community" name="manual_ip_community" class="form-control"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>4Ps Beneficiary? *</label><div class="radio-group"><label><input type="radio" name="manual_is_4ps_beneficiary" value="yes"> Yes</label><label><input type="radio" name="manual_is_4ps_beneficiary" value="no" checked> No</label></div></div>
                        <div class="form-group"><label for="manual_4ps_household_id">If Yes, 4Ps ID:</label><input type="text" id="manual_4ps_household_id" name="manual_4ps_household_id" class="form-control"></div>
                    </div>
                </div>

                 <div class="form-section">
                     <h3 class="form-section-title">Learner with Disability</h3>
                     <div class="form-row">
                          <div class="form-group" style="grid-column: 1 / -1;">
                              <label>Has Disability? *</label>
                              <div class="radio-group">
                                  <label><input type="radio" name="manual_has_disability" value="yes" onclick="toggleManualDisabilityFields(true)"> Yes</label>
                                  <label><input type="radio" name="manual_has_disability" value="no" checked onclick="toggleManualDisabilityFields(false)"> No</label>
                              </div>
                          </div>
                      </div>
                     <div id="manual_disability_details_container" style="display: none;">
                         <label>Specify Disability (Check all applicable and add details):</label>
                         <div class="disability-options-grid">
                             <div class="disability-item">
                                 <label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Visual Impairment"> Visual Impairment</label>
                                 <input type="text" name="manual_disability_sub_types[visual_impairment]" class="form-control form-control-sm disability-subtype-input" placeholder="e.g., Blind, Low Vision">
                             </div>
                             <div class="disability-item">
                                 <label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Hearing Impairment"> Hearing Impairment</label>
                                 <input type="text" name="manual_disability_sub_types[hearing_impairment]" class="form-control form-control-sm disability-subtype-input" placeholder="e.g., Deaf, Hard of Hearing">
                             </div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Learning Disability"> Learning Disability</label><input type="text" name="manual_disability_sub_types[learning_disability]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Intellectual Disability"> Intellectual Disability</label><input type="text" name="manual_disability_sub_types[intellectual_disability]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Autism Spectrum Disorder"> Autism Spectrum Disorder</label><input type="text" name="manual_disability_sub_types[autism_spectrum_disorder]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Emotional-Behavioral Disorder"> Emotional-Behavioral Disorder</label><input type="text" name="manual_disability_sub_types[emotional_behavioral_disorder]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Orthopedic/Physical Handicap"> Orthopedic/Physical Handicap</label><input type="text" name="manual_disability_sub_types[orthopedic_physical_handicap]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Speech/Language Disorder"> Speech/Language Disorder</label><input type="text" name="manual_disability_sub_types[speech_language_disorder]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Cerebral Palsy"> Cerebral Palsy</label><input type="text" name="manual_disability_sub_types[cerebral_palsy]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Special Health Problem/Chronic Disease"> Special Health Problem/Chronic Disease</label><input type="text" name="manual_disability_sub_types[special_health_problem]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                              <div class="disability-item"><label class="checkbox-label"><input type="checkbox" name="manual_disability_types[]" value="Multiple Disorder"> Multiple Disorder</label><input type="text" name="manual_disability_sub_types[multiple_disorder]" class="form-control form-control-sm disability-subtype-input" placeholder="Sub-type"></div>
                         </div>
                     </div>
                 </div>

                <div class="form-section">
                    <h3 class="form-section-title">Address Information</h3>
                    <div class="form-subsection">
                        <h4 class="form-subsection-title">Current Address</h4>
                         <div class="form-row">
                            <div class="form-group"><label for="manual_current_address_house_no_street">House No./Street *</label><input type="text" id="manual_current_address_house_no_street" name="manual_current_address_house_no_street" class="form-control" required></div>
                            <div class="form-group"><label for="manual_current_address_street_name">Street Name *</label><input type="text" id="manual_current_address_street_name" name="manual_current_address_street_name" class="form-control" required></div>
                            <div class="form-group"><label for="manual_current_address_barangay">Barangay *</label><input type="text" id="manual_current_address_barangay" name="manual_current_address_barangay" class="form-control" required></div>
                         </div>
                         <div class="form-row">
                            <div class="form-group"><label for="manual_current_address_city">Municipality/City *</label><input type="text" id="manual_current_address_city" name="manual_current_address_city" class="form-control" required></div>
                            <div class="form-group"><label for="manual_current_address_province">Province *</label><input type="text" id="manual_current_address_province" name="manual_current_address_province" class="form-control" required></div>
                            <div class="form-group"><label for="manual_current_address_country">Country *</label><input type="text" id="manual_current_address_country" name="manual_current_address_country" class="form-control" value="Philippines" required></div>
                            <div class="form-group"><label for="manual_current_address_zip">Zip Code *</label><input type="text" id="manual_current_address_zip" name="manual_current_address_zip" class="form-control" pattern="\d{4,}" required></div>
                         </div>
                    </div>
                     <div class="form-group mt-3">
                        <label>Is Permanent Address same with Current Address? *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="manual_permanent_address_same_as_current" value="yes" checked onclick="toggleManualPermanentAddress(false)"> Yes</label>
                            <label><input type="radio" name="manual_permanent_address_same_as_current" value="no" onclick="toggleManualPermanentAddress(true)"> No</label>
                        </div>
                    </div>
                    <div id="manual_permanent_address_fields" class="form-subsection mt-3" style="display: none;">
                        <h4 class="form-subsection-title">Permanent Address</h4>
                        <div class="form-row">
                           <div class="form-group"><label for="manual_permanent_address_house_no_street">House No./Street *</label><input type="text" id="manual_permanent_address_house_no_street" name="manual_permanent_address_house_no_street" class="form-control"></div>
                           <div class="form-group"><label for="manual_permanent_address_street_name">Street Name *</label><input type="text" id="manual_permanent_address_street_name" name="manual_permanent_address_street_name" class="form-control"></div>
                           <div class="form-group"><label for="manual_permanent_address_barangay">Barangay *</label><input type="text" id="manual_permanent_address_barangay" name="manual_permanent_address_barangay" class="form-control"></div>
                       </div>
                       <div class="form-row">
                           <div class="form-group"><label for="manual_permanent_address_city">Municipality/City *</label><input type="text" id="manual_permanent_address_city" name="manual_permanent_address_city" class="form-control"></div>
                           <div class="form-group"><label for="manual_permanent_address_province">Province *</label><input type="text" id="manual_permanent_address_province" name="manual_permanent_address_province" class="form-control"></div>
                           <div class="form-group"><label for="manual_permanent_address_country">Country *</label><input type="text" id="manual_permanent_address_country" name="manual_permanent_address_country" class="form-control" value="Philippines"></div>
                           <div class="form-group"><label for="manual_permanent_address_zip">Zip Code *</label><input type="text" id="manual_permanent_address_zip" name="manual_permanent_address_zip" class="form-control" pattern="\d{4,}"></div>
                       </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Parent's/Guardian's Information</h3>
                    <div class="form-subsection">
                         <h4 class="form-subsection-title">Father</h4>
                         <div class="form-row">
                             <div class="form-group"><label for="manual_father_last_name">Last Name *</label><input type="text" id="manual_father_last_name" name="manual_father_last_name" class="form-control" required></div>
                             <div class="form-group"><label for="manual_father_first_name">First Name *</label><input type="text" id="manual_father_first_name" name="manual_father_first_name" class="form-control" required></div>
                             <div class="form-group"><label for="manual_father_middle_name">Middle Name</label><input type="text" id="manual_father_middle_name" name="manual_father_middle_name" class="form-control"></div>
                             <div class="form-group"><label for="manual_father_contact">Contact No. *</label><input type="tel" id="manual_father_contact" name="manual_father_contact" class="form-control" pattern="[0-9+]{10,15}" required></div>
                         </div>
                     </div>
                      <div class="form-subsection">
                         <h4 class="form-subsection-title">Mother</h4>
                         <div class="form-row">
                             <div class="form-group"><label for="manual_mother_last_name">Last Name *</label><input type="text" id="manual_mother_last_name" name="manual_mother_last_name" class="form-control" required></div>
                             <div class="form-group"><label for="manual_mother_first_name">First Name *</label><input type="text" id="manual_mother_first_name" name="manual_mother_first_name" class="form-control" required></div>
                             <div class="form-group"><label for="manual_mother_middle_name">Middle Name</label><input type="text" id="manual_mother_middle_name" name="manual_mother_middle_name" class="form-control"></div>
                             <div class="form-group"><label for="manual_mother_contact">Contact No. *</label><input type="tel" id="manual_mother_contact" name="manual_mother_contact" class="form-control" pattern="[0-9+]{10,15}" required></div>
                         </div>
                     </div>
                      <div class="form-subsection">
                         <h4 class="form-subsection-title">Guardian (If not Parent)</h4>
                         <div class="form-row">
                             <div class="form-group"><label for="manual_guardian_last_name">Last Name</label><input type="text" id="manual_guardian_last_name" name="manual_guardian_last_name" class="form-control"></div>
                             <div class="form-group"><label for="manual_guardian_first_name">First Name</label><input type="text" id="manual_guardian_first_name" name="manual_guardian_first_name" class="form-control"></div>
                             <div class="form-group"><label for="manual_guardian_middle_name">Middle Name</label><input type="text" id="manual_guardian_middle_name" name="manual_guardian_middle_name" class="form-control"></div>
                         </div>
                          <div class="form-row">
                             <div class="form-group"><label for="manual_guardian_contact">Contact No.</label><input type="tel" id="manual_guardian_contact" name="manual_guardian_contact" class="form-control" pattern="[0-9+]{10,15}"></div>
                             <div class="form-group"><label for="manual_guardian_relationship">Relationship</label><select id="manual_guardian_relationship" name="manual_guardian_relationship" class="form-select"><option value="">Select</option><option value="Grandparent">Grandparent</option><option value="Aunt/Uncle">Aunt/Uncle</option><option value="Sibling">Sibling</option><option value="Other Relative">Other Relative</option><option value="Legal Guardian (Non-relative)">Legal Guardian (Non-relative)</option></select></div>
                             <div class="form-group"></div>
                         </div>
                     </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelAddManualBtn">Cancel</button>
                <button type="submit" name="add_manual_enrollment" class="btn btn-success">Save</button>
            </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>


  <div class="footer">
    &copy; <span id="current-year"><?= date('Y') ?></span> Faith Family Christian School. All rights reserved.
  </div>

    <!-- Enrollment Analytics Modal -->
    <div class="modal" id="enrollmentAnalyticsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header-custom">
                    <h2 class="modal-main-title">Enrollees by Grade (Last 12 months)</h2>
                    <button type="button" class="close-modal-button" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body-scrollable" style="padding:1rem;">
                    <canvas id="enrollmentByGradeChart" width="800" height="360"></canvas>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    // Pass PHP data to JS
    const enrollmentsData = <?php echo json_encode($enrollments); ?>;
    const isAdmin = <?php echo json_encode($is_admin); ?>;
  </script>
  <script src="js/dashboard.js" defer></script>
   <script>
       // --- Scripts for Manual Add Modal ---

       // Function to calculate age for manual modal
       function calculateManualAge() {
           const birthdateInput = document.getElementById('manual_student_birthdate');
           const ageInput = document.getElementById('manual_student_age');
           if (birthdateInput && ageInput && birthdateInput.value) {
               try {
                   const birthDate = new Date(birthdateInput.value);
                   const today = new Date();
                   let age = today.getFullYear() - birthDate.getFullYear();
                   const m = today.getMonth() - birthDate.getMonth();
                   if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                       age--;
                   }
                   ageInput.value = age >= 0 ? age : '';
               } catch (e) {
                   ageInput.value = ''; // Clear if date is invalid
               }
           } else if (ageInput) {
               ageInput.value = '';
           }
       }

       // Function to toggle permanent address fields in manual modal
       function toggleManualPermanentAddress(show) {
           const fieldsDiv = document.getElementById('manual_permanent_address_fields');
           const inputs = fieldsDiv ? fieldsDiv.querySelectorAll('input, select') : [];
           if (fieldsDiv) {
               fieldsDiv.style.display = show ? 'block' : 'none';
               inputs.forEach(input => {
                   if (show) {
                       input.setAttribute('required', 'required');
                   } else {
                       input.removeAttribute('required');
                       // Optionally clear values and errors here
                   }
               });
           }
       }

        // Function to toggle disability fields in manual modal
       function toggleManualDisabilityFields(show) {
            const container = document.getElementById('manual_disability_details_container');
            if (container) {
                 container.style.display = show ? 'block' : 'none';
                 // Add logic to require/unrequire specific sub-type inputs if needed
            }
       }


       // Add event listener for birthdate change in manual modal
       const manualBirthdateInput = document.getElementById('manual_student_birthdate');
       if (manualBirthdateInput) {
           manualBirthdateInput.addEventListener('change', calculateManualAge);
       }

       // Add event listeners for permanent address radio buttons in manual modal
        const manualSameAddressRadios = document.querySelectorAll('input[name="manual_permanent_address_same_as_current"]');
        manualSameAddressRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                toggleManualPermanentAddress(this.value === 'no');
            });
        });

       // Initial state setup for manual modal (in case of page reload with errors, etc.)
       document.addEventListener('DOMContentLoaded', () => {
           const initialSameAddress = document.querySelector('input[name="manual_permanent_address_same_as_current"]:checked');
           if (initialSameAddress) {
                toggleManualPermanentAddress(initialSameAddress.value === 'no');
           }
            const initialDisability = document.querySelector('input[name="manual_has_disability"]:checked');
            if (initialDisability) {
                 toggleManualDisabilityFields(initialDisability.value === 'yes');
            }
            calculateManualAge(); // Calculate age on load if birthdate is pre-filled

             // Auto-hide action feedback alerts
             const actionAlerts = document.querySelectorAll('.alert-dismissible');
             actionAlerts.forEach(alert => {
                setTimeout(() => {
                   // Use Bootstrap's Alert instance to close it
                   const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                   if (bsAlert) bsAlert.close();
                }, 5000); // Hide after 5 seconds
             });

       });


      // --- Printing helpers (Full + Filtered) ---
            function createPrintableAndPrint(rows, title) {
                    title = title || 'Enrollments Report';
                    const asOf = new Date().toLocaleString();
                    const cols = ['LRN','Last Name','First Name','Grade Level','Submission Date','Status','Section'];

                    function escapeHtml(str){ if (str === null || str === undefined) return ''; return String(str).replace(/[&<>\"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

                    const buildRow = (r) => {
                            const submission = r.submission_timestamp ? new Date(r.submission_timestamp).toLocaleString() : '';
                            return `<tr><td>${escapeHtml(r.lrn||'')}</td><td>${escapeHtml(r.student_last_name||'')}</td><td>${escapeHtml(r.student_first_name||'')}</td><td>${escapeHtml(r.grade_level||'')}</td><td>${escapeHtml(submission)}</td><td>${escapeHtml(r.status||'')}</td><td>${escapeHtml(r.section||'')}</td></tr>`;
                    };

                    const tableRows = (rows && rows.length) ? rows.map(buildRow).join('\n') : '<tr><td colspan="7">No records</td></tr>';

                    // Build a cleaner, print-friendly HTML with logo and improved table styling
                    const printable = `<!doctype html>
                        <html>
                        <head>
                            <meta charset="utf-8"/>
                            <title>${escapeHtml(title)}</title>
                            <style>
                                @page { margin: 18mm; }
                                body{font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color:#111; margin:0}
                                .container{padding:18px}
                                .header{display:flex;align-items:center;gap:12px;margin-bottom:10px}
                                .logo{width:72px;height:auto;border-radius:6px}
                                .school-name{font-size:18px;font-weight:700;color:#0b5ed7}
                                .report-title{font-size:14px;margin-top:2px;color:#333}
                                .meta{color:#6b7280;font-size:12px;margin-top:6px}
                                .summary{margin:14px 0;font-size:13px}
                                table{width:100%;border-collapse:collapse;margin-top:6px;font-size:12px}
                                thead th{background:#0b5ed7;color:#fff;padding:10px 8px;text-align:left;font-weight:700}
                                tbody td{padding:8px;border:1px solid #e6eef8;vertical-align:top}
                                tbody tr:nth-child(even){background:#fbfdff}
                                .no-records{color:#666;padding:12px;text-align:center}
                                footer{margin-top:18px;font-size:11px;color:#6b7280}
                                @media print{
                                    thead th{ -webkit-print-color-adjust: exact; background:#0b5ed7;color:#fff }
                                    .container{padding:0}
                                }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <img class="logo" src="${location.origin + location.pathname.replace(/\/[^/]*$/, '')}/FFCS Pics/logo_monte_cristo.jpg" alt="FFCS Logo" onerror="this.style.display='none'" />
                                    <div>
                                        <div class="school-name">Faith Family Christian School</div>
                                        <div class="report-title">${escapeHtml(title)}</div>
                                        <div class="meta">Generated: ${escapeHtml(asOf)} — ${rows ? rows.length : 0} record(s)</div>
                                    </div>
                                </div>

                                <div style="overflow:auto">
                                    <table>
                                        <thead>
                                            <tr>${cols.map(c=>'<th>'+escapeHtml(c)+'</th>').join('')}</tr>
                                        </thead>
                                        <tbody>
                                            ${tableRows}
                                        </tbody>
                                    </table>
                                </div>

                                <footer>Generated by FFCS Enrollment System</footer>
                            </div>
                        </body>
                        </html>`;

                    const w = window.open('', '_blank');
                    if (!w) { alert('Unable to open print window (popup blocked). Allow popups and try again.'); return; }
                    w.document.open(); w.document.write(printable); w.document.close();
                    // Wait for images and styles to load before printing
                    setTimeout(()=>{ try { w.focus(); w.print(); } catch(e){ console.error(e); } }, 500);
            }

      function printEnrollments(filter) {
          if (!enrollmentsData) { alert('No enrollment data available'); return; }
          if (!filter || filter === 'all') {
              createPrintableAndPrint(enrollmentsData, 'All Enrollments');
              return;
          }
          const rows = enrollmentsData.filter(r => (r.status||'').toLowerCase() === (filter||'').toLowerCase());
          createPrintableAndPrint(rows, filter + ' Applications');
      }

      function printFilteredReport() {
          const qEl = document.getElementById('search-input-dashboard');
          const q = qEl ? (qEl.value||'').trim().toLowerCase() : '';
          if (!q) {
              // If there's no search term, prompt to print all or cancel
              if (!confirm('No search term entered. Do you want to print the full report instead?')) return;
              printEnrollments('all');
              return;
          }
          const rows = (enrollmentsData||[]).filter(r => {
              const hay = [r.lrn, r.student_last_name, r.student_first_name, r.grade_level, r.status, r.section].map(x => (x||'').toString().toLowerCase());
              return hay.some(field => field.indexOf(q) !== -1);
          });
          createPrintableAndPrint(rows, 'Filtered Applications ("' + q + '")');
      }

      // Attach click handlers (clickable cards)
      document.addEventListener('DOMContentLoaded', () => {
          // Make the summary cards clickable for printing
          const printableCards = document.querySelectorAll('.card[data-print-filter]');
          printableCards.forEach(card => {
              const filter = card.getAttribute('data-print-filter');
              card.addEventListener('click', () => {
                  // Build rows for preview then open modal
                  let rows;
                  if (filter === 'all') rows = enrollmentsData || [];
                  else rows = (enrollmentsData || []).filter(r => (r.status||'').toLowerCase() === (filter||'').toLowerCase());
                  const title = (filter === 'all') ? 'All Enrollments' : (filter + ' Applications');
                  showPrintPreview(rows, title);
              });
              // keyboard accessibility: Enter or Space triggers the same
              card.addEventListener('keydown', (ev) => {
                  if (ev.key === 'Enter' || ev.key === ' ') {
                      ev.preventDefault();
                      let rows;
                      if (filter === 'all') rows = enrollmentsData || [];
                      else rows = (enrollmentsData || []).filter(r => (r.status||'').toLowerCase() === (filter||'').toLowerCase());
                      const title = (filter === 'all') ? 'All Enrollments' : (filter + ' Applications');
                      showPrintPreview(rows, title);
                  }
              });
          });
      });

      // --- Preview modal helpers ---
      let _lastPreviewRows = [];
      let _lastPreviewTitle = '';

            function buildPreviewHtml(rows, title) {
                    const cols = ['LRN','Last Name','First Name','Grade Level','Submission Date','Status','Section'];
                    function escapeHtml(str){ if (str === null || str === undefined) return ''; return String(str).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }
                    const buildRow = (r) => {
                            const submission = r.submission_timestamp ? new Date(r.submission_timestamp).toLocaleString() : '';
                            return `<tr><td>${escapeHtml(r.lrn||'')}</td><td>${escapeHtml(r.student_last_name||'')}</td><td>${escapeHtml(r.student_first_name||'')}</td><td>${escapeHtml(r.grade_level||'')}</td><td>${escapeHtml(submission)}</td><td>${escapeHtml(r.status||'')}</td><td>${escapeHtml(r.section||'')}</td></tr>`;
                    };
                    const tableRows = (rows && rows.length) ? rows.map(buildRow).join('\n') : '<tr><td colspan="7" class="no-records">No records</td></tr>';

                    // Return a richer preview markup matching the printable layout but scaled for modal
                    return `
                        <div style="padding:8px 6px">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                                <img src="FFCS Pics/logo_monte_cristo.jpg" alt="logo" style="width:56px;border-radius:6px;object-fit:cover" onerror="this.style.display='none'" />
                                <div>
                                    <div style="font-weight:700;color:#0b5ed7;font-size:1rem">Faith Family Christian School</div>
                                    <div style="font-size:0.92rem;color:#333;margin-top:2px">${escapeHtml(title)}</div>
                                    <div style="font-size:0.82rem;color:#6b7280;margin-top:4px">Preview generated: ${new Date().toLocaleString()} — ${rows ? rows.length : 0} record(s)</div>
                                </div>
                            </div>
                            <div style="overflow:auto; border:1px solid #e6eef8; border-radius:6px; padding:6px; background:#fff">
                                <table class="preview-table" style="width:100%; border-collapse:collapse; font-size:13px">
                                    <thead>
                                        <tr>${cols.map(c=>'<th style="background:#0b5ed7;color:#fff;padding:8px;text-align:left">'+escapeHtml(c)+'</th>').join('')}</tr>
                                    </thead>
                                    <tbody>
                                        ${tableRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
            }

      function showPrintPreview(rows, title) {
          _lastPreviewRows = rows || [];
          _lastPreviewTitle = title || 'Preview Report';
          const body = document.getElementById('printPreviewBody');
          const titleEl = document.getElementById('printPreviewTitle');
          if (titleEl) titleEl.textContent = title || 'Preview Report';
          if (body) body.innerHTML = buildPreviewHtml(_lastPreviewRows, _lastPreviewTitle);
          // show bootstrap modal
          const modalEl = document.getElementById('printPreviewModal');
          if (modalEl) {
              const modal = new bootstrap.Modal(modalEl);
              modal.show();
          }
      }

      // Wire the Print button in preview modal
      document.addEventListener('DOMContentLoaded', () => {
          const printBtn = document.getElementById('printPreviewPrintBtn');
          if (printBtn) printBtn.addEventListener('click', () => {
              // Use createPrintableAndPrint to print the last preview rows
              createPrintableAndPrint(_lastPreviewRows, _lastPreviewTitle);
          });
      });

      // --- Enrollment Analytics: fetch data and render chart when modal opens ---
      document.addEventListener('DOMContentLoaded', () => {
          const analyticsBtn = document.getElementById('show-enrollment-analytics');
          const analyticsModalEl = document.getElementById('enrollmentAnalyticsModal');
          let enrollmentChart = null;

          async function loadAndRenderEnrollmentChart() {
              try {
                  const res = await fetch('/Final-School-Web/api/analytics/enrollees_by_grade.php');
                  const payload = await res.json();
                  if (!payload.success) {
                      console.error('Analytics API error', payload);
                      return;
                  }
                  const labels = payload.labels.map(l => {
                      // Convert YYYY-MM to more readable 'Mon YYYY'
                      const parts = l.split('-');
                      const d = new Date(parts[0], parseInt(parts[1],10)-1, 1);
                      return d.toLocaleString(undefined, { month: 'short', year: 'numeric' });
                  });

                  // Build Chart.js datasets with colors
                  const colors = [
                      '#007bff','#28a745','#ffc107','#dc3545','#6f42c1','#20c997','#fd7e14','#6610f2','#198754','#0d6efd'
                  ];
                  const datasets = payload.datasets.map((ds, idx) => ({
                      label: ds.label,
                      data: ds.data,
                      borderColor: colors[idx % colors.length],
                      backgroundColor: colors[idx % colors.length],
                      tension: 0.2,
                      fill: false,
                      pointRadius: 3
                  }));

                  const ctx = document.getElementById('enrollmentByGradeChart').getContext('2d');
                  if (enrollmentChart) {
                      enrollmentChart.data.labels = labels;
                      enrollmentChart.data.datasets = datasets;
                      enrollmentChart.update();
                      return;
                  }

                  enrollmentChart = new Chart(ctx, {
                      type: 'line',
                      data: { labels: labels, datasets: datasets },
                      options: {
                          responsive: true,
                          maintainAspectRatio: false,
                          plugins: {
                              legend: { position: 'bottom' }
                          },
                          scales: {
                              x: { title: { display: true, text: 'Month' } },
                              y: { title: { display: true, text: 'Enrollments' }, beginAtZero: true, ticks: { precision:0 } }
                          }
                      }
                  });

              } catch (err) {
                  console.error('Failed to load enrollment analytics', err);
              }
          }

          if (analyticsBtn && analyticsModalEl) {
              const modal = new bootstrap.Modal(analyticsModalEl);
              analyticsBtn.addEventListener('click', () => {
                  modal.show();
                  // Load data after showing to ensure canvas sizing
                  setTimeout(loadAndRenderEnrollmentChart, 200);
              });
          }
      });

   </script>


</body>
</html>