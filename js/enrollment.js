// Final-School-Web/js/enrollment.js
document.addEventListener('DOMContentLoaded', function () {
    console.log("enrollment.js: DOMContentLoaded");

    // --- Form and Message Elements ---
    const enrollmentForm = document.getElementById('mainEnrollmentForm');
    const errorMessageDiv = document.querySelector('.error-message');
    const submitButton = enrollmentForm ? enrollmentForm.querySelector('.submit-button') : null;

    // --- Modal Elements ---
    const successModalEl = document.getElementById('successModal');
    const successModal = successModalEl ? new bootstrap.Modal(successModalEl) : null;
    const successModalBody = document.getElementById('successModalBody');

    // --- Section Specific Elements ---
    const permanentAddressSection = document.getElementById('permanent-address-section');
    const sameAddressRadios = document.querySelectorAll('input[name="same-address"]');
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');
    const lrnInput = document.getElementById('lrn');
    const lrnError = document.getElementById('lrn-error');
    const hasLrnRadios = document.querySelectorAll('input[name="has-lrn"]');
    const lrnInputGroup = document.getElementById('lrn-input-group');
    const disabilityDetailsContainer = document.getElementById('disability-details-container');
    const withDisabilityRadios = document.querySelectorAll('input[name="with-disability"]');

    // --- *** DYNAMIC UPLOAD/FIELD ELEMENTS *** ---
    const gradeLevelInput = document.getElementById('grade-level');
    const transfereeRadios = document.querySelectorAll('input[name="is-transferee"]');
    
    // Previous School Field
    const previousSchoolGroup = document.getElementById('previous-school-group');
    const previousSchoolInput = document.getElementById('previous-school-name');
    const previousSchoolError = document.getElementById('previous-school-name-error');

    // File Upload Groups
    const fileGroupPsa = {
        group: document.getElementById('file-group-psa'),
        input: document.getElementById('file-psa')
    };
    const fileGroupReportCard = {
        group: document.getElementById('file-group-report-card'),
        input: document.getElementById('file-report-card')
    };
    const fileGroupTransferee = {
        group: document.getElementById('file-group-transferee'),
        input: document.getElementById('file-transferee-docs')
    };
    
    // Array of all file groups for easy iteration
    const allFileGroups = [fileGroupPsa, fileGroupReportCard, fileGroupTransferee];
    // --- *** END: DYNAMIC ELEMENTS *** ---


    // --- Field Validation Configurations ---
    const fieldValidations = [
        // Numeric & Specific Format Fields
        { id: 'school-year', errorId: 'school-year-error', label: "School Year", required: true, type: 'select' },
        { id: 'lrn', errorId: 'lrn-error', label: "LRN", pattern: /^[0-9]*$/, message: "LRN must be 12 digits.", minLength: 12, maxLength: 12, required: false }, // LRN is conditionally required
        { id: 'current-zipcode', errorId: 'current-zipcode-error', label: "Current Zip Code", pattern: /^[0-9]*$/, message: "Zip code must be numbers only.", required: true },
        { id: 'permanent-zipcode', errorId: 'permanent-zipcode-error', label: "Permanent Zip Code", pattern: /^[0-9]*$/, message: "Zip code must be numbers only.", required: false }, // Conditionally required
        
        // --- MODIFIED CONTACT FIELDS ---
    { id: 'father-contact', errorId: 'father-contact-error', label: "Father's Contact", pattern: /^[0-9]*$/, message: "Must be 11 digits (e.g., 09xxxxxxxxx).", required: true, minLength: 11, maxLength: 11 },
    { id: 'mother-contact', errorId: 'mother-contact-error', label: "Mother's Contact", pattern: /^[0-9]*$/, message: "Must be 11 digits (e.g., 09xxxxxxxxx).", required: true, minLength: 11, maxLength: 11 },
    { id: 'guardian-contact', errorId: 'guardian-contact-error', label: "Guardian's Contact", pattern: /^[0-9]*$/, message: "Must be 11 digits (e.g., 09xxxxxxxxx).", required: false, minLength: 11, maxLength: 11 },

    // Email fields (optional)
    { id: 'student-email', errorId: 'student-email-error', label: "Learner Email", pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: "Enter a valid email address.", required: false },
    { id: 'father-email', errorId: 'father-email-error', label: "Father's Email", pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: "Enter a valid email address.", required: false },
    { id: 'mother-email', errorId: 'mother-email-error', label: "Mother's Email", pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: "Enter a valid email address.", required: false },
    { id: 'guardian-email', errorId: 'guardian-email-error', label: "Guardian's Email", pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: "Enter a valid email address.", required: false },
        // --- END MODIFIED CONTACT FIELDS ---

        // Name & General Text Fields (Pattern allows letters, spaces, and common punctuation)
        { id: 'last-name', errorId: 'last-name-error', label: "Last Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'first-name', errorId: 'first-name-error', label: "First Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'middle-name', errorId: 'middle-name-error', label: "Middle Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'extension-name', errorId: 'extension-name-error', label: "Extension Name", pattern: /^[a-zA-Z\s'-.,Ññ.]*$/, message: "Extension name can include letters, periods.", required: false }, 
        { id: 'place-of-birth', errorId: 'place-of-birth-error', label: "Place of Birth", pattern: /^[a-zA-Z0-9\s'-.,Ññ()]*$/, message: "Place of birth can include letters, numbers, and some punctuation.", required: true },
        { id: 'mother-tongue', errorId: 'mother-tongue-error', label: "Mother Tongue", pattern: /^[a-zA-Z\s'-]*$/, message: "Mother tongue should only contain letters, spaces, or hyphens.", required: true },
        { id: 'ip-community', errorId: 'ip-community-error', label: "IP Community", pattern: /^[a-zA-Z0-9\s'-]*$/, message: "IP Community can include letters, numbers, spaces, or hyphens.", required: false }, 
        { id: '4ps-id', errorId: '4ps-id-error', label: "4Ps ID", pattern: /^[a-zA-Z0-9\s-]*$/, message: "4Ps ID can include letters, numbers, spaces, or hyphens.", required: false }, 
        { id: 'current-house-no', errorId: 'current-house-no-error', label: "Current House No./Street", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: true },
        { id: 'current-street', errorId: 'current-street-error', label: "Current Street Name", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: true },
        { id: 'current-barangay', errorId: 'current-barangay-error', label: "Current Barangay", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: true },
        { id: 'current-municipality', errorId: 'current-municipality-error', label: "Current Municipality/City", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: true },
        { id: 'current-province', errorId: 'current-province-error', label: "Current Province", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: true },
        { id: 'current-country', errorId: 'current-country-error', label: "Current Country", pattern: /^[a-zA-Z\s'-]*$/, message: "Country should only contain letters, spaces, or hyphens.", required: true },
        { id: 'permanent-house-no', errorId: 'permanent-house-no-error', label: "Permanent House No./Street", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: false },
        { id: 'permanent-sitio', errorId: 'permanent-sitio-error', label: "Permanent Sitio/Street", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: false },
        { id: 'permanent-barangay', errorId: 'permanent-barangay-error', label: "Permanent Barangay", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: false },
        { id: 'permanent-municipality', errorId: 'permanent-municipality-error', label: "Permanent Municipality/City", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: false },
        { id: 'permanent-province', errorId: 'permanent-province-error', label: "Permanent Province", pattern: /^[a-zA-Z0-9\s'#.,-/Ññ]*$/, message: "Address can include letters, numbers and # . , - /", required: false },
        { id: 'permanent-country', errorId: 'permanent-country-error', label: "Permanent Country", pattern: /^[a-zA-Z\s'-]*$/, message: "Country should only contain letters, spaces, or hyphens.", required: false },
        { id: 'father-last-name', errorId: 'father-last-name-error', label: "Father's Last Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'father-first-name', errorId: 'father-first-name-error', label: "Father's First Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'father-middle-name', errorId: 'father-middle-name-error', label: "Father's Middle Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'mother-last-name', errorId: 'mother-last-name-error', label: "Mother's Last Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'mother-first-name', errorId: 'mother-first-name-error', label: "Mother's First Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: true },
        { id: 'mother-middle-name', errorId: 'mother-middle-name-error', label: "Mother's Middle Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'guardian-last-name', errorId: 'guardian-last-name-error', label: "Guardian's Last Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'guardian-first-name', errorId: 'guardian-first-name-error', label: "Guardian's First Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'guardian-middle-name', errorId: 'guardian-middle-name-error', label: "Guardian's Middle Name", pattern: /^[a-zA-Z\s'-.,Ññ]*$/, message: "Name fields should only contain letters and appropriate punctuation.", required: false },
        { id: 'previous-school-name', errorId: 'previous-school-name-error', label: "Previous School Name", pattern: /^[a-zA-Z0-9\s'-.,Ññ()]*$/, message: "School name can include letters, numbers, and some punctuation.", required: false }, // Conditionally required

        // Select fields
        { id: 'grade-level', errorId: 'grade-level-error', label: "Grade Level", required: true, type: 'select' },
        { id: 'returning', errorId: 'returning-error', label: "Returning Status", required: true, type: 'select' },
        { id: 'sex', errorId: 'sex-error', label: "Sex", required: true, type: 'select' },
        { id: 'guardian-relationship', errorId: 'guardian-relationship-error', label: "Guardian Relationship", required: false, type: 'select' }, 

        // Date fields
        { id: 'birthdate', errorId: 'birthdate-error', label: "Birthdate", required: true, type: 'date' }
    ];

    // Consent element for email notifications
    const emailConsentEl = document.getElementById('email-consent');
    const emailConsentErrorEl = document.getElementById('email-consent-error');

    // --- Helper Functions ---
    function displayError(inputElement, errorElement, message) {
        if (inputElement) inputElement.classList.add('input-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearError(inputElement, errorElement) {
        if (inputElement) inputElement.classList.remove('input-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    // --- DYNAMIC FIELD VISIBILITY LOGIC ---
    function updateFieldVisibility() {
        if (!gradeLevelInput || !previousSchoolGroup || !previousSchoolInput) {
            console.warn("Dynamic field elements (grade, transferee, school name) not found.");
            return;
        }

        const selectedGrade = gradeLevelInput.value;
        const selectedTransfereeRadio = document.querySelector('input[name="is-transferee"]:checked');
        const isTransferee = selectedTransfereeRadio ? selectedTransfereeRadio.value : 'no';
        const prevSchoolFieldConfig = fieldValidations.find(f => f.id === 'previous-school-name');

        // 1. Hide all file groups and remove 'required' from all inputs
        allFileGroups.forEach(item => {
            if (item.group) item.group.style.display = 'none';
            if (item.input) item.input.removeAttribute('required');
        });

        // 2. Hide Previous School and remove 'required'
        previousSchoolGroup.style.display = 'none';
        previousSchoolInput.removeAttribute('required');
        if (prevSchoolFieldConfig) prevSchoolFieldConfig.required = false;
        if (previousSchoolError) clearError(previousSchoolInput, previousSchoolError);


        // 3. Show the correct file group(s) based on grade
        switch (selectedGrade) {
            case 'Kindergarten':
                if (fileGroupPsa.group) fileGroupPsa.group.style.display = 'block';
                if (fileGroupPsa.input) fileGroupPsa.input.setAttribute('required', 'required');
                break;
            case '1':
            case '7':
            case '11':
                if (fileGroupPsa.group) fileGroupPsa.group.style.display = 'block';
                if (fileGroupPsa.input) fileGroupPsa.input.setAttribute('required', 'required');
                
                if (fileGroupReportCard.group) fileGroupReportCard.group.style.display = 'block';
                if (fileGroupReportCard.input) fileGroupReportCard.input.setAttribute('required', 'required');
                break;
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '8':
            case '9':
            case '10':
            case '12':
                if (fileGroupReportCard.group) fileGroupReportCard.group.style.display = 'block';
                if (fileGroupReportCard.input) fileGroupReportCard.input.setAttribute('required', 'required');
                break;
        }

        // 4. Show transferee fields if 'Yes' is selected
        if (isTransferee === 'yes') {
            if (fileGroupTransferee.group) fileGroupTransferee.group.style.display = 'block';
            if (fileGroupTransferee.input) fileGroupTransferee.input.setAttribute('required', 'required');
            
            if (previousSchoolGroup) previousSchoolGroup.style.display = 'block';
            if (previousSchoolInput) previousSchoolInput.setAttribute('required', 'required');
            if (prevSchoolFieldConfig) prevSchoolFieldConfig.required = true;
        }
    }
    // --- *** END: NEW DYNAMIC LOGIC *** ---


    // --- Dynamic Form Logic (Existing) ---
    function toggleLrnInput() {
        const selectedLrnOption = document.querySelector('input[name="has-lrn"]:checked');
        const lrnFieldConfig = fieldValidations.find(f => f.id === 'lrn');

        if (lrnInput && lrnInputGroup && lrnFieldConfig) {
            if (selectedLrnOption && selectedLrnOption.value === 'no') {
                lrnInputGroup.style.display = 'none';
                lrnInput.value = '';
                lrnInput.removeAttribute('required');
                lrnFieldConfig.required = false;
                if (lrnError) clearError(lrnInput, lrnError);
            } else {
                lrnInputGroup.style.display = 'block';
                lrnInput.setAttribute('required', 'required');
                lrnFieldConfig.required = true;
            }
        } else {
            console.warn("LRN input, group, or config not found for toggleLrnInput.");
        }
    }

    function togglePermanentAddress() {
        if (!permanentAddressSection || !sameAddressRadios || sameAddressRadios.length === 0) return;
        const selectedRadio = document.querySelector('input[name="same-address"]:checked');
        if (selectedRadio) {
            const showPermanent = (selectedRadio.value === 'no');
            permanentAddressSection.style.display = showPermanent ? 'block' : 'none';
            const permAddressFields = [
                'permanent-house-no', 'permanent-sitio', 'permanent-barangay',
                'permanent-municipality', 'permanent-province', 'permanent-country', 'permanent-zipcode'
            ];
            permAddressFields.forEach(fieldId => {
                const input = document.getElementById(fieldId);
                const fieldConfig = fieldValidations.find(f => f.id === fieldId);
                if (input && fieldConfig) {
                    if (showPermanent) {
                        input.setAttribute('required', 'required');
                        fieldConfig.required = true;
                    } else {
                        input.removeAttribute('required');
                        fieldConfig.required = false;
                        const errorEl = document.getElementById(fieldId + '-error');
                        if (errorEl) clearError(input, errorEl);
                    }
                }
            });
        }
    }

    function calculateAge() {
        if (birthdateInput && ageInput) {
            if (this.value) {
                var birthDate = new Date(this.value);
                var today = new Date();
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                ageInput.value = age >= 0 ? age : '';
                 clearError(birthdateInput, document.getElementById('birthdate-error'));
            } else {
                ageInput.value = '';
            }
        }
    }

    window.toggleDisabilityFields = function(value) {
        if (disabilityDetailsContainer) {
            disabilityDetailsContainer.style.display = value === 'yes' ? 'block' : 'none';
        }
    };


    // --- Attach Event Listeners ---
    if (hasLrnRadios.length > 0) {
        hasLrnRadios.forEach(radio => radio.addEventListener('change', toggleLrnInput));
        toggleLrnInput(); // Initial state
    } else { console.warn("Elements for 'has-lrn' radios not found.");}

    if (sameAddressRadios.length > 0) {
      sameAddressRadios.forEach(radio => radio.addEventListener('change', togglePermanentAddress));
      togglePermanentAddress(); // Initial state
    } else { console.warn("Elements for 'same-address' radios not found.");}

    if (birthdateInput) {
        birthdateInput.addEventListener('change', calculateAge);
    } else { console.warn("Birthdate input not found."); }

    if (withDisabilityRadios.length > 0) {
        const initialDisabilityRadio = document.querySelector('input[name="with-disability"]:checked');
        if (initialDisabilityRadio) {
            toggleDisabilityFields(initialDisabilityRadio.value);
        }
    } else { console.warn("Elements for 'with-disability' radios not found."); }

    // --- *** ATTACH LISTENERS FOR DYNAMIC FIELDS *** ---
    if (gradeLevelInput) {
        gradeLevelInput.addEventListener('change', updateFieldVisibility);
    }
    if (transfereeRadios.length > 0) {
        transfereeRadios.forEach(radio => radio.addEventListener('change', updateFieldVisibility));
    }
    // Call once on load to set initial state
    updateFieldVisibility();
    // --- *** END: NEW LISTENERS *** ---


    // Attach input event listeners for real-time validation (for text fields)
    fieldValidations.forEach(field => {
        const inputElement = document.getElementById(field.id);
        const errorElement = document.getElementById(field.errorId);

        if (inputElement && errorElement) {
            inputElement.addEventListener('input', function () {
                const value = this.value.trim();
                
                // --- Conditional Logic Check ---
                let isRequired = field.required;
                if (field.id === 'lrn') {
                    const lrnYesRadio = document.querySelector('input[name="has-lrn"][value="yes"]');
                    if (!lrnYesRadio || !lrnYesRadio.checked) isRequired = false;
                }
                if (field.id === 'previous-school-name') {
                    const transfereeYesRadio = document.querySelector('input[name="is-transferee"][value="yes"]');
                    if (!transfereeYesRadio || !transfereeYesRadio.checked) isRequired = false;
                }
                // --- End Conditional Logic ---

                if (value === "" && isRequired) {
                    // Don't show error on input, wait for submit
                    clearError(inputElement, errorElement);
                } else if (value !== "" && field.pattern && !field.pattern.test(value)) {
                    displayError(inputElement, errorElement, field.message);
                } else if (field.customValidation) {
                    field.customValidation(inputElement, errorElement, field);
                } else if (field.maxLength && value.length > field.maxLength) {
                     displayError(inputElement, errorElement, `${field.label} cannot exceed ${field.maxLength} characters.`);
                } else if (field.minLength && value.length < field.minLength && value !== "") {
                     displayError(inputElement, errorElement, `${field.label} must be at least ${field.minLength} characters.`);
                }
                else {
                    clearError(inputElement, errorElement);
                }
            });
        } else {
            // console.warn(`Validation elements for field ID '${field.id}' or error ID '${field.errorId}' not found.`);
        }
    });


    // --- Form Submission Logic ---
    if (enrollmentForm) {
        enrollmentForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log("Form submission attempt.");
            let overallFormIsValid = true;

            if(errorMessageDiv) errorMessageDiv.style.display = 'none';

            // Perform all text field validations
            fieldValidations.forEach(field => {
                const inputElement = document.getElementById(field.id);
                const errorElement = document.getElementById(field.errorId);
                let isFieldValid = true;

                if (!inputElement) {
                    console.warn(`Submit validation: Input element for ${field.id} not found.`);
                    return;
                }
                 if (!errorElement) {
                    // console.warn(`Submit validation: Error element for ${field.errorId} not found.`);
                }

                const value = inputElement.value.trim();
                
                // --- Check Conditional Requirement ---
                let isRequired = field.required; // Get base requirement
                if (field.id === 'lrn') {
                    const lrnYesRadio = document.querySelector('input[name="has-lrn"][value="yes"]');
                    isRequired = (lrnYesRadio && lrnYesRadio.checked);
                }
                if (field.id === 'previous-school-name') {
                    const transfereeYesRadio = document.querySelector('input[name="is-transferee"][value="yes"]');
                    isRequired = (transfereeYesRadio && transfereeYesRadio.checked);
                }
                if (field.id.startsWith('permanent-')) {
                    const sameAddressNoRadio = document.querySelector('input[name="same-address"][value="no"]');
                    isRequired = (sameAddressNoRadio && sameAddressNoRadio.checked);
                }
                // --- End Conditional Check ---

                if (isRequired && value === "") {
                    if (field.type === 'select' && inputElement.value === "") {
                         displayError(inputElement, errorElement, `Please select a ${field.label.toLowerCase()}.`);
                    } else {
                        displayError(inputElement, errorElement, `${field.label} is required.`);
                    }
                    isFieldValid = false;
                } else if (value !== "" && field.pattern && !field.pattern.test(value)) {
                    displayError(inputElement, errorElement, field.message);
                    isFieldValid = false;
                } else if (field.customValidation) {
                    if (!field.customValidation(inputElement, errorElement, field) && value !== "") {
                        isFieldValid = false;
                    }
                } else if (field.maxLength && value.length > field.maxLength) {
                    displayError(inputElement, errorElement, `${field.label} cannot exceed ${field.maxLength} characters.`);
                    isFieldValid = false;
                } else if (field.minLength && value.length > 0 && value.length < field.minLength) {
                    displayError(inputElement, errorElement, `${field.label} must be at least ${field.minLength} characters.`);
                    isFieldValid = false;
                } else {
                    // Only clear if not already marked as invalid by a required check
                    if (isFieldValid) {
                        clearError(inputElement, errorElement);
                    }
                }
                 if (!isFieldValid) overallFormIsValid = false;
            });

            // --- Check browser validation for file inputs ---
            let firstInvalidFile = null;
            allFileGroups.forEach(item => {
                if (!firstInvalidFile && item.group && item.input && item.group.style.display !== 'none') {
                    if (item.input.hasAttribute('required') && !item.input.value) {
                        firstInvalidFile = item.input;
                    }
                }
            });

            if (firstInvalidFile) {
                console.log("Form has missing required file.");
                overallFormIsValid = false;
                firstInvalidFile.focus(); // Focus to show browser's native error
            }

            // --- Email consent rule: if any email provided, consent must be checked ---
            try {
                const studentEmailVal = (document.getElementById('student-email') || {value:''}).value.trim();
                const fatherEmailVal = (document.getElementById('father-email') || {value:''}).value.trim();
                const motherEmailVal = (document.getElementById('mother-email') || {value:''}).value.trim();
                const guardianEmailVal = (document.getElementById('guardian-email') || {value:''}).value.trim();
                const anyEmailProvided = studentEmailVal !== '' || fatherEmailVal !== '' || motherEmailVal !== '' || guardianEmailVal !== '';
                if (anyEmailProvided && emailConsentEl && !emailConsentEl.checked) {
                    overallFormIsValid = false;
                    if (emailConsentErrorEl) {
                        emailConsentErrorEl.textContent = 'Please consent to receive enrollment-related emails if you provided an email address.';
                        emailConsentErrorEl.style.display = 'block';
                    }
                    if (emailConsentEl) emailConsentEl.focus();
                } else if (emailConsentErrorEl) {
                    emailConsentErrorEl.textContent = '';
                    emailConsentErrorEl.style.display = 'none';
                }
            } catch (ex) {
                console.warn('Error checking email consent:', ex);
            }


            if (!overallFormIsValid) {
                console.log("Form has validation errors.");
                if(errorMessageDiv) {
                    errorMessageDiv.textContent = 'Please correct the errors highlighted in the form.';
                    errorMessageDiv.style.display = 'block';
                    errorMessageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            console.log("Form is valid, proceeding with submission.");
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
            }

            const formData = new FormData(enrollmentForm);
            try {
                const response = await fetch('api/enrollments/submit.php', {
                    method: 'POST',
                    body: formData,
                });
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error("Failed to parse JSON response:", jsonError, "Raw response:", responseText);
                    throw new Error("Server returned an invalid response. Please check console.");
                }

                if (!response.ok) {
                    let errorMsg = result.message || `Submission failed (HTTP ${response.status})`;
                    if (result.errors && Array.isArray(result.errors) && result.errors.length > 0) {
                         errorMsg += "\nDetails:\n - " + result.errors.join("\n - ");
                    }
                    throw new Error(errorMsg);
                }

                if (result.success) {
                    if (successModal && successModalBody) {
                        let successText = result.message || 'Application submitted successfully!';
                        if (result.generated_lrn) {
                            successText += `<br><strong>Your Temporary Reference Number is: ${result.generated_lrn}</strong><br>Please use this number to check your enrollment status.`;
                        }
                        if(result.file_upload_messages && Array.isArray(result.file_upload_messages) && result.file_upload_messages.length > 0) {
                            const notes = result.file_upload_messages.filter(m => m).join("; ");
                            if (notes) successText += "<br>File notes: " + notes;
                        }
                        successModalBody.innerHTML = successText;
                        successModal.show();
                    }
                    enrollmentForm.reset();
                    // Reset all conditional fields
                    togglePermanentAddress();
                    toggleLrnInput();
                    updateFieldVisibility(); // Reset file fields and transferee field
                    // Clear email consent UI state
                    if (emailConsentErrorEl) { emailConsentErrorEl.textContent = ''; emailConsentErrorEl.style.display = 'none'; }
                    if (emailConsentEl) emailConsentEl.checked = false;
                    const initialDisabilityRadioReset = document.querySelector('input[name="with-disability"]:checked');
                    if (initialDisabilityRadioReset) toggleDisabilityFields(initialDisabilityRadioReset.value);
                } else {
                    let errorText = result.message || 'Submission failed. Please review your details.';
                    if (result.errors && Array.isArray(result.errors) && result.errors.length > 0) {
                        errorText += "\nDetails:\n - " + result.errors.join("\n - ");
                    }
                    throw new Error(errorText);
                }
            } catch (err) {
                console.error('Error during form submission:', err);
                if(errorMessageDiv) {
                    const cleanMsg = String(err.message || "An unknown error occurred.").replace(/<[^>]*>?/gm, '');
                    errorMessageDiv.textContent = `Submission error: ${cleanMsg}`;
                    errorMessageDiv.style.display = 'block';
                    errorMessageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit Application';
                }
            }
        });
    } else {
        console.warn("Enrollment form with ID 'mainEnrollmentForm' not found.");
    }

    // --- Modal Logic (Privacy Consent) ---
    const privacyModalElement = document.getElementById('privacyConsentModal');
    const enrollmentFormWrapper = document.getElementById('enrollmentFormWrapper');
    const continueBtnModal = document.getElementById('continueToFormBtnModal');

    if (!privacyModalElement || !enrollmentFormWrapper || !continueBtnModal) {
        console.error('Modal elements (privacyConsentModal, enrollmentFormWrapper, or continueToFormBtnModal) not found. Privacy consent flow may not work correctly.');
        if(enrollmentFormWrapper) enrollmentFormWrapper.classList.remove('disabled-form');
        return; 
    }

    let privacyModalInstance;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        privacyModalInstance = new bootstrap.Modal(privacyModalElement, {
          keyboard: false,
          backdrop: 'static'
        });
    } else {
        console.error("Bootstrap Modal component not found. Privacy modal will not function.");
        if(enrollmentFormWrapper) enrollmentFormWrapper.classList.remove('disabled-form');
        return;
    }


    function disableEnrollmentForm() {
        if (enrollmentFormWrapper) enrollmentFormWrapper.classList.add('disabled-form');
    }

    function enableEnrollmentForm() {
        if (enrollmentFormWrapper) enrollmentFormWrapper.classList.remove('disabled-form');
    }

    if (sessionStorage.getItem('privacyConsentGivenFvres') !== 'true') {
        disableEnrollmentForm();
        if (privacyModalInstance) privacyModalInstance.show();
    } else {
        enableEnrollmentForm();
    }

    continueBtnModal.addEventListener('click', function () {
        if (privacyModalInstance) privacyModalInstance.hide();
        enableEnrollmentForm();
        sessionStorage.setItem('privacyConsentGivenFvres', 'true');
    });

     console.log("enrollment.js: Setup complete.");
});