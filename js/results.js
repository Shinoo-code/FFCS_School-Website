// Final-School-Web/js/results.js

document.addEventListener('DOMContentLoaded', function () {
    const statusForm = document.getElementById('statusForm');
    const lrnInput = document.getElementById('lrnToFind');
    const paymentStatusMessageDiv = document.getElementById('payment-status-message');
    const statusModal = document.getElementById('statusModal');
    const modalStatusContent = document.getElementById('modalStatusContent');
    const closeStatusModalBtn = document.getElementById('closeStatusModal');
    
    const modalNavigation = document.getElementById('modal-navigation');
    const modalPrevBtn = document.getElementById('modal-prev-btn');
    const modalNextBtn = document.getElementById('modal-next-btn');
    const modalPageIndicator = document.getElementById('modal-page-indicator');

    let currentPageIndex = 0;
    let pages = [];
    let currentEnrollmentData = null; 

    // --- NEW: Helper function ---
    const escapeHtml = (unsafe) => {
         if (unsafe === null || unsafe === undefined) return '';
         return String(unsafe)
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
     };
    // --- End Helper ---

    const urlParams = new URLSearchParams(window.location.search);
    const lrnParam = urlParams.get('lrn');
    const paymentStatus = urlParams.get('payment');

    if (paymentStatusMessageDiv) {
        if (paymentStatus === 'success') {
            paymentStatusMessageDiv.innerHTML = '<div class="alert alert-success">Payment successful! Your enrollment is being updated. Please check your status again shortly.</div>';
        } else if (paymentStatus === 'cancelled') {
            paymentStatusMessageDiv.innerHTML = '<div class="alert alert-warning">Payment was cancelled. You can try again from your status details.</div>';
        }
    }
    
    if (lrnParam && lrnInput) {
        lrnInput.value = lrnParam;
        fetchEnrollmentStatus(lrnParam); 
    }
    
    if (statusForm) {
        statusForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const lrnToFind = lrnInput ? lrnInput.value.trim() : '';
            if (lrnToFind) {
                const newUrl = window.location.pathname + '?lrn=' + encodeURIComponent(lrnToFind);
                history.pushState({ path: newUrl }, '', newUrl);
                fetchEnrollmentStatus(lrnToFind);
            } else {
                showModalWithContent('<p class="modal-message-generic modal-message-error">Please enter a Learner Reference Number (LRN).</p>');
            }
        });
    }

    if (closeStatusModalBtn) closeStatusModalBtn.addEventListener('click', hideModal);
    if (statusModal) {
        statusModal.addEventListener('click', (event) => (event.target === statusModal) && hideModal());
        window.addEventListener('keydown', (event) => (event.key === 'Escape' && statusModal.style.display === 'flex') && hideModal());
    }

    function hideModal() {
        if (statusModal) statusModal.style.display = 'none';
        if (modalStatusContent) modalStatusContent.innerHTML = '';
        if (modalNavigation) modalNavigation.style.display = 'none';
        currentEnrollmentData = null;
    }

    function setupPagination() {
        if (modalPrevBtn) modalPrevBtn.addEventListener('click', () => changePage(-1));
        if (modalNextBtn) modalNextBtn.addEventListener('click', () => changePage(1));
    }

    function changePage(direction) {
        const newIndex = currentPageIndex + direction;
        if (newIndex >= 0 && newIndex < pages.length) {
            currentPageIndex = newIndex;
            updateModalPage();
        }
    }

    function updateModalPage() {
        const modalPages = modalStatusContent.querySelectorAll('.modal-page');
        modalPages.forEach((page, index) => {
            page.classList.toggle('active', index === currentPageIndex);
        });

        if (modalPageIndicator) modalPageIndicator.textContent = `Page ${currentPageIndex + 1} of ${pages.length}`;
        
        if (modalPrevBtn) modalPrevBtn.style.visibility = currentPageIndex === 0 ? 'hidden' : 'visible';
        if (modalNextBtn) modalNextBtn.style.visibility = currentPageIndex === pages.length - 1 ? 'hidden' : 'visible';

        attachDynamicEventListeners();
    }
    
    function showModalWithPages(htmlPagesArray) {
        pages = htmlPagesArray;
        currentPageIndex = 0;
        
        modalStatusContent.innerHTML = pages.map((pageHtml, index) => 
            `<div class="modal-page ${index === 0 ? 'active' : ''}">${pageHtml}</div>`
        ).join('');

        if (pages.length > 1) {
            if (modalNavigation) modalNavigation.style.display = 'flex';
            updateModalPage();
        } else {
            if (modalNavigation) modalNavigation.style.display = 'none';
            attachDynamicEventListeners();
        }
        
        if (statusModal) statusModal.style.display = 'flex';
    }

    function showModalWithContent(singleContentHtml) {
        pages = [singleContentHtml];
        currentPageIndex = 0;
        modalStatusContent.innerHTML = `<div class="modal-page active">${singleContentHtml}</div>`;
        if (modalNavigation) modalNavigation.style.display = 'none';
        if (statusModal) statusModal.style.display = 'flex';
    }
    
    function attachDynamicEventListeners() {
        const activePage = modalStatusContent.querySelector('.modal-page.active');
        if (!activePage) return;

        const paymentForm = activePage.querySelector('#payment-form');
        if (paymentForm) {
            paymentForm.addEventListener('submit', handlePaymentSubmission);
        }

        const payOnlineBtn = activePage.querySelector('#pay-online-btn');
        if (payOnlineBtn) {
            payOnlineBtn.removeEventListener('click', handleOnlinePaymentClick); 
            payOnlineBtn.addEventListener('click', handleOnlinePaymentClick);
        }

        const paymentMonthsSelect = activePage.querySelector('#payment-months');
        if (paymentMonthsSelect) {
            paymentMonthsSelect.addEventListener('change', updatePaymentButton);
        }

        // Wire Pre-Registration button to current enrollment
        const preRegBtn = activePage.querySelector('#pre-reg-btn');
        if (preRegBtn) {
            // Build URL only if we have enrollment id in the fetched data
            if (currentEnrollmentData && currentEnrollmentData.id) {
                preRegBtn.href = `generate_pre_registration.php?id=${encodeURIComponent(currentEnrollmentData.id)}`;
            } else {
                preRegBtn.removeAttribute('href');
            }
        }
    }

    function updatePaymentButton() {
        const select = document.getElementById('payment-months');
        const payButton = document.getElementById('pay-online-btn');
        const baseAmount = parseFloat(payButton.dataset.baseAmount);
        const selectedValue = select.value;

        let amountToPay = 0;
        let buttonText = 'Pay Now';

        if (selectedValue === 'full') {
            amountToPay = parseFloat(payButton.dataset.fullBalance);
            buttonText = `Pay Full Balance (₱${amountToPay.toFixed(2)})`;
        } else {
            const months = parseInt(selectedValue, 10);
            amountToPay = baseAmount * months;
            buttonText = `Pay for ${months} Month(s) (₱${(amountToPay).toFixed(2)})`;
        }
        
        payButton.dataset.amount = amountToPay.toFixed(2);
        payButton.textContent = buttonText;
    }


    function handleOnlinePaymentClick(event) {
        const button = event.currentTarget;
        const amountToPay = button.dataset.amount; 

        if (currentEnrollmentData && amountToPay) {
            handleOnlinePayment(
                currentEnrollmentData.id,
                currentEnrollmentData.student.lrn,
                amountToPay
            );
        } else {
            console.error("Enrollment data or payment amount not available.");
            alert("An error occurred. Please close the modal and try again.");
        }
    }

    async function fetchEnrollmentStatus(lrnToFind) {
        showModalWithContent('<p class="modal-message-generic modal-message-loading">Checking status...</p>');

        try {
            const response = await fetch(`api/enrollments/status.php?lrn=${encodeURIComponent(lrnToFind)}`);
            if (!response.ok) {
                 const errorData = await response.json().catch(() => ({ message: `Server error: ${response.statusText}` }));
                 throw new Error(errorData.message || 'Could not fetch status.');
            }
            const data = await response.json();
            
            if (data && data.student && data.student.lrn) {
                currentEnrollmentData = data; 
                buildAndShowModalPages(data);
            } else {
                 throw new Error(`No enrollment record found for LRN: ${lrnToFind}.`);
            }
        } catch (error) {
            console.error("Error fetching status:", error);
            showModalWithContent(`<p class="modal-message-generic modal-message-error">${error.message}</p>`);
        }
    }
    
    function buildAndShowModalPages(data) {
        const htmlPages = [];
        
        // --- Build Page 1: Enrollment & Subjects ---
        const statusText = data.status || 'N/A';
        
        let statusClass = 'status-unknown';
        switch (statusText.toLowerCase()) {
            case 'enrolled': statusClass = 'status-Approved'; break;
            case 'pending': statusClass = 'status-pending'; break;
            case 'declined': // legacy
            case 'for verification': statusClass = 'status-Declined'; break;
        }
        
        // --- Build Admin Remarks Box ---
        let adminRemarksHtml = '';
        if (data.admin_remarks && (statusText.toLowerCase() === 'pending' || statusText.toLowerCase() === 'declined' || statusText.toLowerCase() === 'for verification')) {
            adminRemarksHtml = `
                <div class="alert alert-info mt-3" role="alert" style="background-color: var(--kinderly-info-bg); border-color: var(--kinderly-info-border); color: var(--kinderly-info-text);">
                    <h5 class="alert-heading" style="font-size: 1rem; font-weight: bold; color: inherit;"><i class="bi bi-info-circle-fill"></i> Admin Note:</h5>
                    <p style="margin-bottom: 0;">${escapeHtml(data.admin_remarks)}</p>
                </div>
            `;
        }

        let subjectsHtml = '';
        let downloadButtonHtml = '';

        if (data.status.toLowerCase() === 'enrolled' && data.subjects) {
            if (data.subjects.length > 0) {
                subjectsHtml = `
                    <div class="subjects-section">
                        <h4>Your Class Schedule</h4>
                        <table class="subjects-table">
                            <thead><tr><th>Subject</th><th>Schedule</th><th>Teacher</th><th>Room</th><th>Modality</th></tr></thead>
                            <tbody>
                                ${data.subjects.map(s => `<tr>
                                    <td>${escapeHtml(s.subject_name || 'N/A')}</td>
                                    <td>${escapeHtml(s.schedule || 'TBA')}</td>
                                    <td>${escapeHtml(s.teacher_name || 'TBA')}</td>
                                    <td>${escapeHtml(s.room || 'TBA')}</td>
                                    <td>${escapeHtml(s.modality || 'TBA')}</td>
                                </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>`;
            } else {
                 subjectsHtml = `<div class="subjects-section"><p>Your class schedule is not yet available. Please check back later.</p></div>`;
            }
            downloadButtonHtml = `<div class="text-center">
                                    <a href="generate_cor.php?id=${data.id}" target="_blank" class="btn-download-cor">
                                        <i class="bi bi-download"></i> Download Certificate of Registration
                                    </a>
                                 </div>`;
        }


        // --- MODIFIED: Moved adminRemarksHtml to the top ---
        const page1Html = `
            <h3>Enrollment Status</h3>
            ${adminRemarksHtml} 
            <p><strong>LRN:</strong> ${escapeHtml(data.student.lrn)}</p>
            <p><strong>Name:</strong> ${escapeHtml(data.student.firstName || '')} ${escapeHtml(data.student.middleName || '')} ${escapeHtml(data.student.lastName || '')}</p>
            <p><strong>Status:</strong> <span class="status-pill ${statusClass}">${escapeHtml((statusText.toLowerCase() === 'declined' || statusText.toLowerCase() === 'for verification') ? 'For Verification' : statusText)}</span></p>
            <p><strong>Section:</strong> ${escapeHtml(data.section || 'N/A')}</p>
            <p><strong>School Year:</strong> ${escapeHtml(data.school_year || 'N/A')}</p>
            <p><strong>Grade Level:</strong> ${escapeHtml(data.grade_level || 'N/A')}</p>
            ${subjectsHtml}
            ${downloadButtonHtml}
        `;
        htmlPages.push(page1Html);
        // --- END MODIFICATION ---

        // --- Build Page 2: Financial & Payment ---
        const outstandingBalance = parseFloat(data.outstanding_balance || 0);
        let paymentHtml = '';
        // Pre-registration button HTML (always available)
        const preRegHtml = `
            <div class="mt-3 text-center">
                <a id="pre-reg-btn" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer" href="#">Download/Print Pre-Registration Form</a>
            </div>
        `;
        let paymentPlanHtml = '';
        let paymentWarningHtml = ''; 

        if (outstandingBalance > 0) {
            paymentWarningHtml = `
                <div class="alert alert-warning" role="alert">
                    <strong>Payment Notice:</strong> Your account has an outstanding balance of <strong>₱${outstandingBalance.toFixed(2)}</strong>. Please settle your account to ensure your enrollment is fully processed.
                </div>
            `;
            
            let amountToPay = outstandingBalance;
            let paymentButtonText = `Pay Full Balance (₱${outstandingBalance.toFixed(2)})`;
            let paymentOptionsDropdown = ''; 

            if (data.tuition_mode === 'Installment' && data.installment_months > 0) {
                const totalTuition = parseFloat(data.total_tuition);
                const monthlyPayment = totalTuition / parseInt(data.installment_months);
                const remainingMonths = Math.ceil(outstandingBalance / monthlyPayment);
                
                paymentPlanHtml = `<p><strong>Payment Plan:</strong> ${data.installment_months} months at ₱${monthlyPayment.toFixed(2)}/month.</p>`;
                
                amountToPay = Math.min(outstandingBalance, monthlyPayment);
                 if (amountToPay < 100 && outstandingBalance > 0) {
                    amountToPay = 100.00;
                }
                paymentButtonText = `Pay for 1 Month (₱${amountToPay.toFixed(2)})`;

                let optionsHtml = '';
                for (let i = 1; i <= remainingMonths; i++) {
                    if (i * monthlyPayment <= outstandingBalance) {
                        optionsHtml += `<option value="${i}">${i} Month(s) - ₱${(i * monthlyPayment).toFixed(2)}</option>`;
                    }
                }
                
                paymentOptionsDropdown = `
                    <div class="mb-3">
                        <label for="payment-months" class="form-label fw-bold">Select number of months to pay:</label>
                        <select id="payment-months" class="form-select">
                            ${optionsHtml}
                            <option value="full">Pay Full Balance - ₱${outstandingBalance.toFixed(2)}</option>
                        </select>
                    </div>
                `;

                paymentHtml = `
                    <h4>Payment Options</h4>
                    <div class="payment-grid">
                        <div class="online-payment-col">
                            <h5>Pay Online</h5>
                            <p>Securely pay with GCash, Maya, or Card.</p>
                            ${paymentOptionsDropdown}
                            <button id="pay-online-btn" class="btn btn-success btn-lg w-100" 
                                data-base-amount="${monthlyPayment.toFixed(2)}" 
                                data-full-balance="${outstandingBalance.toFixed(2)}"
                                data-amount="${amountToPay.toFixed(2)}">
                                ${paymentButtonText}
                            </button>
                        </div>
                        <div class="manual-payment-col">
                            <h5>Manual Payment</h5>
                            <ul>
                                <li><strong>On-site:</strong> Visit the school cashier.</li>
                            </ul>
                            <div id="payment-message" class="mt-3"></div>
                            ${preRegHtml}
                        </div>
                    </div>
                `;

            } else { 
                paymentHtml = `
                    <h4>Payment Options</h4>
                    <div class="payment-grid">
                        <div class="online-payment-col">
                            <h5>Pay Online</h5>
                            <p>Securely pay with GCash, Maya, or Card.</p>
                            <button id="pay-online-btn" class="btn btn-success btn-lg w-100" data-amount="${amountToPay.toFixed(2)}">
                                ${paymentButtonText}
                            </button>
                        </div>
                        <div class="manual-payment-col">
                            <h5>Manual Payment</h5>
                             <ul>
                                <li><strong>On-site:</strong> Visit the school cashier.</li>
                            </ul>
                            <div id="payment-message" class="mt-3"></div>
                            ${preRegHtml}
                        </div>
                    </div>
                `;
            }
        } else if (statusText.toLowerCase() === 'enrolled' && outstandingBalance <= 0) {
            paymentWarningHtml = `
                <div class="alert alert-success" role="alert">
                    <strong>Fully Paid:</strong> Thank you! Your account is fully settled.
                </div>
            `;
            // Even if fully paid, allow printing pre-registration
            paymentHtml = `
                <div class="payment-grid">
                    <div class="manual-payment-col" style="width:100%;">
                        <h5>Manual Delivery</h5>
                        <p>No payment is required at this time.</p>
                        ${preRegHtml}
                    </div>
                </div>
            `;
        }
        
        const page2Html = `
            <h3>Financial Status</h3>
            ${paymentWarningHtml} 
            <div class="financial-section">
                <p><strong>Tuition Mode:</strong> ${escapeHtml(data.tuition_mode || 'N/A')}</p>
                ${paymentPlanHtml}
                <p><strong>Total Tuition:</strong> ₱${parseFloat(data.total_tuition || 0).toFixed(2)}</p>
                <p><strong>Outstanding Balance:</strong> <span class="balance">₱${outstandingBalance.toFixed(2)}</span></p>
            </div>
            <div class="payment-section">
                ${paymentHtml}
            </div>
        `;
        htmlPages.push(page2Html);
        
        showModalWithPages(htmlPages);
    }
    
    async function handleOnlinePayment(enrollment_id, lrn, amount) {
        console.log("handleOnlinePayment called with:", { enrollment_id, lrn, amount });
        const payButton = document.getElementById('pay-online-btn');
        if (payButton) {
            payButton.disabled = true;
            payButton.textContent = 'Processing...';
        }
        try {
            const response = await fetch('api/payments/create_payment_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enrollment_id, lrn, amount })
            });
            const result = await response.json();
            if (result.success && result.checkout_url) {
                window.location.href = result.checkout_url;
            } else {
                throw new Error(result.message || 'Could not initiate payment session.');
            }
        } catch (error) {
            console.error('Error creating payment session:', error);
            alert(`Error: ${error.message}`);
            if (payButton) {
                payButton.disabled = false;
                // Re-call updatePaymentButton to reset the text and amount
                updatePaymentButton();
            }
        }
    }

    async function handlePaymentSubmission(event) {
        event.preventDefault();
        const form = event.target;
        const messageDiv = document.getElementById('payment-message');
        const submitButton = form.querySelector('button[type="submit"]');

        submitButton.disabled = true;
        messageDiv.textContent = 'Submitting...';
        messageDiv.className = 'alert alert-info mt-3';

        try {
            const formData = new FormData(form);
            const response = await fetch('api/payments/submit_payment.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                messageDiv.textContent = result.message;
                messageDiv.className = 'alert alert-success mt-3';
                form.reset();
            } else {
                 throw new Error(result.message || 'An error occurred.');
            }
        } catch (error) {
            console.error('Payment submission error:', error);
            messageDiv.textContent = error.message;
            messageDiv.className = 'alert alert-danger mt-3';
        } finally {
            submitButton.disabled = false;
        }
    }
    
    setupPagination();
});