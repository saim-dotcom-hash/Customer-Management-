/**
 * Customer & Balance Management System Client Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('wrapper');
    if (menuToggle && wrapper) {
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
        });
    }

    // 2. Real-time Balance Calculation in Entry Forms
    const priceInput = document.getElementById('price');
    const advancedInput = document.getElementById('advanced');
    const balanceInput = document.getElementById('balance');
    const balanceWarning = document.getElementById('balance-warning');

    function calculateRealtimeBalance() {
        if (!priceInput || !advancedInput || !balanceInput) return;

        const price = parseFloat(priceInput.value) || 0;
        const advanced = parseFloat(advancedInput.value) || 0;

        if (advanced > price) {
            if (balanceWarning) {
                balanceWarning.classList.remove('d-none');
                balanceWarning.textContent = 'Advanced payment cannot be greater than Price!';
            }
        } else {
            if (balanceWarning) {
                balanceWarning.classList.add('d-none');
            }
        }

        const balance = Math.max(0, price - advanced);
        balanceInput.value = balance.toFixed(2);
    }

    if (priceInput && advancedInput) {
        priceInput.addEventListener('input', calculateRealtimeBalance);
        advancedInput.addEventListener('input', calculateRealtimeBalance);
        // Initial run on page load
        calculateRealtimeBalance();
    }

    // 3. Mobile Number Check (Auto-detect Existing Customer via AJAX)
    const mobileInput = document.getElementById('mobile');
    const customerNameInput = document.getElementById('name');
    const existingCustomerAlert = document.getElementById('existing-customer-alert');

    if (mobileInput && existingCustomerAlert) {
        mobileInput.addEventListener('blur', function () {
            const mobile = this.value.trim();
            if (mobile.length >= 10) {
                fetch(`../api/ajax.php?action=check_customer&mobile=${encodeURIComponent(mobile)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            existingCustomerAlert.classList.remove('d-none');
                            existingCustomerAlert.innerHTML = `<i class="bi bi-info-circle-fill me-2"></i> Existing customer found: <strong>${data.name}</strong>. New entry will be attached to this profile.`;
                            if (customerNameInput && (!customerNameInput.value || customerNameInput.value !== data.name)) {
                                customerNameInput.value = data.name;
                            }
                        } else {
                            existingCustomerAlert.classList.add('d-none');
                        }
                    })
                    .catch(err => console.error('Error checking customer mobile:', err));
            } else {
                existingCustomerAlert.classList.add('d-none');
            }
        });
    }

    // 4. Form Validation before Submit
    const entryForm = document.getElementById('customer-entry-form');
    if (entryForm) {
        entryForm.addEventListener('submit', function (e) {
            const price = parseFloat(priceInput.value) || 0;
            const advanced = parseFloat(advancedInput.value) || 0;

            if (price < 0) {
                e.preventDefault();
                alert('Price must be a valid positive number.');
                priceInput.focus();
                return false;
            }

            if (advanced < 0) {
                e.preventDefault();
                alert('Advanced payment must be a valid non-negative number.');
                advancedInput.focus();
                return false;
            }

            if (advanced > price) {
                e.preventDefault();
                alert('Advanced payment cannot be greater than Price!');
                advancedInput.focus();
                return false;
            }
        });
    }

    // 5. Payment Modal Calculation
    const payAmountInput = document.getElementById('pay_amount');
    const payEntryPrice = document.getElementById('pay_entry_price');
    const payCurrentAdvanced = document.getElementById('pay_current_advanced');
    const payNewBalance = document.getElementById('pay_new_balance');

    if (payAmountInput && payEntryPrice && payCurrentAdvanced && payNewBalance) {
        payAmountInput.addEventListener('input', function () {
            const price = parseFloat(payEntryPrice.dataset.price) || 0;
            const currentAdv = parseFloat(payCurrentAdvanced.dataset.advanced) || 0;
            const addPay = parseFloat(this.value) || 0;

            const totalAdv = currentAdv + addPay;
            const remBal = Math.max(0, price - totalAdv);

            if (totalAdv > price) {
                payNewBalance.className = 'text-danger font-monospace fw-bold';
                payNewBalance.textContent = 'Invalid: Total advance exceeds price!';
            } else {
                payNewBalance.className = 'text-success font-monospace fw-bold';
                payNewBalance.textContent = '₹' + remBal.toFixed(2);
            }
        });
    }
});

/**
 * Global Print Handler
 */
function printWindow() {
    window.print();
}
