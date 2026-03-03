// Final-School-Web/js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    console.log("Dashboard JS Initializing..."); 

    // --- Element Selectors ---
    const API_URL = './api/enrollments/';
    const AUTH_API_URL = './api/auth/';
    const SECTIONS_API_URL = './api/sections/';
    const HISTORY_API_URL = './api/enrollment_history_handler.php'; 
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    const logoutButton = document.getElementById('logout-btn');
    const facultyNameSpan = document.getElementById('faculty-name');
    const facultyRoleSpan = document.querySelector('.user-role');
    const applicationsTableBody = document.querySelector('#dashboard-section .data-table tbody');
    const searchApplicationsInput = document.getElementById('search-input-dashboard');

    // --- Card Selectors (NEW) ---
    const totalApplicationsCard = document.querySelector('.card.total-students');
    const enrolledApplicationsCard = document.querySelector('.card.enrolled-applications');
    const pendingApplicationsCard = document.querySelector('.card.pending-applications');
    const declinedApplicationsCard = document.querySelector('.card.declined-applications');
    const allCards = [totalApplicationsCard, enrolledApplicationsCard, pendingApplicationsCard, declinedApplicationsCard];

    // --- Card Value Spans ---
    const totalApplicationsSpan = document.querySelector('.card.total-students .card-value');
    const enrolledApplicationsSpan = document.querySelector('.card.enrolled-applications .card-value');
    const pendingApplicationsSpan = document.querySelector('.card.pending-applications .card-value');
    const declinedApplicationsSpan = document.querySelector('.card.declined-applications .card-value');

    // --- Modal Elements ---
    const applicationModal = document.getElementById('viewModal');
    const viewDetailsContainer = document.getElementById('modal-details-content');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    const addManuallyButton = document.getElementById('add-enrollee-btn');
    const addManualModal = document.getElementById('addManualModal');
    const closeAddManualModalBtn = document.getElementById('closeAddManualModalBtn');
    const cancelAddManualBtn = document.getElementById('cancelAddManualBtn');

    // --- Pagination Elements ---
    const modalNavigation = document.getElementById('modal-navigation');
    const modalPrevBtn = document.getElementById('modal-prev-btn');
    const modalNextBtn = document.getElementById('modal-next-btn');
    const modalPageIndicator = document.getElementById('modal-page-indicator');

    // --- State Management ---
    let allApplications = [];
    let currentPageIndex = 0;
    let modalPages = [];
    let currentApplicationData = null;
    let isAdminGlobal = false; 
    let currentStatusFilter = 'all'; // NEW: State for card filter

    // --- Utility Functions ---
    const getSafeValue = (value, defaultValue = 'N/A') => (value === undefined || value === null || String(value).trim() === '') ? defaultValue : String(value);
    const escapeHtml = (unsafe) => {
         if (unsafe === null || unsafe === undefined) return '';
         return String(unsafe)
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
     };
    const formatDisplayDate = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) { return escapeHtml(dateString); }
            // Format for date and time
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric'}) + ' ' + date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', hour12: true });
        }
        catch (e) {
             console.warn("Could not format date:", dateString, e);
             return escapeHtml(dateString);
        }
    };
    const getStatusClass = (status) => {
        switch (String(status).toLowerCase()) {
            case 'enrolled': return 'status-enrolled';
            case 'pending': return 'status-pending';
            case 'declined': // legacy value
            case 'for verification': return 'status-declined';
            default: return 'status-unknown';
        }
    };
     const getStatusText = (status) => {
        const safeStatus = getSafeValue(status, 'Unknown');
        // Title-case each word for nicer display (e.g., 'for verification')
        return safeStatus.split(' ').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(' ');
    };

    // --- API & Data Functions ---
    async function fetchAPI(url, options = {}) {
        console.log(`Fetching API: ${url}`, options.method || 'GET'); 
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                let errorData = { message: `HTTP error ${response.status} - ${response.statusText}` };
                try {
                    errorData = await response.json(); 
                } catch (e) { /* Ignore if response is not JSON */ }
                console.error(`API Error Response from ${url}:`, errorData); 
                throw new Error(errorData.message || `HTTP error ${response.status}`);
            }
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                 console.warn(`Non-JSON response from ${url}`);
                 return response.text();
            }
        } catch (error) {
            console.error(`Fetch API Error (${url}):`, error);
            
            if (!url.includes('session.php') && !url.includes('logout.php')) {
                 alert('API Communication Error: ' + error.message + '\nCheck console for details.');
            }

            throw error;
        }
    }

    async function fetchSessionData() {
        try {
            const data = await fetchAPI(`${AUTH_API_URL}session.php`);
            console.log("Session Data:", data); 
            if (data && data.isAuthenticated) {
                isAdminGlobal = (data.role === 'admin'); 
                console.log("isAdminGlobal set to:", isAdminGlobal);
                if (facultyNameSpan) facultyNameSpan.textContent = escapeHtml(data.displayName) || 'Faculty';
                if (facultyRoleSpan) facultyRoleSpan.textContent = (data.role ? escapeHtml(data.role.charAt(0).toUpperCase() + data.role.slice(1)) : 'Faculty') + ' Role';
                document.body.classList.toggle('is-admin', isAdminGlobal);
                updateAdminElementVisibility();
                return true;
            } else {
                 console.warn("Session check returned not authenticated.");
                 window.location.href = 'login.php?reason=session_expired_or_invalid';
                return false;
            }
        } catch (error) {
             console.error("Critical error fetching session data:", error);
             window.location.href = 'login.php?reason=session_fetch_error';
            return false;
        }
    }

    function updateAdminElementVisibility() {
        const isAdminBody = document.body.classList.contains('is-admin');
        console.log("Updating admin element visibility. Is Admin:", isAdminBody);

        document.querySelectorAll('.admin-only-item, .admin-only-button, .admin-only-form-section, .admin-only-action-cell, .admin-only-action-header').forEach(el => {
            el.style.display = isAdminBody ? '' : 'none'; 
        });
         const actionHeader = document.querySelector('.data-table th.admin-only-action-header');
         if (actionHeader) {
            actionHeader.style.display = isAdminBody ? 'table-cell' : 'none';
         }
         const noAppsRow = applicationsTableBody?.querySelector('td[colspan]');
         if (noAppsRow) {
             noAppsRow.setAttribute('colspan', isAdminBody ? '7' : '6');
         }
    }


    async function performLogout() {
        console.log("Attempting logout...");
        try {
            await fetchAPI(`${AUTH_API_URL}logout.php`, { method: 'POST' });
            window.location.href = 'login.php?reason=logout_initiated';
        } catch (error) {
             console.error("Error during logout API call:", error);
             window.location.href = 'login.php?reason=logout_error';
        }
    }


    // --- DOM Update Functions ---
    function updateDashboardSummary() {
         if (!Array.isArray(allApplications)) {
             console.error("Cannot update summary: allApplications is not an array.");
             return;
         }
        const enrolledCount = allApplications.filter(app => String(app.status).toLowerCase() === 'enrolled').length;
        const pendingCount = allApplications.filter(app => String(app.status).toLowerCase() === 'pending').length;
    const declinedCount = allApplications.filter(app => ['declined','for verification'].includes(String(app.status).toLowerCase())).length;
        if(totalApplicationsSpan) totalApplicationsSpan.textContent = allApplications.length;
        if(enrolledApplicationsSpan) enrolledApplicationsSpan.textContent = enrolledCount;
        if(pendingApplicationsSpan) pendingApplicationsSpan.textContent = pendingCount;
        if(declinedApplicationsSpan) declinedApplicationsSpan.textContent = declinedCount;
        console.log("Dashboard summary updated.");
    }

    function createApplicationRowHTML(app) {
        if (!app || typeof app !== 'object') {
             console.warn("Invalid application data passed to createApplicationRowHTML:", app);
             return '<tr><td colspan="7">Error loading row data</td></tr>';
        }

        const safeLRN = escapeHtml(getSafeValue(app.lrn, 'N/A'));
        const appId = escapeHtml(getSafeValue(app.id, ''));

        const actionCellContent = isAdminGlobal ? `
            <button class="view-btn" data-id="${appId}">View</button>
            <button class="delete-btn" data-id="${appId}" data-lrn="${safeLRN}">Delete</button>
        ` : 'N/A';

         const actionCellClass = isAdminGlobal ? '' : 'admin-only-action-cell'; 

        return `
            <tr data-id="${appId}">
                <td>${safeLRN}</td>
                <td>${escapeHtml(getSafeValue(app.student_last_name))}</td>
                <td>${escapeHtml(getSafeValue(app.student_first_name))}</td>
                <td>${escapeHtml(getSafeValue(app.grade_level))}</td>
                <td>${formatDisplayDate(app.submission_timestamp)}</td>
                <td><span class="status-badge ${getStatusClass(app.status)}"><span class="status-dot"></span>${escapeHtml(getStatusText(app.status))}</span></td>
                <td class="${actionCellClass}">
                    ${actionCellContent}
                </td>
            </tr>`;
    }

    function displayApplicationsInTable(appsToDisplay) {
        if (!applicationsTableBody) {
             console.error("Cannot display applications: Table body not found.");
             return;
        }
        applicationsTableBody.innerHTML = ''; 
        if (!appsToDisplay || !Array.isArray(appsToDisplay) || appsToDisplay.length === 0) {
            const colspan = isAdminGlobal ? 7 : 6; 
            applicationsTableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">No applications match the current filter.</td></tr>`;
            return;
        }
        appsToDisplay.forEach(app => {
            applicationsTableBody.insertAdjacentHTML('beforeend', createApplicationRowHTML(app));
        });
        attachTableButtonListeners();
        updateAdminElementVisibility(); 
        console.log(`Displayed ${appsToDisplay.length} applications.`);
    }

    // --- *** NEW: COMBINED FILTER FUNCTION *** ---
    function applyFilters() {
        const searchTerm = searchApplicationsInput ? searchApplicationsInput.value.toLowerCase().trim() : '';
        
        let filteredApps = allApplications;

        // 1. Filter by Status (from card click)
        if (currentStatusFilter !== 'all') {
            filteredApps = filteredApps.filter(app => 
                String(app.status).toLowerCase() === currentStatusFilter
            );
        }

        // 2. Filter by Search Term
        if (searchTerm !== '') {
            filteredApps = filteredApps.filter(app =>
                getSafeValue(app.lrn).toLowerCase().includes(searchTerm) ||
                getSafeValue(app.student_last_name).toLowerCase().includes(searchTerm) ||
                getSafeValue(app.student_first_name).toLowerCase().includes(searchTerm) ||
                getSafeValue(app.grade_level).toLowerCase().includes(searchTerm) ||
                getSafeValue(app.status).toLowerCase().includes(searchTerm)
            );
        }

        // 3. Display the final filtered list
        displayApplicationsInTable(filteredApps);
    }
    // --- *** END: COMBINED FILTER FUNCTION *** ---

    // --- Navigation ---
    function handleNavigation() {
        let hash = window.location.hash || '#dashboard-section';
        if (!hash.startsWith('#') || !document.getElementById(hash.substring(1))) {
            hash = '#dashboard-section';
        }
        console.log("Handling navigation to:", hash);

        contentSections.forEach(section => {
            section.classList.toggle('active-section', section.id === hash.substring(1));
        });

        navLinks.forEach(link => {
             link.classList.remove('active');
             const parentLi = link.closest('.has-submenu');
             if (parentLi && !parentLi.contains(document.querySelector(`.nav-link.active`)) && !link.classList.contains('submenu-toggle')) {
             } else if (link.classList.contains('submenu-toggle')) {
             }

             const targetSection = link.getAttribute('data-section');
             let directMatch = targetSection === hash.substring(1);

             if (directMatch) {
                 link.classList.add('active');
                 const parentSubmenuLi = link.closest('.has-submenu > .submenu > .nav-item');
                 if (parentSubmenuLi) {
                     const parentToggleLi = parentSubmenuLi.closest('.has-submenu');
                     if (parentToggleLi) {
                         parentToggleLi.classList.add('open');
                         const parentToggleLink = parentToggleLi.querySelector(':scope > .nav-link.submenu-toggle');
                         if (parentToggleLink) parentToggleLink.classList.add('active');
                         const grandParentToggleLi = parentToggleLi.closest('.has-submenu > .submenu > .nav-item')?.closest('.has-submenu');
                          if (grandParentToggleLi) {
                              grandParentToggleLi.classList.add('open');
                              const grandParentToggleLink = grandParentToggleLi.querySelector(':scope > .nav-link.submenu-toggle');
                              if(grandParentToggleLink) grandParentToggleLink.classList.add('active');
                          }
                     }
                 }
             }
        });
        console.log("Navigation handled.");
    }


    // --- Modal Pagination Logic ---
    function changePage(direction) {
        const newIndex = currentPageIndex + direction;
        if (newIndex >= 0 && newIndex < modalPages.length) {
            currentPageIndex = newIndex;
            updateModalPage();
        }
    }

    function updateModalPage() {
        if (!viewDetailsContainer) return;
        const modalPageElements = viewDetailsContainer.querySelectorAll('.modal-page');
        if (!modalPageElements || modalPageElements.length === 0) return;

        modalPageElements.forEach((page, index) => {
            page.classList.toggle('active', index === currentPageIndex);
            page.style.display = index === currentPageIndex ? 'block' : 'none';
        });

        if (modalPageIndicator) modalPageIndicator.textContent = `Page ${currentPageIndex + 1} of ${modalPages.length}`;
        if (modalPrevBtn) modalPrevBtn.style.visibility = currentPageIndex === 0 ? 'hidden' : 'visible';
        if (modalNextBtn) modalNextBtn.style.visibility = currentPageIndex === modalPages.length - 1 ? 'hidden' : 'visible';

        const activePageElement = viewDetailsContainer?.querySelector('.modal-page.active');
        const historyContainer = activePageElement?.querySelector('#modal-history-container');
        
        if (historyContainer && (historyContainer.querySelector('.text-muted.small') || historyContainer.innerHTML.trim() === '')) {
            console.log("History page activated, fetching history...");
            if (currentApplicationData && currentApplicationData.id) {
                fetchHistoryForModal(currentApplicationData.id); 
            } else {
                historyContainer.innerHTML = '<p class="text-danger small text-center">Error: Cannot load history, student data missing.</p>';
            }
        }
        
        attachDynamicEventListeners(); 
    }


    // --- Modal Content Generation ---
    async function populateAndShowModal(data) {
         console.log("Populating modal for:", data);
         if (!data || typeof data !== 'object') {
             console.error("Invalid data for modal:", data);
             viewDetailsContainer.innerHTML = '<p class="text-danger p-3">Error: Could not load application details.</p>';
             if(applicationModal) applicationModal.style.display = 'flex'; 
             return;
         }

        currentApplicationData = data; 
        modalPages = [];

        const createDataPair = (label, value) => `<div class="modal-form-group"><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(getSafeValue(value))}</dd></div>`;
        const createDataPairDate = (label, value) => `<div class="modal-form-group"><dt>${escapeHtml(label)}</dt><dd>${formatDisplayDate(getSafeValue(value))}</dd></div>`;
        const createDataPairYesNo = (label, value) => `<div class="modal-form-group"><dt>${escapeHtml(label)}</dt><dd>${(String(getSafeValue(value, 'no')).toLowerCase() === 'yes' || getSafeValue(value) === 1 || getSafeValue(value) === '1') ? 'Yes' : 'No'}</dd></div>`;
        const createFileLink = (label, url) => {
             if (url && typeof url === 'string' && url.trim() !== '') {
                 const webUrl = url.startsWith('http') ? url : `./${url.replace(/^\//, '')}`; 
                 return `<div class="file-link-item"><strong>${escapeHtml(label)}:</strong> <a class="file-view-link" href="${escapeHtml(webUrl)}" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> View</a></div>`;
             } return '';
        };
         const createMultipleFileLinks = (label, jsonString) => {
            let linksHtml = ''; let filesFoundHere = false;
            try {
                if (jsonString && typeof jsonString === 'string') {
                    const urls = JSON.parse(jsonString);
                    if (Array.isArray(urls) && urls.length > 0) {
                         linksHtml += `<div class="file-link-item"><strong>${escapeHtml(label)}:</strong><br>`;
                         urls.forEach((url, index) => {
                             if (url && typeof url === 'string' && url.trim() !== '') {
                                 filesFoundHere = true;
                                 const webUrl = url.startsWith('http') ? url : `./${url.replace(/^\//, '')}`;
                                 linksHtml += `<a class="file-view-link" href="${escapeHtml(webUrl)}" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> File ${index + 1}</a> `;
                             }
                         });
                         linksHtml += `</div>`;
                    }
                }
            } catch (e) { console.error("Error parsing JSON files:", e); linksHtml = `<div class="file-link-item"><strong>${escapeHtml(label)}:</strong> <span class="text-danger small">Error loading files.</span></div>`; }
            return filesFoundHere ? linksHtml : ''; 
        };


        // --- Page 1 (Basic, Learner, Disability, Address, Parent/Guardian, Files) ---
        let page1Html = '<div class="modal-page active">'; 
        page1Html += '<div class="modal-form-section kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-info-circle"></i> Basic Info</h4><dl>';
        page1Html += createDataPair('ID:', data.id) + createDataPairDate('Submitted:', data.submission_timestamp) + createDataPair('SY:', data.school_year) + createDataPair('Grade:', data.grade_level) + createDataPairYesNo('Returning:', data.returning_student);
        page1Html += '</dl></div>';
        page1Html += '<div class="modal-form-section kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-user-graduate"></i> Learner Info</h4><dl>';
        page1Html += createDataPairYesNo('With LRN?:', data.has_lrn) + 
                    createDataPair('LRN:', data.lrn) + 
                    createDataPairYesNo('Transferee?:', data.is_transferee);

// If transferee is yes, always show the previous school name
if (String(data.is_transferee).toLowerCase() === 'yes' || data.is_transferee === 1 || data.is_transferee === '1') {
    const schoolName = data.previous_school_name && data.previous_school_name.trim() !== '' 
        ? data.previous_school_name 
        : 'N/A';
    page1Html += createDataPair('Previous School Name:', schoolName);
}

        // Continue with the rest of the learner info
        page1Html += createDataPair('Last Name:', data.student_last_name) + 
                    createDataPair('First Name:', data.student_first_name) + 
                    createDataPair('Email:', getSafeValue(data.student_email, '')) + 
                    createDataPair('Middle Name:', getSafeValue(data.student_middle_name, '')) + 
                    createDataPair('Ext. Name:', getSafeValue(data.student_extension_name, '')) + 
                    createDataPairDate('Birthdate:', data.student_birthdate) + 
                    createDataPair('Age:', data.student_age) + 
                    createDataPair('Sex:', data.student_sex) + 
                    createDataPair('Birthplace:', data.student_place_of_birth) + 
                    createDataPair('Mother Tongue:', data.student_mother_tongue) + 
                    createDataPairYesNo('Indigenous?:', data.is_indigenous) + 
                    createDataPair('IP Community:', getSafeValue(data.ip_community, '')) + 
                    createDataPairYesNo('4Ps?:', data.is_4ps_beneficiary) + 
                    createDataPair('4Ps ID:', getSafeValue(data['4ps_household_id'], ''));
        page1Html += '</dl></div>';
         page1Html += '<div class="modal-form-section kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-wheelchair"></i> Disability Info</h4><dl>';
        page1Html += createDataPairYesNo('Has Disability?:', data.has_disability) + createDataPair('Types:', getSafeValue(data.disability_types, '')) + createDataPair('Sub-Types:', getSafeValue(data.disability_sub_types, ''));
        page1Html += '</dl></div>';
         page1Html += '<div class="modal-form-section kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-map-marker-alt"></i> Address Info</h4><h5>Current</h5><dl>';
        page1Html += createDataPair('Addr:', data.current_address_house_no_street) + createDataPair('Street:', data.current_address_street_name) + createDataPair('Brgy:', data.current_address_barangay) + createDataPair('City:', data.current_address_city) + createDataPair('Province:', data.current_address_province) + createDataPair('Country:', data.current_address_country) + createDataPair('Zip:', data.current_address_zip);
        page1Html += '</dl><h5>Permanent</h5><dl>' + createDataPairYesNo('Same?:', data.permanent_address_same_as_current);
        if (String(data.permanent_address_same_as_current).toLowerCase() !== 'yes' && data.permanent_address_same_as_current !== 1 && data.permanent_address_same_as_current !== '1') {
             page1Html += createDataPair('Addr:', getSafeValue(data.permanent_address_house_no_street)) + createDataPair('Street:', getSafeValue(data.permanent_address_street_name)) + createDataPair('Brgy:', getSafeValue(data.permanent_address_barangay)) + createDataPair('City:', getSafeValue(data.permanent_address_city)) + createDataPair('Province:', getSafeValue(data.permanent_address_province)) + createDataPair('Country:', getSafeValue(data.permanent_address_country)) + createDataPair('Zip:', getSafeValue(data.permanent_address_zip));
        }
        page1Html += '</dl></div>';
         page1Html += '<div class="modal-form-section kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-user-friends"></i> Parent/Guardian Info</h4><h5>Father</h5><dl>';
    page1Html += createDataPair("Name:", `${getSafeValue(data.father_first_name)} ${getSafeValue(data.father_middle_name, '')} ${getSafeValue(data.father_last_name)}`.trim()) + createDataPair("Contact:", data.father_contact) + createDataPair("Email:", getSafeValue(data.father_email, ''));
        page1Html += '</dl><h5>Mother</h5><dl>';
    page1Html += createDataPair("Name:", `${getSafeValue(data.mother_first_name)} ${getSafeValue(data.mother_middle_name, '')} ${getSafeValue(data.mother_last_name)}`.trim()) + createDataPair("Contact:", data.mother_contact) + createDataPair("Email:", getSafeValue(data.mother_email, ''));
        page1Html += '</dl><h5>Guardian</h5><dl>';
    page1Html += createDataPair("Name:", `${getSafeValue(data.guardian_first_name, '')} ${getSafeValue(data.guardian_middle_name, '')} ${getSafeValue(data.guardian_last_name, '')}`.trim()) + createDataPair("Contact:", getSafeValue(data.guardian_contact, '')) + createDataPair("Email:", getSafeValue(data.guardian_email, '')) + createDataPair("Relationship:", getSafeValue(data.guardian_relationship, ''));
        page1Html += '</dl></div>';
        
        page1Html += '<div class="modal-form-section file-link-container kinderly-section"><h4 class="modal-section-header-kinderly"><i class="fas fa-folder-open"></i> Uploaded Files</h4>';
        let filesHtml = createFileLink('PSA Certificate', data.psa_birth_cert_url) +
                        createFileLink('Report Card', data.report_card_url) +
                        createMultipleFileLinks('Transferee Documents', data.other_docs_urls_json); 
        if (filesHtml.trim() === '') {
             filesHtml = '<p class="text-muted small">No files were uploaded with this application.</p>';
        }
        page1Html += filesHtml + '</div></div>'; 
        modalPages.push(page1Html);

        // --- Page 2: Status & Section (Admin Only) ---
        if (isAdminGlobal) {
            let page2Html = `<div class="modal-page">
                <form id="update-status-form" action="api/enrollments/update_status_section.php" method="POST">
                    <input type="hidden" name="enrollment_id" value="${escapeHtml(getSafeValue(data.id))}">
                    <input type="hidden" name="csrf_token" value="${window.APP_CSRF_TOKEN || ''}">
                    <div class="modal-form-section kinderly-section">
                        <h4 class="modal-section-header-kinderly"><i class="fas fa-tasks"></i> Update Status & Section</h4>
                        <div class="row align-items-end">
                           <div class="col-md-6 mb-3">
                               <label for="modal-status" class="form-label-kinderly">Status</label>
                               <select name="status" id="modal-status" class="form-select-kinderly">
                                   <option value="Pending" ${String(data.status) === 'Pending' ? 'selected' : ''}>Pending</option>
                                   <option value="Enrolled" ${String(data.status) === 'Enrolled' ? 'selected' : ''}>Enrolled</option>
                                   <option value="For Verification" ${String(data.status) === 'For Verification' || String(data.status) === 'Declined' ? 'selected' : ''}>For Verification</option>
                               </select>
                           </div>
                           <div class="col-md-6 mb-3" id="modal-section-container" style="display: none;">
                               <label for="modal-section-id" class="form-label-kinderly">Assign Section</label>
                               <div id="section-dropdown-container"><p class="text-muted small">Select "Enrolled" to load sections.</p></div>
                           </div>
                        </div>
                        
                        <div class="row">
                           <div class="col-md-12 mb-3">
                                <label for="admin-remarks" class="form-label-kinderly">Admin Note (Visible to student)</label>
                                <textarea name="admin_remarks" id="admin-remarks" class="form-control-kinderly" rows="3" placeholder="e.g., 'For Verification: missing PSA document.' or 'Pending: Awaiting hardcopy of report card.'">${escapeHtml(getSafeValue(data.admin_remarks, ''))}</textarea>
                           </div>
                        </div>
                        <div class="text-end mt-3">
                           <button type="submit" class="btn-kinderly-save"><i class="fas fa-save"></i> Save Status & Section</button>
                         </div>
                    </div>
                 </form>
                 <div id="subject-view-wrapper" class="modal-form-section kinderly-section subjects-management-section mt-3" style="display: none;">
                     <h4 class="modal-section-header-kinderly"><i class="fas fa-book"></i> Assigned Subjects (Auto-updated on Save)</h4>
                     <div id="subjects-list-container"><p class="text-muted small">Subjects are shown for enrolled students with an assigned section.</p></div>
                 </div>
            </div>`; 
            modalPages.push(page2Html);
        }

        // --- Page 3: Financials (Admin Only) ---
         if (isAdminGlobal) {
            const installmentMonths = getSafeValue(data.installment_months, '');
            let page3Html = `<div class="modal-page">
                <form id="update-financials-form" action="api/enrollments/update_financials.php" method="POST">
                    <input type="hidden" name="enrollment_id" value="${escapeHtml(getSafeValue(data.id))}">
                    <input type="hidden" name="csrf_token" value="${window.APP_CSRF_TOKEN || ''}">
                    <div class="modal-form-section admin-only-form-section kinderly-section">
                        <h4 class="modal-section-header-kinderly"><i class="fas fa-dollar-sign"></i> Financial Details</h4>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="modal-tuition-mode" class="form-label-kinderly">Tuition Mode</label>
                                <select name="tuition_mode" id="modal-tuition-mode" class="form-select-kinderly">
                                    <option value="" ${!data.tuition_mode ? 'selected' : ''}>Select Mode...</option>
                                    <option value="Full Payment" ${String(data.tuition_mode) === 'Full Payment' ? 'selected' : ''}>Full Payment</option>
                                    <option value="Installment" ${String(data.tuition_mode) === 'Installment' ? 'selected' : ''}>Installment</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal-total-tuition" class="form-label-kinderly">Total Tuition (PHP)</label>
                                <input type="number" step="0.01" min="0" name="total_tuition" id="modal-total-tuition" class="form-control-kinderly" value="${escapeHtml(getSafeValue(data.total_tuition, '0.00'))}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal-outstanding-balance" class="form-label-kinderly">Outstanding Balance (PHP)</label>
                                <input type="number" step="0.01" min="0" name="outstanding_balance" id="modal-outstanding-balance" class="form-control-kinderly" value="${escapeHtml(getSafeValue(data.outstanding_balance, '0.00'))}">
                            </div>
                        </div>
                        <div id="installment-options-container" class="row" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="installment_months" class="form-label-kinderly">Installment Plan (Months)</label>
                                <select name="installment_months" id="installment_months" class="form-select-kinderly">
                                    <option value="" ${!installmentMonths ? 'selected' : ''}>Select Months...</option>
                                    <option value="2" ${String(installmentMonths) == '2' ? 'selected' : ''}>2 Months</option>
                                    <option value="4" ${String(installmentMonths) == '4' ? 'selected' : ''}>4 Months</option>
                                    <option value="6" ${String(installmentMonths) == '6' ? 'selected' : ''}>6 Months</option>
                                    <option value="10" ${String(installmentMonths) == '10' ? 'selected' : ''}>10 Months</option>
                                    <option value="12" ${String(installmentMonths) == '12' ? 'selected' : ''}>12 Months</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 align-self-end">
                                 <label class="form-label-kinderly">Calculated Monthly Payment</label>
                                 <input type="text" id="monthly-payment-display" class="form-control-kinderly" readonly placeholder="Auto-calculates...">
                            </div>
                        </div>
                        <div class="text-end mt-3">
                           <button type="submit" class="btn-kinderly-save"><i class="fas fa-save"></i> Save Financials</button>
                         </div>
                    </div>
                </form>
            </div>`; 
            modalPages.push(page3Html);
        }

        // --- Page 4 (Enrollment History - Admin Only) ---
        if (isAdminGlobal) {
            let page4Html = `<div class="modal-page">
                <div class="modal-form-section kinderly-section">
                    <h4 class="modal-section-header-kinderly"><i class="fas fa-history"></i> Enrollment History</h4>
                    <div id="modal-history-container">
                    </div>
                     <div class="text-center mt-3">
                        <a href="enrollment_history.php" class="btn btn-sm btn-outline-secondary">Manage Full History</a>
                     </div>
                </div>
            </div>`; 
            modalPages.push(page4Html);
        }

        // --- Show Modal ---
        if (!viewDetailsContainer) { console.error("Modal content container not found."); return; }
        viewDetailsContainer.innerHTML = modalPages.join(''); 
        currentPageIndex = 0; 
        if(modalNavigation) modalNavigation.style.display = modalPages.length > 1 ? 'flex' : 'none';

        updateModalPage(); 
        if(applicationModal) applicationModal.style.display = 'flex'; 
        // Attach download button handler (exports JSON without uploaded files)
        try {
            const downloadBtn = document.getElementById('downloadInfoBtn');
            if (downloadBtn) {
                // remove previous listener to avoid duplicates
                downloadBtn.replaceWith(downloadBtn.cloneNode(true));
            }
        } catch (e) { console.warn('Could not reset download button listeners', e); }
        const downloadBtnEl = document.getElementById('downloadInfoBtn');
        if (downloadBtnEl) {
            downloadBtnEl.addEventListener('click', function() {
                try {
                    if (!currentApplicationData) { alert('No application data available to download.'); return; }

                    const title = `Enrollment Details - ${currentApplicationData.student_last_name || ''}, ${currentApplicationData.student_first_name || ''}`;
                    const asOf = new Date().toLocaleString();

                    function row(label, value) {
                        return `<tr><td style="width:35%;padding:8px;border:1px solid #e6eef8;font-weight:600">${escapeHtml(label)}</td><td style="padding:8px;border:1px solid #e6eef8">${escapeHtml(value)}</td></tr>`;
                    }

                    function buildTableSection(header, pairsHtml) {
                        return `<div style="margin-bottom:12px"><h4 style="margin:6px 0 8px 0;color:#0b5ed7">${escapeHtml(header)}</h4><table style="width:100%;border-collapse:collapse;font-size:13px">${pairsHtml}</table></div>`;
                    }

                    // Build sections using currentApplicationData, excluding uploaded files
                    const d = currentApplicationData;
                    const basicPairs = '' +
                        row('ID:', d.id || '') +
                        row('Submitted:', formatDisplayDate(d.submission_timestamp)) +
                        row('SY:', d.school_year || '') +
                        row('Grade:', d.grade_level || '') +
                        row('Returning:', (String(d.returning_student || d.returning || 'no').toLowerCase() === 'yes' || d.returning_student === 1) ? 'Yes' : 'No');

                    let learnerPairs = '' +
                        row('With LRN?:', (String(d.has_lrn).toLowerCase() === 'yes' || d.has_lrn === 1) ? 'Yes' : 'No') +
                        row('LRN:', d.lrn || '') +
                        row('Transferee?:', (String(d.is_transferee).toLowerCase() === 'yes' || d.is_transferee === 1) ? 'Yes' : 'No');

                    if (String(d.is_transferee).toLowerCase() === 'yes' || d.is_transferee === 1) {
                        learnerPairs += row('Previous School Name:', d.previous_school_name || 'N/A');
                    }

                    learnerPairs += '' +
                        row('Last Name:', d.student_last_name || '') +
                        row('First Name:', d.student_first_name || '') +
                        row('Middle Name:', d.student_middle_name || '') +
                        row('Ext. Name:', d.student_extension_name || '') +
                        row('Birthdate:', formatDisplayDate(d.student_birthdate)) +
                        row('Age:', d.student_age || '') +
                        row('Sex:', d.student_sex || '') +
                        row('Birthplace:', d.student_place_of_birth || '') +
                        row('Mother Tongue:', d.student_mother_tongue || '') +
                        row('Indigenous?:', (String(d.is_indigenous).toLowerCase() === 'yes' || d.is_indigenous === 1) ? 'Yes' : 'No') +
                        row('IP Community:', d.ip_community || '') +
                        row('4Ps?:', (String(d.is_4ps_beneficiary).toLowerCase() === 'yes' || d.is_4ps_beneficiary === 1) ? 'Yes' : 'No') +
                        row('4Ps ID:', d['4ps_household_id'] || '');

                    const disabilityPairs = '' +
                        row('Has Disability?:', (String(d.has_disability).toLowerCase() === 'yes' || d.has_disability === 1) ? 'Yes' : 'No') +
                        row('Types:', d.disability_types || '') +
                        row('Sub-Types:', d.disability_sub_types || '');

                    const currentAddr = '' +
                        row('House No./Street:', d.current_address_house_no_street || '') +
                        row('Street Name:', d.current_address_street_name || '') +
                        row('Barangay:', d.current_address_barangay || '') +
                        row('City:', d.current_address_city || '') +
                        row('Province:', d.current_address_province || '') +
                        row('Country:', d.current_address_country || '') +
                        row('Zip:', d.current_address_zip || '');

                    let permanentHtml = row('Same as current?:', (String(d.permanent_address_same_as_current).toLowerCase() === 'yes' || d.permanent_address_same_as_current === 1) ? 'Yes' : 'No');
                    if (!(String(d.permanent_address_same_as_current).toLowerCase() === 'yes' || d.permanent_address_same_as_current === 1)) {
                        permanentHtml += '' +
                            row('House No./Street:', d.permanent_address_house_no_street || '') +
                            row('Street Name:', d.permanent_address_street_name || '') +
                            row('Barangay:', d.permanent_address_barangay || '') +
                            row('City:', d.permanent_address_city || '') +
                            row('Province:', d.permanent_address_province || '') +
                            row('Country:', d.permanent_address_country || '') +
                            row('Zip:', d.permanent_address_zip || '');
                    }

                    const parentPairs = '' +
                        row('Father Name:', `${d.father_first_name || ''} ${d.father_middle_name || ''} ${d.father_last_name || ''}`.trim()) +
                        row('Father Contact:', d.father_contact || '') +
                        row('Father Email:', d.father_email || '') +
                        row('Mother Name:', `${d.mother_first_name || ''} ${d.mother_middle_name || ''} ${d.mother_last_name || ''}`.trim()) +
                        row('Mother Contact:', d.mother_contact || '') +
                        row('Mother Email:', d.mother_email || '') +
                        row('Guardian Name:', `${d.guardian_first_name || ''} ${d.guardian_middle_name || ''} ${d.guardian_last_name || ''}`.trim()) +
                        row('Guardian Contact:', d.guardian_contact || '') +
                        row('Guardian Email:', d.guardian_email || '') +
                        row('Relationship:', d.guardian_relationship || '');

                    // Financials (admin only)
                    let financialPairs = '';
                    if (isAdminGlobal) {
                        financialPairs += '' +
                            row('Tuition Mode:', d.tuition_mode || '') +
                            row('Total Tuition (PHP):', d.total_tuition || '') +
                            row('Outstanding Balance (PHP):', d.outstanding_balance || '') +
                            row('Installment Months:', d.installment_months || '');
                    }

                    // Compose printable body
                    const bodyHtml = '' +
                        buildTableSection('Basic Info', basicPairs) +
                        buildTableSection('Learner Info', learnerPairs) +
                        buildTableSection('Disability Info', disabilityPairs) +
                        buildTableSection('Current Address', currentAddr) +
                        buildTableSection('Permanent Address', permanentHtml) +
                        buildTableSection('Parent / Guardian Info', parentPairs) +
                        (financialPairs ? buildTableSection('Financial Details', financialPairs) : '');

                    const printable = `<!doctype html>
                        <html>
                        <head>
                            <meta charset="utf-8"/>
                            <title>${escapeHtml(title)}</title>
                            <style>
                                @page { margin: 18mm; }
                                body{font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color:#111; margin:0; padding:12px}
                                .header{display:flex;align-items:center;gap:12px;margin-bottom:10px}
                                .logo{width:72px;height:auto;border-radius:6px}
                                .school-name{font-size:18px;font-weight:700;color:#0b5ed7}
                                .report-title{font-size:14px;margin-top:2px;color:#333}
                                .meta{color:#6b7280;font-size:12px;margin-top:6px}
                                table{width:100%;border-collapse:collapse;margin-bottom:8px}
                                thead th{background:#f5f9ff}
                                td{vertical-align:top}
                                @media print{ thead th{ -webkit-print-color-adjust: exact } }
                            </style>
                        </head>
                        <body>
                            <div class="header">
                                <img class="logo" src="${location.origin + location.pathname.replace(/\/[^/]*$/, '')}/FVRES Pics/logo_monte_cristo.jpg" alt="MCREI Logo" onerror="this.style.display='none'" />
                                <div>
                                    <div class="school-name">Monte Cristo Research and Educational Institute</div>
                                    <div class="report-title">${escapeHtml(title)}</div>
                                    <div class="meta">Generated: ${escapeHtml(asOf)}</div>
                                </div>
                            </div>
                            <div>${bodyHtml}</div>
                            <footer style="margin-top:14px;color:#6b7280;font-size:11px">Generated by MCREI Enrollment System</footer>
                        </body>
                        </html>`;

                    const w = window.open('', '_blank');
                    if (!w) { alert('Unable to open print window (popup blocked). Allow popups and try again.'); return; }
                    w.document.open(); w.document.write(printable); w.document.close();
                    setTimeout(()=>{ try { w.focus(); w.print(); } catch(e){ console.error(e); } }, 500);

                } catch (err) {
                    console.error('Download (PDF) error:', err);
                    alert('Failed to prepare PDF. See console for details.');
                }
            });
        }
        console.log("Modal populated and shown.");
    }

    function getDownloadableApplicationData(data) {
        // Deep copy
        let copy;
        try { copy = JSON.parse(JSON.stringify(data)); } catch (e) { copy = Object.assign({}, data); }

        // Remove keys that likely point to uploaded files or file URLs
        Object.keys(copy).forEach(k => {
            if (/psa|report|doc|file|url|upload|other_docs|attachment/i.test(k)) {
                delete copy[k];
            }
        });

        // Additionally, if there are nested objects or arrays containing file URLs, remove them
        Object.keys(copy).forEach(k => {
            const v = copy[k];
            if (typeof v === 'string' && /https?:\/\/|\.pdf$|\.jpg$|\.png$|uploads\//i.test(v)) {
                delete copy[k];
            }
            if (Array.isArray(v)) {
                // filter array entries that look like file paths/urls
                copy[k] = v.filter(item => !(typeof item === 'string' && /https?:\/\/|\.pdf$|\.jpg$|\.png$|uploads\//i.test(item)));
                if (copy[k].length === 0) delete copy[k];
            }
        });

        // Return cleaned object
        return copy;
    }


    async function fetchHistoryForModal(enrollmentId) {
        const historyContainer = document.getElementById('modal-history-container');
        if (!historyContainer) {
            console.warn("Modal history container not found.");
            return;
        }
        console.log(`Fetching history for enrollment ID: ${enrollmentId}`);
        historyContainer.innerHTML = '<p class="text-muted small text-center"><i class="fas fa-spinner fa-spin"></i> Loading history...</p>'; 

        try {
            const historyData = await fetchAPI(`${HISTORY_API_URL}?action=get_history&enrollment_id=${enrollmentId}`);
            console.log("History API Response:", historyData);

            if (historyData.success && Array.isArray(historyData.history)) {
                renderHistoryInModal(historyData.history, enrollmentId); 
            } else {
                throw new Error(historyData.message || "Could not load history data.");
            }
        } catch (error) {
            console.error("Error fetching history for modal:", error);
            historyContainer.innerHTML = `<p class="text-danger small text-center">Error loading history: ${escapeHtml(error.message)}</p>`;
        }
    }


    function renderHistoryInModal(historyRecords, enrollmentId) { 
        const historyContainer = document.getElementById('modal-history-container');
        if (!historyContainer) return;

        if (!historyRecords || historyRecords.length === 0) {
            historyContainer.innerHTML = '<p class="text-muted small text-center">No enrollment history found for this student.</p>';
            return;
        }

        const groupedHistory = historyRecords.reduce((acc, record) => {
            const key = `${record.school_year || 'N/A'}_${record.grade_level || 'N/A'}`; 
            if (!acc[key]) {
                acc[key] = {
                    school_year: record.school_year,
                    grade_level: record.grade_level,
                    records: [] 
                };
            }
            acc[key].records.push(record);
            return acc;
        }, {});

        const groupedArray = Object.values(groupedHistory).sort((a, b) => {
            const gradeA = a.grade_level || '';
            const gradeB = b.grade_level || '';
    
            if (gradeA === 'Kindergarten' && gradeB !== 'Kindergarten') return -1;
            if (gradeB === 'Kindergarten' && gradeA !== 'Kindergarten') return 1;
    
            const numA = parseInt(gradeA);
            const numB = parseInt(gradeB);
    
            if (!isNaN(numA) && !isNaN(numB)) {
                return numA - numB; 
            }
    
            return gradeA.localeCompare(gradeB);
        });


        let tableHtml = `<p class="text-muted small mb-2 text-center">Click 'View Subjects' for details of each year/grade.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped subjects-table-modal">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Grade Level</th>
                            <th style="width: 120px;">Actions</th> 
                        </tr>
                    </thead>
                    <tbody>`;

        groupedArray.forEach(group => {
            const escapedGroupRecordsJson = JSON.stringify(group.records).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
            tableHtml += `<tr>
                <td>${escapeHtml(getSafeValue(group.school_year))}</td>
                <td>${escapeHtml(getSafeValue(group.grade_level))}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info view-history-group-btn"
                            data-group-records='${escapedGroupRecordsJson}'
                            data-year="${escapeHtml(getSafeValue(group.school_year))}"
                            data-grade="${escapeHtml(getSafeValue(group.grade_level))}"
                            title="View Subjects for ${escapeHtml(getSafeValue(group.school_year))} - ${escapeHtml(getSafeValue(group.grade_level))}">
                        <i class="fas fa-eye"></i> View Subjects
                    </button>
                </td>
            </tr>`;
        });

        tableHtml += `</tbody></table></div>`;
        historyContainer.innerHTML = tableHtml;

        historyContainer.querySelectorAll('.view-history-group-btn').forEach(button => {
            button.addEventListener('click', function() {
                try {
                    const groupRecords = JSON.parse(this.dataset.groupRecords.replace(/&apos;/g, "'").replace(/&quot;/g, '"'));
                    const year = this.dataset.year;
                    const grade = this.dataset.grade;
                    renderHistoryDetailView(groupRecords, enrollmentId, year, grade); 
                } catch (e) {
                    console.error("Error parsing group record data for detail view:", e, this.dataset.groupRecords);
                    historyContainer.innerHTML = '<p class="text-danger small text-center">Error loading details.</p>';
                }
            });
        });

        console.log("Grouped history table rendered in modal.");
    }

    function renderHistoryDetailView(recordsArray, enrollmentId, year, grade) {
        const historyContainer = document.getElementById('modal-history-container');
        if (!historyContainer) return;

        let detailHtml = `
            <button class="btn btn-sm btn-outline-secondary mb-3 back-to-history-list-btn" data-enrollment-id="${enrollmentId}">
                <i class="fas fa-arrow-left"></i> Back to History List
            </button>
            <h5>Subjects for ${escapeHtml(grade)} (${escapeHtml(year)})</h5>
        `;

        if (!recordsArray || recordsArray.length === 0) {
            detailHtml += '<p class="text-muted small">No subject details found for this period.</p>';
        } else {
            detailHtml += `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped subjects-table-modal">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Grade</th>
                                <th>Teacher</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>`;
            recordsArray.forEach(record => {
                detailHtml += `
                    <tr>
                        <td>${escapeHtml(getSafeValue(record.subject))}</td>
                        <td>${escapeHtml(getSafeValue(record.description, 'None'))}</td>
                        <td>${escapeHtml(getSafeValue(record.grades, 'N/A'))}</td>
                        <td>${escapeHtml(getSafeValue(record.teacher_name, 'N/A'))}</td>
                        <td>${escapeHtml(getSafeValue(record.remarks))}</td>
                    </tr>`;
            });
            detailHtml += `</tbody></table></div>`;
        }

        historyContainer.innerHTML = detailHtml;

        const backButton = historyContainer.querySelector('.back-to-history-list-btn');
        if (backButton) {
            backButton.addEventListener('click', function() {
                fetchHistoryForModal(this.dataset.enrollmentId); 
            });
        }
    }

    function hideModal(modalElement) {
        if (!modalElement) return;
        
        modalElement.style.display = 'none';
        console.log("Hiding modal:", modalElement.id);

        if (modalElement === applicationModal) {
            if (viewDetailsContainer) viewDetailsContainer.innerHTML = '';
            if (modalNavigation) modalNavigation.style.display = 'none';
            currentApplicationData = null;
        } else if (modalElement === addManualModal) {
            const form = modalElement.querySelector('form');
            if (form) form.reset();
        }
    }

    async function populateSectionsDropdown(gradeLevel, currentSectionName) {
         const container = document.getElementById('section-dropdown-container');
         if (!container) { console.warn("Section dropdown container not found."); return; }
         container.innerHTML = '<p class="text-muted small">Loading sections...</p>';

         if (!gradeLevel) {
             container.innerHTML = '<p class="text-danger small">Cannot load sections: Grade level missing.</p>';
             return;
         }

         try {
             const result = await fetchAPI(`${SECTIONS_API_URL}get_for_grade.php?grade_level=${encodeURIComponent(gradeLevel)}`);
             console.log("Sections API Response:", result); 
             if (result.success && Array.isArray(result.sections)) {
                 if (result.sections.length > 0) {
                     let currentSectionId = null;
                     const currentSection = result.sections.find(sec => sec.section_name === currentSectionName);
                     if (currentSection) currentSectionId = currentSection.id;

                     let selectHTML = '<select name="section_id" id="modal-section-id" class="form-select-kinderly" required>'; 
                     selectHTML += '<option value="">-- Select a Section --</option>';
                     result.sections.forEach(section => {
                         const isSelected = (section.id == currentSectionId) ? 'selected' : ''; 
                         selectHTML += `<option value="${escapeHtml(section.id)}" ${isSelected}>${escapeHtml(section.section_name)}</option>`;
                     });
                     selectHTML += '</select>';
                     container.innerHTML = selectHTML;
                     console.log("Sections dropdown populated.");
                 } else {
                     container.innerHTML = '<p class="text-muted small">No sections defined for this grade level.</p>';
                 }
             } else {
                  throw new Error(result.message || 'Could not load sections.');
             }
         } catch (error) {
             console.error("Error populating sections dropdown:", error);
             container.innerHTML = `<p class="text-danger small">Error loading sections: ${escapeHtml(error.message)}</p>`;
         }
    }


    function handleFinancialCalculations() {
        const tuitionModeSelect = document.getElementById('modal-tuition-mode');
        const installmentContainer = document.getElementById('installment-options-container');
        const totalTuitionInput = document.getElementById('modal-total-tuition');
        const balanceInput = document.getElementById('modal-outstanding-balance');
        const monthsSelect = document.getElementById('installment_months');
        const monthlyDisplay = document.getElementById('monthly-payment-display');

        if (!tuitionModeSelect || !installmentContainer || !totalTuitionInput || !balanceInput || !monthsSelect || !monthlyDisplay) {
            console.warn("One or more financial elements not found in the modal page 3.");
            return;
        }

        const calculate = () => {
            const mode = tuitionModeSelect.value;
            // Use outstanding balance for installment calculations (user request)
            const outstanding = parseFloat(balanceInput.value) || 0;

            if (mode === 'Full Payment') {
                installmentContainer.style.display = 'none';
                monthsSelect.value = '';
                monthlyDisplay.value = '';
                monthsSelect.removeAttribute('required');
            } else if (mode === 'Installment') {
                installmentContainer.style.display = 'flex'; 
                monthsSelect.setAttribute('required', 'required');
                const months = parseInt(monthsSelect.value);
                if (outstanding > 0 && months > 0) {
                    monthlyDisplay.value = `₱${(outstanding / months).toFixed(2)} / month`;
                } else {
                    monthlyDisplay.value = 'Select plan & enter outstanding balance';
                }
            } else { 
                 installmentContainer.style.display = 'none';
                 monthsSelect.value = '';
                 monthlyDisplay.value = '';
                 monthsSelect.removeAttribute('required');
            }
        };

    tuitionModeSelect.removeEventListener('change', calculate);
    monthsSelect.removeEventListener('change', calculate);
    totalTuitionInput.removeEventListener('input', calculate);
    balanceInput.removeEventListener('input', calculate);

    tuitionModeSelect.addEventListener('change', calculate);
    monthsSelect.addEventListener('change', calculate);
    totalTuitionInput.addEventListener('input', calculate);
    balanceInput.addEventListener('input', calculate);

        calculate(); 
        console.log("Financial calculations attached/updated.");
    }


    function attachDynamicEventListeners() {
         console.log("Attaching dynamic listeners for modal page:", currentPageIndex);
         const activePage = viewDetailsContainer?.querySelector('.modal-page.active');
         if (!activePage) {
             console.warn("Could not find active modal page to attach listeners.");
             return;
         }

        const statusSelect = activePage.querySelector('#modal-status');
        const sectionContainer = activePage.querySelector('#modal-section-container');
        const subjectWrapper = activePage.querySelector('#subject-view-wrapper');

        const updateSectionVisibility = () => {
             if (!statusSelect) return; 
             const isEnrolled = statusSelect.value === 'Enrolled';
             console.log("Status changed. Is Enrolled:", isEnrolled);

             if (sectionContainer) sectionContainer.style.display = isEnrolled ? 'block' : 'none';
             if (subjectWrapper) subjectWrapper.style.display = isEnrolled ? 'block' : 'none';

             if (isEnrolled && currentApplicationData) {
                 console.log("Populating dropdown for grade:", currentApplicationData.grade_level, "Current section:", currentApplicationData.section);
                 populateSectionsDropdown(currentApplicationData.grade_level, currentApplicationData.section); 
                 renderSubjectsTable(subjectWrapper);
             } else if (subjectWrapper) {
                  const container = subjectWrapper.querySelector('#subjects-list-container');
                  if (container) container.innerHTML = '<p class="text-muted small">Subjects are shown for enrolled students with an assigned section.</p>';
             }
        };

        if (statusSelect) {
            statusSelect.removeEventListener('change', updateSectionVisibility);
            statusSelect.addEventListener('change', updateSectionVisibility);
            updateSectionVisibility();
        }

        if (activePage.querySelector('#modal-tuition-mode')) {
            handleFinancialCalculations();
        }
    }


    async function renderSubjectsTable(wrapperElement) {
        if (!wrapperElement) { console.warn("Subject wrapper not found."); return; }
        const container = wrapperElement.querySelector('#subjects-list-container');
        if (!container) { console.warn("Subject list container not found."); return; }
        if (!currentApplicationData) { container.innerHTML = '<p class="text-danger small">Error: Student data missing.</p>'; return; }

        container.innerHTML = '<p class="text-muted small">Loading assigned subjects...</p>';
        try {
            const data = await fetchAPI(`${API_URL}manage_subjects.php?enrollment_id=${currentApplicationData.id}`);
            console.log("Subjects Data:", data); 
            let tableHtml = '<p class="text-muted small">No subjects currently assigned.</p>';

            if (data.success && Array.isArray(data.subjects) && data.subjects.length > 0) {
                tableHtml = `<div class="table-responsive">
                    <table class="subjects-table-modal table table-sm table-bordered table-striped">
                        <thead><tr><th>Subject</th><th>Teacher</th><th>Schedule</th><th>Time</th><th>Room</th></tr></thead>
                        <tbody>`;
                data.subjects.forEach(s => {
                    tableHtml += `<tr>
                        <td>${escapeHtml(getSafeValue(s.subject_name))}</td>
                        <td>${escapeHtml(getSafeValue(s.teacher_name, 'TBA'))}</td>
                        <td>${escapeHtml(getSafeValue(s.schedule, 'TBA'))}</td>
                        <td>${escapeHtml(getSafeValue(s.time_slot, 'TBA'))}</td>
                        <td>${escapeHtml(getSafeValue(s.room, 'TBA'))}</td>
                    </tr>`;
                });
                tableHtml += `</tbody></table></div>`;
            } else if (!data.success) {
                 tableHtml = `<p class="text-danger small">Error loading subjects: ${escapeHtml(data.message)}</p>`;
            }
            container.innerHTML = tableHtml;
        } catch (error) {
            console.error("Error fetching/rendering subjects:", error);
            container.innerHTML = `<p class="text-danger small">Could not load assigned subjects: ${escapeHtml(error.message)}</p>`;
        }
    }


    // --- Event Listeners ---
    function attachTableButtonListeners() {
        if (!applicationsTableBody) return;
        applicationsTableBody.removeEventListener('click', handleTableButtonClick); 
        applicationsTableBody.addEventListener('click', handleTableButtonClick);
        console.log("Table button listeners attached.");
    }

    async function handleTableButtonClick(event) {
         console.log("Table button clicked:", event.target);
         const viewBtn = event.target.closest('.view-btn');
         const deleteBtn = event.target.closest('.delete-btn');

         if (viewBtn) {
             const id = viewBtn.dataset.id;
             console.log("View button clicked for ID:", id);
             const data = Array.isArray(allApplications) ? allApplications.find(e => String(e.id) === id) : null;
             if (data) {
                 await populateAndShowModal(data);
             } else {
                 console.error("Application data not found locally for ID:", id);
                 alert("Error: Could not find details for this application.");
             }
         }

         if (deleteBtn && isAdminGlobal) { 
             const id = deleteBtn.dataset.id;
             const lrn = deleteBtn.dataset.lrn || 'this application';
             console.log("Delete button clicked for ID:", id);

             if (confirm(`Are you sure you want to delete application for LRN ${lrn}? This cannot be undone.`)) {
                 console.log("Deletion confirmed for ID:", id);
                 try {
                     const result = await fetchAPI(`${API_URL}delete.php`, {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json' },
                         body: JSON.stringify({ id: id })
                     });
                     console.log("Delete API response:", result);

                     if (result.success) {
                         alert(result.message || 'Application deleted.');
                         allApplications = allApplications.filter(app => String(app.id) !== id);
                         applyFilters(); // REFRESH TABLE using filters
                         updateDashboardSummary();
                     } else {
                          throw new Error(result.message || 'Failed to delete.');
                     }
                 } catch (error) {
                     console.error("Error during deletion:", error);
                     alert('Deletion Error: ' + error.message);
                 }
             } else {
                 console.log("Deletion cancelled for ID:", id);
             }
         } else if (deleteBtn && !isAdminGlobal) {
             alert("You do not have permission to delete applications.");
         }
    }


    function initializeEventListeners() {
        console.log("Initializing event listeners...");
        if (logoutButton) logoutButton.addEventListener('click', performLogout);

        navLinks.forEach(link => {
            const targetSection = link.getAttribute('data-section');
            if (targetSection && !link.classList.contains('submenu-toggle')) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.hash = targetSection;
                });
            }
        });
        window.addEventListener('hashchange', handleNavigation);

        if (modalPrevBtn) modalPrevBtn.addEventListener('click', () => changePage(-1));
        if (modalNextBtn) modalNextBtn.addEventListener('click', () => changePage(1));
        if (closeViewModalBtn) closeViewModalBtn.addEventListener('click', () => hideModal(applicationModal));
        if (applicationModal) applicationModal.addEventListener('click', (event) => (event.target === applicationModal) && hideModal(applicationModal));

        // Modify the Add Manually button section
        const addManuallyBtn = document.getElementById('add-enrollee-btn');
        if (addManuallyBtn) {
            console.log("Found Add Manually button, attaching listener");
            addManuallyBtn.addEventListener('click', () => {
                console.log("Add Manually button clicked");
                const manualModal = document.getElementById('addManualModal');
                if (manualModal) {
                    manualModal.style.display = 'flex';
                    // Make sure these listeners are attached after showing the modal
                    if (closeAddManualModalBtn) {
                        closeAddManualModalBtn.addEventListener('click', () => hideModal(manualModal));
                    }
                    if (cancelAddManualBtn) {
                        cancelAddManualBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            hideModal(manualModal);
                        });
                    }
                    // Close on outside click
                    manualModal.addEventListener('click', (event) => {
                        if (event.target === manualModal) {
                            hideModal(manualModal);
                        }
                    });
                } else {
                    console.error("Add Manual Modal not found");
                }
            });
        } else {
            console.warn("Add Manually button not found on page");
        }

        searchApplicationsInput.addEventListener('input', applyFilters);
        allCards.forEach(card => {
            if (card) {
                card.addEventListener('click', function() {
                    allCards.forEach(c => c.classList.remove('active-card'));
                    this.classList.add('active-card');
                    
                    if (this.classList.contains('total-students')) {
                        currentStatusFilter = 'all';
                    } else if (this.classList.contains('enrolled-applications')) {
                        currentStatusFilter = 'enrolled';
                    } else if (this.classList.contains('pending-applications')) {
                        currentStatusFilter = 'pending';
                    } else if (this.classList.contains('declined-applications')) {
                        currentStatusFilter = 'for verification';
                    }
                    
                    console.log("Card filter set to:", currentStatusFilter);
                    applyFilters();
                });
            }
        });


        // --- *** MODIFIED: Simplified Accordion Menu Logic *** ---
        const submenuToggles = document.querySelectorAll('.sidebar .submenu-toggle');
        console.log(`Found ${submenuToggles.length} submenu toggles.`);
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const parentItem = this.closest('.has-submenu');
                if (!parentItem) {
                     console.warn("Could not find parent '.has-submenu' for toggle:", this);
                     return;
                }

                const currentlyOpen = parentItem.classList.contains('open');
                console.log(`Toggle clicked for: ${this.textContent.trim()}. Currently open: ${currentlyOpen}`);

                // --- REMOVED: Logic that closes other submenus ---
                
                // Toggle the clicked one
                parentItem.classList.toggle('open', !currentlyOpen);
                this.classList.toggle('active', !currentlyOpen);
                console.log(`Toggled submenu: ${this.textContent.trim()}. Now open: ${!currentlyOpen}`);
            });
        });
        console.log("Accordion listeners attached.");
        // --- *** END MODIFICATION *** ---
    }

    async function init() {
        console.log("Initializing dashboard...");
        if (await fetchSessionData()) {
            console.log("Session authenticated. Proceeding with init.");
            if (typeof enrollmentsData !== 'undefined' && Array.isArray(enrollmentsData)) {
                allApplications = enrollmentsData;
                console.log(`Loaded ${allApplications.length} enrollments from PHP.`);
            } else {
                 console.warn("enrollmentsData from PHP not found or invalid. Attempting API fetch...");
                 try {
                     const apiData = await fetchAPI(API_URL);
                     if (Array.isArray(apiData)) {
                         allApplications = apiData;
                         console.log(`Loaded ${allApplications.length} enrollments from API fallback.`);
                     } else {
                         console.error("API fallback did not return an array:", apiData);
                         allApplications = []; 
                     }
                 } catch (e) {
                     console.error("Failed to fetch initial enrollment data via API fallback:", e);
                     allApplications = []; 
                 }
            }
            updateDashboardSummary();
            if(totalApplicationsCard) totalApplicationsCard.classList.add('active-card');
            applyFilters(); 
            initializeEventListeners();
            handleNavigation(); 
        } else {
             console.error("Initialization aborted due to failed session authentication.");
        }
        console.log("Dashboard initialization complete.");
    }

    // --- Start the application ---
    init();
});

// --- Helper functions for manual add modal (keep outside DOMContentLoaded) ---
function calculateManualAge() {
    const birthdateInput = document.getElementById('manual_student_birthdate');
    const ageInput = document.getElementById('manual_student_age');
    if (birthdateInput && ageInput && birthdateInput.value) {
        try {
            const birthDate = new Date(birthdateInput.value);
            const today = new Date();
            if (isNaN(birthDate.getTime())) { ageInput.value = ''; return; } 
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) { age--; }
            ageInput.value = age >= 0 ? age : '';
        } catch (e) { console.error("Error calculating age:", e); ageInput.value = ''; }
    } else if (ageInput) { ageInput.value = ''; }
}

function toggleManualPermanentAddress(show) {
    const fieldsDiv = document.getElementById('manual_permanent_address_fields');
    if (!fieldsDiv) return;
    const inputs = fieldsDiv.querySelectorAll('input, select');
    fieldsDiv.style.display = show ? 'block' : 'none';
    inputs.forEach(input => {
        if (show) { input.setAttribute('required', 'required'); }
        else {
            input.removeAttribute('required'); input.value = '';
            const errorSpan = document.getElementById(input.id + '-error');
            if (errorSpan) { errorSpan.textContent = ''; errorSpan.style.display = 'none'; }
            input.classList.remove('input-error');
        }
    });
}

function toggleManualDisabilityFields(show) {
     const container = document.getElementById('manual_disability_details_container');
     if (container) {
          container.style.display = show ? 'block' : 'none';
          const inputs = container.querySelectorAll('input[type="checkbox"], input[type="text"]');
          inputs.forEach(input => {
              if (!show) {
                  if (input.type === 'checkbox') input.checked = false;
                  if (input.type === 'text') input.value = '';
              }
          });
     }
}

function toggleManualPreviousSchool(show) {
    const previousSchoolField = document.getElementById('manual_previous_school_name_container');
    if (!previousSchoolField) return;
    
    previousSchoolField.style.display = show ? 'block' : 'none';
    const input = previousSchoolField.querySelector('input');
    if (input) {
        if (show) {
            input.setAttribute('required', 'required');
        } else {
            input.removeAttribute('required');
            input.value = '';
            const errorSpan = document.getElementById(input.id + '-error');
            if (errorSpan) {
                errorSpan.textContent = '';
                errorSpan.style.display = 'none';
            }
            input.classList.remove('input-error');
        }
    }
}

// Add this function with the other helper functions
function handleManualTransfereeChange() {
    toggleManualPreviousSchool(this.value === 'yes');
}

function attachManualModalListeners() {
    console.log("Attaching manual modal listeners...");
     const manualBirthdateInput = document.getElementById('manual_student_birthdate');
     if (manualBirthdateInput) {
         manualBirthdateInput.removeEventListener('change', calculateManualAge); 
         manualBirthdateInput.addEventListener('change', calculateManualAge);
     } else { console.warn("Manual birthdate input not found."); }

     const manualSameAddressRadios = document.querySelectorAll('input[name="manual_permanent_address_same_as_current"]');
     manualSameAddressRadios.forEach(radio => {
         radio.removeEventListener('change', handleManualSameAddressChange); 
         radio.addEventListener('change', handleManualSameAddressChange);
     });

     const manualDisabilityRadios = document.querySelectorAll('input[name="manual_has_disability"]');
      manualDisabilityRadios.forEach(radio => {
          radio.removeEventListener('change', handleManualDisabilityChange); 
          radio.addEventListener('change', handleManualDisabilityChange);
      });

    // Add the transferee radio button listeners
    const manualTransfereeRadios = document.querySelectorAll('input[name="manual_is_transferee"]');
    manualTransfereeRadios.forEach(radio => {
        radio.removeEventListener('change', handleManualTransfereeChange);
        radio.addEventListener('change', handleManualTransfereeChange);
    });

    // Initialize the previous school field visibility based on initial transferee value
    const initialTransfereeValue = document.querySelector('input[name="manual_is_transferee"]:checked')?.value;
    if (initialTransfereeValue) {
        toggleManualPreviousSchool(initialTransfereeValue === 'yes');
    }

     console.log("Manual modal listeners attached.");
}