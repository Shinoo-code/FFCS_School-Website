<?php
// Include the database connection at the top
require_once 'api/db_connect.php';

// Fetch the dynamic instructions from the database
$instructions_html = '<p>Loading enrollment instructions...</p>'; // Default text
try {
    $stmt = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt->execute(['enrollment_instructions']);
    $content = $stmt->fetchColumn();
    
    if (!$content) {
        $instructions_html = '<p>Enrollment instructions are not available at this time. Please check back later.</p>';
    } else {
        $instructions_html = $content; // Use content from DB
    }
} catch (PDOException $e) {
    // In a production environment, you might log this error instead of showing it
    $instructions_html = '<p>Error loading instructions. Please contact the administrator.</p>';
    error_log("Failed to fetch enrollment instructions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enrollment - FFCS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/enrollment.css">
</head>
<body>

  <div id="enrollmentFormWrapper" class="disabled-form">
    <section id="enrollment" class="enrollment">
          <div class="container">
              <div class="enrollment-container">
                  <div class="enrollment-header">
                      <h2 class="enrollment-title">Enroll Your Child</h2>
                      <p class="enrollment-subtitle">Join our family of learners for the upcoming academic year</p>
                  </div>

                  <div class="enrollment-instructions">
                      <?php echo $instructions_html; // PHP dynamically inserts the content here ?>
                  </div>

                  <div class="success-message alert alert-success" style="display:none;" role="alert">
                      </div>

                  <div class="error-message alert alert-danger" style="display:none;" role="alert">
                      </div>

                  <form class="enrollment-form" method="POST" enctype="multipart/form-data" action="api/enrollments/submit.php" id="mainEnrollmentForm">
                      <div class="form-section">
                          <h3 class="form-section-title">Basic Enrollment Information</h3>
                          <div class="form-row">
                              <div class="form-group">
                                  <label for="school-year">School Year *</label>
                                  <select id="school-year" name="school-year" required>
                                      <option value="">Select School Year</option>
                                      <?php
                                          // Fetch the comma-separated list of school years from the database
                                          $school_years_str = '';
                                          try {
                                              $stmt_years = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
                                              $stmt_years->execute(['enrollment_school_years']);
                                              $school_years_str = $stmt_years->fetchColumn();
                                          } catch (PDOException $e) {
                                              error_log("Failed to fetch school years for enrollment form: " . $e->getMessage());
                                          }
                                          
                                          if ($school_years_str) {
                                              $school_years_array = explode(',', $school_years_str);
                                              foreach ($school_years_array as $year) {
                                                  $year_val = htmlspecialchars(trim($year));
                                                  if (!empty($year_val)) {
                                                      echo "<option value=\"{$year_val}\">{$year_val}</option>";
                                                  }
                                              }
                                          } else {
                                              echo '<option value="" disabled>No school years available</option>';
                                          }
                                      ?>
                                  </select>
                                  <span class="error-text" id="school-year-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="grade-level">Grade Level Applying For *</label>
                                  <select id="grade-level" name="grade-level" required>
                                      <option value="">Select Grade Level</option>
                                      <option value="Playschool">Playschool</option>
                                      <option value="Kinder 1 & 2">Kinder 1 & 2</option>
                                      <option value="Elementary">Elementary</option>
                                  </select>
                                  <span class="error-text" id="grade-level-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="returning">Returning (Balik-Aral) *</label>
                                  <select id="returning" name="returning" required>
                                       <option value="no" selected>No</option>
                                       <option value="yes">Yes</option>
                                  </select>
                                  <span class="error-text" id="returning-error"></span>
                              </div>
                          </div>
                      </div>

                      <div class="form-section">
                          <h3 class="form-section-title">Learner Information</h3>

                          <div class="form-row">
                               <div class="form-group" id="lrn-input-group">
                                  <label for="lrn">Learner Reference No. (LRN)</label>
                                  <input type="text" id="lrn" name="lrn" pattern="\d{12}" title="LRN must be 12 digits, if available." maxlength="12">
                                  <span class="error-text" id="lrn-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="has-lrn">With LRN? *</label>
                                  <div class="radio-group">
                                      <label class="radio-label">
                                          <input type="radio" name="has-lrn" value="yes"> Yes
                                      </label>
                                      <label class="radio-label">
                                          <input type="radio" name="has-lrn" value="no" checked> No
                                      </label>
                                  </div>
                                  <span class="error-text" id="has-lrn-error"></span>
                              </div>
                               <div class="form-group">
                                  <label for="is-transferee">Transferee? *</label>
                                  <div class="radio-group">
                                      <label class="radio-label">
                                          <input type="radio" name="is-transferee" value="yes"> Yes
                                      </label>
                                      <label class="radio-label">
                                          <input type="radio" name="is-transferee" value="no" checked> No
                                      </label>
                                  </div>
                                  <span class="error-text" id="is-transferee-error"></span>
                              </div>
                          </div>
                          
                          <div class="form-row" id="previous-school-group" style="display: none;">
                              <div class="form-group full-width">
                                  <label for="previous-school-name">Previous School Name *</label>
                                  <input type="text" id="previous-school-name" name="previous_school_name">
                                  <span class="error-text" id="previous-school-name-error"></span>
                              </div>
                          </div>

                          <div class="form-row">
                              <div class="form-group">
                                  <label for="last-name">Last Name *</label>
                                  <input type="text" id="last-name" name="last-name" required>
                                  <span class="error-text" id="last-name-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="first-name">First Name *</label>
                                  <input type="text" id="first-name" name="first-name" required>
                                  <span class="error-text" id="first-name-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="middle-name">Middle Name</label>
                                  <input type="text" id="middle-name" name="middle-name">
                                  <span class="error-text" id="middle-name-error"></span>
                              </div>
                          </div>

                          <div class="form-row">
                              <div class="form-group">
                                  <label for="student-email">Learner Email</label>
                                  <input type="email" id="student-email" name="student-email" maxlength="255" placeholder="example@domain.com">
                                  <div class="form-text">We'll use this to notify you about enrollment status and important updates.</div>
                                  <span class="error-text" id="student-email-error"></span>
                              </div>
                              <div class="form-group"></div>
                              <div class="form-group"></div>
                          </div>

                          <div class="form-row" id="ext-bdate-age-sex-row">
                               <div class="form-group">
                                  <label for="extension-name">Extension Name (Jr., III, etc.)</label>
                                  <input type="text" id="extension-name" name="extension-name">
                                  <span class="error-text" id="extension-name-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="birthdate">Date of Birth *</label>
                                  <input type="date" id="birthdate" name="birthdate" required>
                                  <span class="error-text" id="birthdate-error"></span>
                              </div>
                               <div class="form-group">
                                  <label for="age">Age * (Auto-calculates)</label>
                                  <input type="number" id="age" name="age" min="3" max="18" readonly required>
                                  <span class="error-text" id="age-error"></span>
                              </div>
                               <div class="form-group">
                                  <label for="sex">Sex *</label>
                                  <select id="sex" name="sex" required>
                                      <option value="">Select Sex</option>
                                      <option value="Male">Male</option>
                                      <option value="Female">Female</option>
                                  </select>
                                  <span class="error-text" id="sex-error"></span>
                              </div>
                          </div>

                           <div class="form-row" id="pob-tongue-row">
                               <div class="form-group">
                                  <label for="place-of-birth">Place of Birth (Municipality/City) *</label>
                                  <input type="text" id="place-of-birth" name="place-of-birth" required>
                                  <span class="error-text" id="place-of-birth-error"></span>
                              </div>
                              <div class="form-group">
                                  <label for="mother-tongue">Mother Tongue *</label>
                                  <input type="text" id="mother-tongue" name="mother-tongue" required>
                                  <span class="error-text" id="mother-tongue-error"></span>
                              </div>
                          </div>

                           <div class="form-row">
                               <div class="form-group"> <label for="indigenous">Belonging to any Indigenous Peoples (IP) Community? *</label>
                                  <div class="radio-group">
                                      <label class="radio-label">
                                          <input type="radio" name="indigenous" value="yes"> Yes
                                      </label>
                                      <label class="radio-label">
                                          <input type="radio" name="indigenous" value="no" checked> No
                                      </label>
                                  </div>
                                  <span class="error-text" id="indigenous-error"></span>
                              </div>
                              <div class="form-group"> <label for="ip-community">If Yes, please specify:</label>
                                  <input type="text" id="ip-community" name="ip-community">
                                  <span class="error-text" id="ip-community-error"></span>
                              </div>
                               <div class="form-group"> </div>
                          </div>

                          <div class="form-row">
                              <div class="form-group"> <label for="4ps">Is your family a beneficiary of 4Ps? *</label>
                                  <div class="radio-group">
                                      <label class="radio-label">
                                          <input type="radio" name="4ps" value="yes"> Yes
                                      </label>
                                      <label class="radio-label">
                                          <input type="radio" name="4ps" value="no" checked> No
                                      </label>
                                  </div>
                                  <span class="error-text" id="4ps-error"></span>
                              </div>
                              <div class="form-group"> <label for="4ps-id">If Yes, write the 4Ps Household ID Number:</label>
                                  <input type="text" id="4ps-id" name="4ps-id">
                                  <span class="error-text" id="4ps-id-error"></span>
                              </div>
                               <div class="form-group"> </div>
                          </div>
                      </div>

                       <div class="form-section">
                          <h3 class="form-section-title">Learner with Disability Information</h3>
                           <div class="form-row"> <div class="form-group">
                                   <label for="with-disability">Is the child a Learner with Disability? *</label>
                                   <div class="radio-group">
                                       <label class="radio-label">
                                           <input type="radio" name="with-disability" value="yes" onchange="toggleDisabilityFields(this.value)"> Yes
                                       </label>
                                       <label class="radio-label">
                                           <input type="radio" name="with-disability" value="no" onchange="toggleDisabilityFields(this.value)" checked> No
                                       </label>
                                   </div>
                                   <span class="error-text" id="with-disability-error"></span>
                               </div>
                           </div>

                          <div id="disability-details-container" style="display: none;">
                            <div class="form-group full-width"> <label>If Yes, specify the type of disability (check all that apply):</label>
                               <div class="checkbox-group disability-checkboxes">
                                   <div class="disability-category">
                                       <label class="checkbox-label">
                                           <input type="checkbox" name="disability-type[]" value="Visual Impairment"> Visual Impairment
                                       </label>
                                       <div class="sub-options">
                                           <label class="checkbox-label sub-option">
                                               <input type="checkbox" name="disability-subtype[]" value="Blind (VI)"> a. Blind
                                           </label>
                                           <label class="checkbox-label sub-option">
                                               <input type="checkbox" name="disability-subtype[]" value="Low Vision (VI)"> b. Low Vision
                                           </label>
                                       </div>
                                   </div>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Hearing Impairment"> Hearing Impairment</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Learning Disability"> Learning Disability</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Intellectual Disability"> Intellectual Disability</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Autism Spectrum Disorder"> Autism Spectrum Disorder</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Emotional-Behavioral Disorder"> Emotional-Behavioral Disorder</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Orthopedic/Physical Handicap"> Orthopedic/Physical Handicap</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Speech/Language Disorder"> Speech/Language Disorder</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Cerebral Palsy"> Cerebral Palsy</label>
                                  <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Special Health Problem/Chronic Disease"> Special Health Problem/Chronic Disease</label>
                                   <div class="disability-category">
                                       <label class="checkbox-label"><input type="checkbox" name="disability-type[]" value="Multiple Disorder"> Multiple Disorder</label>
                                       <div class="sub-options">
                                           <label class="checkbox-label sub-option"><input type="checkbox" name="disability-subtype[]" value="Cancer (MD)"> a. Cancer (Example)</label>
                                       </div>
                                   </div>
                               </div>
                           </div>
                          </div>
                       </div>

                      <div class="form-section">
                          <h3 class="form-section-title">Address Information</h3>
                          <div class="form-subsection">
                               <h4 class="form-subsection-title">Current Address</h4>
                               <div class="form-row"> <div class="form-group">
                                       <label for="current-house-no">House No./Street *</label>
                                       <input type="text" id="current-house-no" name="current-house-no" required>
                                       <span class="error-text" id="current-house-no-error"></span>
                                   </div>
                                   <div class="form-group">
                                       <label for="current-street">Street Name *</label>
                                       <input type="text" id="current-street" name="current-street" required>
                                       <span class="error-text" id="current-street-error"></span>
                                   </div>
                                   <div class="form-group">
                                       <label for="current-barangay">Barangay *</label>
                                       <input type="text" id="current-barangay" name="current-barangay" required>
                                       <span class="error-text" id="current-barangay-error"></span>
                                   </div>
                               </div>

                              <div class="form-row"> <div class="form-group">
                                      <label for="current-municipality">Municipality/City *</label>
                                      <input type="text" id="current-municipality" name="current-municipality" required>
                                      <span class="error-text" id="current-municipality-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="current-province">Province *</label>
                                      <input type="text" id="current-province" name="current-province" required>
                                      <span class="error-text" id="current-province-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="current-country">Country *</label>
                                      <input type="text" id="current-country" name="current-country" value="Philippines" required>
                                      <span class="error-text" id="current-country-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="current-zipcode">Zip Code *</label>
                                      <input type="text" id="current-zipcode" name="current-zipcode" pattern="\d{4,}" title="Enter a valid zip code" required>
                                      <span class="error-text" id="current-zipcode-error"></span>
                                  </div>
                              </div>
                           </div>

                          <div class="form-row"> <div class="form-group">
                                  <label for="same-address">Is Permanent Address same with Current Address? *</label>
                                  <div class="radio-group">
                                      <label class="radio-label">
                                          <input type="radio" name="same-address" value="yes" checked> Yes
                                      </label>
                                      <label class="radio-label">
                                          <input type="radio" name="same-address" value="no"> No
                                      </label>
                                  </div>
                                  <span class="error-text" id="same-address-error"></span>
                              </div>
                          </div>

                           <div id="permanent-address-section" class="form-subsection" style="display: none;">
                               <h4 class="form-subsection-title">Permanent Address</h4>
                               <div class="form-row"> <div class="form-group">
                                       <label for="permanent-house-no">House No. *</label>
                                       <input type="text" id="permanent-house-no" name="permanent-house-no">
                                       <span class="error-text" id="permanent-house-no-error"></span>
                                   </div>
                                   <div class="form-group">
                                       <label for="permanent-sitio">Sitio/Street Name *</label>
                                       <input type="text" id="permanent-sitio" name="permanent-sitio">
                                       <span class="error-text" id="permanent-sitio-error"></span>
                                   </div>
                                   <div class="form-group">
                                       <label for="permanent-barangay">Barangay *</label>
                                       <input type="text" id="permanent-barangay" name="permanent-barangay">
                                       <span class="error-text" id="permanent-barangay-error"></span>
                                   </div>
                               </div>

                              <div class="form-row"> <div class="form-group">
                                      <label for="permanent-municipality">Municipality/City *</label>
                                      <input type="text" id="permanent-municipality" name="permanent-municipality">
                                      <span class="error-text" id="permanent-municipality-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="permanent-province">Province *</label>
                                      <input type="text" id="permanent-province" name="permanent-province">
                                      <span class="error-text" id="permanent-province-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="permanent-country">Country *</label>
                                      <input type="text" id="permanent-country" name="permanent-country" value="Philippines">
                                      <span class="error-text" id="permanent-country-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="permanent-zipcode">Zip Code *</label>
                                      <input type="text" id="permanent-zipcode" name="permanent-zipcode" pattern="\d{4,}">
                                      <span class="error-text" id="permanent-zipcode-error"></span>
                                  </div>
                              </div>
                          </div>
                      </div>

                      <div class="form-section">
                          <h3 class="form-section-title">Parent's/Guardian's Information</h3>
                           <div class="form-subsection">
                              <h4 class="form-subsection-title">Father's Information</h4>
                               <div class="form-row"> <div class="form-group">
                                      <label for="father-last-name">Last Name *</label>
                                      <input type="text" id="father-last-name" name="father-last-name" required>
                                      <span class="error-text" id="father-last-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="father-first-name">First Name *</label>
                                      <input type="text" id="father-first-name" name="father-first-name" required>
                                      <span class="error-text" id="father-first-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="father-middle-name">Middle Name</label>
                                      <input type="text" id="father-middle-name" name="father-middle-name">
                                      <span class="error-text" id="father-middle-name-error"></span>
                                  </div>
                              </div>
                              <div class="form-row"> 
                                  <div class="form-group">
                                      <label for="father-contact">Contact Number *</label>
                                      <input type="tel" id="father-contact" name="father-contact" pattern="[0-9]{11}" title="Must be 11 digits, e.g., 09xxxxxxxxx" maxlength="11" required>
                                      <span class="error-text" id="father-contact-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="father-email">Father's Email</label>
                                      <input type="email" id="father-email" name="father-email" maxlength="255" placeholder="example@domain.com">
                                      <div class="form-text">We'll only use this to contact you about this application.</div>
                                      <span class="error-text" id="father-email-error"></span>
                                  </div>
                                  <div class="form-group"></div>
                              </div>
                          </div>

                           <div class="form-subsection">
                               <h4 class="form-subsection-title">Mother's Information</h4>
                              <div class="form-row"> <div class="form-group">
                                      <label for="mother-last-name">Last Name *</label>
                                      <input type="text" id="mother-last-name" name="mother-last-name" required>
                                      <span class="error-text" id="mother-last-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="mother-first-name">First Name *</label>
                                      <input type="text" id="mother-first-name" name="mother-first-name" required>
                                      <span class="error-text" id="mother-first-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="mother-middle-name">Middle Name</label>
                                      <input type="text" id="mother-middle-name" name="mother-middle-name">
                                      <span class="error-text" id="mother-middle-name-error"></span>
                                  </div>
                              </div>
                               <div class="form-row"> 
                                  <div class="form-group">
                                      <label for="mother-contact">Contact Number *</label>
                                      <input type="tel" id="mother-contact" name="mother-contact" pattern="[0-9]{11}" title="Must be 11 digits, e.g., 09xxxxxxxxx" maxlength="11" required>
                                      <span class="error-text" id="mother-contact-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="mother-email">Mother's Email</label>
                                      <input type="email" id="mother-email" name="mother-email" maxlength="255" placeholder="example@domain.com">
                                      <div class="form-text">We'll only use this to contact you about this application.</div>
                                      <span class="error-text" id="mother-email-error"></span>
                                  </div>
                                  <div class="form-group"></div>
                               </div>
                          </div>

                           <div class="form-subsection">
                               <h4 class="form-subsection-title">Legal Guardian's Information (if applicable)</h4>
                               <div class="form-row"> <div class="form-group">
                                      <label for="guardian-last-name">Last Name</label>
                                      <input type="text" id="guardian-last-name" name="guardian-last-name">
                                      <span class="error-text" id="guardian-last-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="guardian-first-name">First Name</label>
                                      <input type="text" id="guardian-first-name" name="guardian-first-name">
                                      <span class="error-text" id="guardian-first-name-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="guardian-middle-name">Middle Name</label>
                                      <input type="text" id="guardian-middle-name" name="guardian-middle-name">
                                      <span class="error-text" id="guardian-middle-name-error"></span>
                                  </div>
                              </div>
                              <div class="form-row"> 
                                  <div class="form-group">
                                      <label for="guardian-contact">Contact Number</label>
                                      <input type="tel" id="guardian-contact" name="guardian-contact" pattern="[0-9]{11}" title="Must be 11 digits, e.g., 09xxxxxxxxx" maxlength="11">
                                      <span class="error-text" id="guardian-contact-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="guardian-email">Guardian's Email</label>
                                      <input type="email" id="guardian-email" name="guardian-email" maxlength="255" placeholder="example@domain.com">
                                      <div class="form-text">We'll only use this to contact you about this application when applicable.</div>
                                      <span class="error-text" id="guardian-email-error"></span>
                                  </div>
                                  <div class="form-group">
                                      <label for="guardian-relationship">Relationship to Student</label>
                                      <select id="guardian-relationship" name="guardian-relationship">
                                          <option value="">Select Relationship</option>
                                          <option value="Grandparent">Grandparent</option>
                                          <option value="Aunt/Uncle">Aunt/Uncle</option>
                                          <option value="Sibling">Sibling</option>
                                          <option value="Other Relative">Other Relative</option>
                                          <option value="Legal Guardian (Non-relative)">Legal Guardian (Non-relative)</option>
                                      </select>
                                      <span class="error-text" id="guardian-relationship-error"></span>
                                  </div>
                              </div>
                          </div>
                      </div>

                      <div class="form-section">
                        <h3 class="form-section-title">Upload Required Documents</h3>
                        <p>Based on your grade level and transferee status, the required fields will appear below. Accepted formats: JPG, PNG, PDF. Max file size: 10MB per file.</p>
                        
                        <div class="alert alert-info">
                            <strong>Notice:</strong> While you can upload digital copies of your documents here, you are still required to submit photocopies of these documents to the school for verification during the enrollment period.
                        </div>
                        
                        <!-- PSA (for Kinder, G1, G7, G11) -->
                        <div id="file-group-psa" class="form-group full-width mb-3" style="display: none;">
                            <label for="file-psa">NSO/PSA Birth Certificate *</label>
                            <input type="file" id="file-psa" name="file_psa" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        
                        <!-- Report Card (for G1, G2-6, G7, G8-10, G11, G12) -->
                        <div id="file-group-report-card" class="form-group full-width mb-3" style="display: none;">
                            <label for="file-report-card">Previous Report Card (SF9) *</label>
                            <input type="file" id="file-report-card" name="file_report_card" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <!-- Transferee Documents -->
                        <div id="file-group-transferee" class="form-group full-width" style="display: none;">
                            <label for="file-transferee-docs">Transferee Documents (Good Moral Certificate, etc.) *</label>
                            <input type="file" id="file-transferee-docs" name="file_transferee_docs[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                      </div>

                      <div class="submit-button-container">
                                                <!-- Email consent checkbox: required if any email provided -->
                                                <div class="form-group mb-2">
                                                        <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" value="1" id="email-consent" name="email-consent">
                                                                <label class="form-check-label" for="email-consent">I consent to receive enrollment-related emails to the addresses I provided. See our <a href="privacy.php" target="_blank">Privacy Policy</a>.</label>
                                                        </div>
                                                        <span class="error-text" id="email-consent-error"></span>
                                                </div>
                                                <button type="submit" class="submit-button">Submit Application</button>
                      </div>
                       <div class="back-link-container">
                          <a href="index.php" class="back-link">← Back to Main Page</a>
                      </div>
                  </form>
              </div>
          </div>
      </section>
  </div>
  
  <!-- Privacy Modal -->
  <div class="modal fade privacy-modal" id="privacyConsentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="privacyConsentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="privacyConsentModalLabel"><i class="bi bi-shield-check me-2"></i>Data Privacy & Terms Agreement</h5>
      </div>
      <div class="modal-body">
        <p><strong>Welcome to the FFCS Online Enrollment Portal!</strong></p>
        <p>To continue with the enrollment process, please read and agree to our Data Privacy Statement and Terms and Conditions. Your privacy and understanding of our terms are important to us.</p>

        <h5>Data Privacy Statement Summary</h5>
        <?php
            $privacy_summary = "Welcome to the FFCS Online Enrollment. By proceeding, you acknowledge that you have read, understood, and agree to the terms outlined in our Data Privacy Policy. We are committed to protecting your personal information in compliance with the Data Privacy Act of 2012 (RA 10173). The information collected through this form will be used exclusively for processing your enrollment, for communication purposes related to your application, and for maintaining accurate student records. We will not share your personal data with third parties without your explicit consent, unless required by law. For more details, please review our full Privacy Policy.";
            $terms_summary = "Please also review our Terms and Conditions before proceeding with your enrollment. Your continuation of the enrollment process signifies your acceptance of these terms.";
        ?>
        <p><?= htmlspecialchars($privacy_summary) ?></p>
        <p>For complete details, please review our full <a href="privacy.php" target="_blank" rel="noopener noreferrer">Data Privacy Policy</a>.</p>

        <h5>Terms and Conditions Summary</h5>
        <p><?= htmlspecialchars($terms_summary) ?></p>
        <p>For complete details, please review our full <a href="terms&conditions.php" target="_blank" rel="noopener noreferrer">Terms and Conditions</a>.</p>

        <hr class="my-3">
        <p class="fw-bold">By clicking "I Understand and Continue," you confirm that:</p>
        <ul>
            <li>You have read and understood the summaries provided.</li>
            <li>You consent to the collection and processing of your personal data as described in our Data Privacy Policy for the purpose of enrollment.</li>
            <li>You agree to abide by the school's Terms and Conditions.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <a href="index.php" class="btn btn-secondary btn-secondary-custom">Cancel and Return Home</a>
        <button type="button" class="btn btn-primary-custom" id="continueToFormBtnModal">I Understand and Continue</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Submission Successful!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="successModalBody">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/enrollment.js"></script>
    </body>
</html>