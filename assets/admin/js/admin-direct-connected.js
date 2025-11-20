(function($) {
    'use strict';

    // State variables
    let currentPage = 1;
    const rowsPerPage = 25;
    let allTableRows = [];

    // Service Types
    const serviceTypes = [
        'Hosting', 'Domain', 'All in One SEO', 'Yoast SEO', 
        'Email Service', 'Cookie Yes', 'Dedicated IP', 'CDN', 'Theme', 'Other'
    ];

    // Services that require the Extra "Domain/URL" Input
    const servicesWithExtraDetail = ['Domain', 'Email Service', 'Theme', 'Dedicated IP', 'Other', 'CDN', 'All in One SEO', 'Yoast SEO', 'Cookie Yes'];

    $(document).ready(function() {
        const $table = $('#direct-sites-table');
        if (!$table.length) return;

        // Initialize Table
        allTableRows = $table.find('tbody tr').toArray();
        $(allTableRows).hide();
        updateTableView();

        // --- MAIN LISTENERS ---
        $table.on('click', '.edit-site-btn', handleEditClick);
        $table.on('click', '.renew-site-btn', handleRenewClick);
        $table.on('click', '.send-email-btn', handleEmailClick);
        $table.on('click', '.toggle-monitoring-btn', handleMonitoringToggle);
        
        $('#site-search-input').on('keyup', debounce(handleSearch, 300));
        $('#load-more-btn').on('click', function() { currentPage++; updateTableView(); });
        $('.sortable-header').on('click', handleSortClick);

        // --- DYNAMIC MODAL LISTENERS ---
        
        $(document).on('change', '.service-select', function() {
            const $row = $(this).closest('.service-row');
            const val = $(this).val();
            
            if (val === 'Other') {
                $row.find('.service-name-custom').removeClass('d-none').focus();
            } else {
                $row.find('.service-name-custom').addClass('d-none');
            }

            if (servicesWithExtraDetail.includes(val)) {
                $row.find('.service-detail-input').removeClass('d-none');
            } else {
                $row.find('.service-detail-input').addClass('d-none');
            }
        });

        $(document).on('change', '.unlimited-check', function() {
            const $dateInput = $(this).closest('.service-row').find('.expiration-date');
            $dateInput.prop('disabled', $(this).is(':checked'));
        });

        $(document).on('click', '.remove-service', function() {
            $(this).closest('.service-row').remove();
        });
        $(document).on('click', '#add-service-btn', function() {
            $('#services-container').append(renderServiceRow({}));
        });

        $(document).on('click', '.quick-date-btn', function() {
            const monthsToAdd = parseInt($(this).data('add'));
            const $input = $(this).closest('.renew-grid-row').find('.renew-date-input');
            
            const newDate = new Date();
            newDate.setMonth(newDate.getMonth() + monthsToAdd);
            
            const formatted = newDate.toISOString().split('T')[0];
            $input.val(formatted);
            
            $(this).siblings().removeClass('btn-success').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-success');
        });

        $(document).on('click', '#auto-detect-expired', function() {
            $('.email-service-check').prop('checked', false); 
            $('.email-service-check').each(function() {
                if ($(this).data('expired') === true) {
                    $(this).prop('checked', true);
                }
            });
        });
    });

    // ==========================================
    //  MODAL HANDLERS
    // ==========================================

    // --- 1. MODIFY SERVICES ---
    function handleEditClick() {
        const $btn = $(this);
        const user = $btn.data('user');
        const currentName = $btn.data('name');
        const rowData = JSON.parse($(`#site-${user}`).attr('data-json') || '{}');
        const emails = rowData.emails || { primary: '', secondary: '' };
        const services = rowData.items || [];

        Swal.fire({
            title: 'Modify Services',
            width: '900px',
            // IMPORTANT: Added 'whmin-swal-modal' to popup class to fix width issues via CSS
            customClass: {
                popup: 'whmin-swal-modal',
                confirmButton: 'swal2-styled btn-primary-custom',
                cancelButton: 'swal2-styled btn-secondary-custom'
            },
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                confirmBtn.style.backgroundColor = '#075b63';
                confirmBtn.style.boxShadow = 'none';
                confirmBtn.onmouseover = function() { this.style.backgroundColor = '#0a8a96'; };
                confirmBtn.onmouseout = function() { this.style.backgroundColor = '#075b63'; };
            },
            html: `
                <div class="container-fluid px-0 text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Friendly Name</label>
                        <input type="text" id="swal-site-name" class="form-control" value="${currentName}">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Primary Email</label>
                            <input type="email" id="swal-email-1" class="form-control" value="${emails.primary}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Secondary Email</label>
                            <input type="email" id="swal-email-2" class="form-control" value="${emails.secondary}">
                        </div>
                    </div>
                    <hr>
                    <label class="form-label mb-2 fw-bold">Service Configuration</label>
                    <div id="services-container" class="w-100" style="max-height:400px; overflow-y:auto; padding-right:5px;">
                        ${services.map(s => renderServiceRow(s)).join('')}
                    </div>
                    <button type="button" id="add-service-btn" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="mdi mdi-plus"></i> Add Service
                    </button>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            preConfirm: () => {
                const primaryEmail = $('#swal-email-1').val();
                if (!primaryEmail) { Swal.showValidationMessage('Primary email is required'); return false; }

                const items = [];
                $('.service-row').each(function() {
                    const typeVal = $(this).find('.service-select').val();
                    const customName = $(this).find('.service-name-custom').val();
                    const name = (typeVal === 'Other') ? customName : typeVal;
                    
                    items.push({
                        name: name || 'Service',
                        price: $(this).find('.service-price').val(),
                        domain_detail: $(this).find('.service-detail-input').val(),
                        start_date: $(this).find('.start-date').val(),
                        expiration_date: $(this).find('.expiration-date').val(),
                        unlimited: $(this).find('.unlimited-check').is(':checked')
                    });
                });

                return {
                    newName: $('#swal-site-name').val(),
                    emails: { primary: primaryEmail, secondary: $('#swal-email-2').val() },
                    items: items
                };
            }
        }).then((result) => {
            if (result.isConfirmed) saveServicesData(user, result.value);
        });
    }

    function renderServiceRow(data) {
        const isOther = data.name && !serviceTypes.includes(data.name) && data.name !== '';
        const selectVal = isOther ? 'Other' : (data.name || 'Hosting');
        const customNameVal = isOther ? data.name : '';
        const customDisplay = isOther ? '' : 'd-none';
        
        const hasDetail = servicesWithExtraDetail.includes(selectVal);
        const detailDisplay = hasDetail ? '' : 'd-none';
        const detailVal = data.domain_detail || '';

        const unlimitedChecked = data.unlimited ? 'checked' : '';
        const dateDisabled = data.unlimited ? 'disabled' : '';
        
        const startDate = data.start_date || new Date().toISOString().split('T')[0];
        let expDate = data.expiration_date;
        if (!expDate) {
            const d = new Date();
            d.setFullYear(d.getFullYear() + 1);
            expDate = d.toISOString().split('T')[0];
        }

        let optionsHtml = serviceTypes.map(t => `<option value="${t}" ${t === selectVal ? 'selected' : ''}>${t}</option>`).join('');

        return `
            <div class="service-row card card-body bg-light mb-2 p-3 border shadow-sm w-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex flex-column flex-grow-1 gap-2">
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm service-select" style="width:150px;">
                                ${optionsHtml}
                            </select>
                            <input type="text" class="form-control form-control-sm service-name-custom ${customDisplay}" placeholder="Enter Service Name" value="${customNameVal}">
                            <button type="button" class="btn btn-sm text-danger remove-service ms-auto"><i class="mdi mdi-delete"></i></button>
                        </div>
                        <input type="text" class="form-control form-control-sm service-detail-input ${detailDisplay}" placeholder="Enter Domain URL / Detail" value="${detailVal}">
                    </div>
                </div>
                
                <div class="row g-2 mt-1">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">€</span>
                            <input type="number" class="form-control service-price" placeholder="Price" value="${data.price || ''}">
                        </div>
                    </div>
                    <div class="col-md-4">
                         <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">Start</span>
                            <input type="date" class="form-control start-date" value="${startDate}">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">Exp</span>
                            <input type="date" class="form-control expiration-date" value="${expDate}" ${dateDisabled}>
                            <div class="input-group-text bg-white">
                                <input class="form-check-input mt-0 unlimited-check" type="checkbox" title="Unlimited" ${unlimitedChecked}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // --- 2. RENEW SERVICES ---
    function handleRenewClick() {
        const $btn = $(this);
        const user = $btn.data('user');
        const rowData = JSON.parse($(`#site-${user}`).attr('data-json') || '{}');
        const items = rowData.items || [];
        const emails = rowData.emails || {};

        if (items.length === 0) {
            Swal.fire('Info', 'No services to renew.', 'info');
            return;
        }

        const listHtml = items.map((item, index) => {
            if (item.unlimited) return ''; 
            
            const isExpired = (new Date(item.expiration_date) < new Date());
            const statusBadge = isExpired 
                ? `<span class="badge bg-danger">Exp: ${item.expiration_date}</span>` 
                : `<span class="badge bg-success">Exp: ${item.expiration_date}</span>`;

            return `
                <div class="renew-grid-row w-100" data-index="${index}">
                    <div style="flex:1;">
                        <div class="fw-bold small">${item.name}</div>
                        ${statusBadge}
                    </div>
                    <div style="flex:1;">
                        <input type="date" class="form-control form-control-sm renew-date-input" value="${item.expiration_date}">
                    </div>
                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="1">1M</button>
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="3">3M</button>
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="6">6M</button>
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="12">12M</button>
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="24">24M</button>
                        <button type="button" class="btn btn-outline-secondary quick-date-btn" data-add="36">36M</button>
                    </div>
                </div>
            `;
        }).join('');

        Swal.fire({
            title: 'Renew Services',
            width: '700px',
            // Use the custom class here too
            customClass: {
                popup: 'whmin-swal-modal',
                confirmButton: 'swal2-styled'
            },
            html: `
                <p class="text-muted small text-start mb-2">Click buttons to extend expiration from <strong>today</strong>.</p>
                <div class="border rounded bg-white text-start w-100" style="max-height: 300px; overflow-y: auto;">
                    ${listHtml}
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Expirations',
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                confirmBtn.style.backgroundColor = '#198754';
                confirmBtn.style.boxShadow = 'none';
            },
            preConfirm: () => {
                $('.renew-grid-row').each(function() {
                    const index = $(this).data('index');
                    const newDate = $(this).find('.renew-date-input').val();
                    items[index].expiration_date = newDate;
                });
                return { emails: emails, items: items };
            }
        }).then((result) => {
            if (result.isConfirmed) saveServicesData(user, result.value);
        });
    }

    // --- 3. SEND EMAIL ---
    function handleEmailClick() {
        const user = $(this).data('user');
        const rowData = JSON.parse($(`#site-${user}`).attr('data-json') || '{}');
        const items = rowData.items || [];

        if (items.length === 0) {
            Swal.fire('Info', 'No services configured.', 'info');
            return;
        }

        const listHtml = items.map((item, index) => {
            const isExpired = (!item.unlimited && new Date(item.expiration_date) < new Date());
            const style = isExpired ? 'text-danger fw-bold' : '';
            const badge = isExpired ? '<span class="badge bg-danger ms-1">Expired</span>' : '';
            const expText = item.unlimited ? 'Unlimited' : item.expiration_date;
            const priceText = item.price ? `(${item.price}€)` : '';

            return `
                <div class="form-check mb-2 border-bottom pb-2 w-100">
                    <input class="form-check-input email-service-check" type="checkbox" value="${index}" id="svc-${index}" data-expired="${isExpired}">
                    <label class="form-check-label ${style}" for="svc-${index}">
                        ${item.name} ${priceText} - Exp: ${expText} ${badge}
                    </label>
                </div>
            `;
        }).join('');

        Swal.fire({
            title: 'Send Expiration Notification',
            width: '600px',
            customClass: {
                popup: 'whmin-swal-modal', // Ensures width fixes apply
                confirmButton: 'text-dark'
            },
            html: `
                <div class="text-start mb-3 border p-3 rounded bg-white w-100" style="max-height:250px; overflow-y:auto;">
                    ${listHtml}
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" id="auto-detect-expired" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-auto-fix"></i> Auto Detect Expired
                    </button>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Send Email',
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                confirmBtn.style.backgroundColor = '#ffc107';
                confirmBtn.style.color = '#212529';
                confirmBtn.style.boxShadow = 'none';
            },
            preConfirm: () => {
                const selected = [];
                $('.email-service-check:checked').each(function() { selected.push($(this).val()); });
                if (selected.length === 0) { Swal.showValidationMessage('Select at least one service'); return false; }
                return selected;
            }
        }).then((result) => {
            if (result.isConfirmed) sendEmail(user, result.value);
        });
    }

    // --- COMMON FUNCTIONS ---

    function saveServicesData(user, data) {
        Swal.fire({ title: 'Saving...', didOpen: () => Swal.showLoading() });

        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_save_site_services',
            nonce: WHMIN_Admin.nonce,
            user: user,
            data: data
        }).done(function(response) {
            if (response.success) {
                if (data.newName) {
                    $.post(WHMIN_Admin.ajaxurl, {
                        action: 'whmin_update_site_name',
                        nonce: WHMIN_Admin.nonce,
                        user: user,
                        new_name: data.newName
                    });
                }
                Swal.fire({
                    icon: 'success', 
                    title: 'Saved!', 
                    text: 'Services updated successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', response.data.message, 'error');
            }
        }).fail(() => Swal.fire('Error', 'Connection failed', 'error'));
    }

    function sendEmail(user, selectedIndices) {
        Swal.fire({ title: 'Sending...', didOpen: () => Swal.showLoading() });
        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_send_service_email',
            nonce: WHMIN_Admin.nonce,
            user: user,
            services: selectedIndices
        }).done((res) => {
            res.success ? Swal.fire('Sent!', res.data.message, 'success') : Swal.fire('Error', res.data.message, 'error');
        });
    }

    function handleMonitoringToggle() {
        const $btn = $(this);
        const user = $btn.data('user');
        const newEnabled = !($btn.data('enabled') == 1);

        Swal.fire({
            title: newEnabled ? 'Enable Monitoring?' : 'Disable Monitoring?',
            showCancelButton: true,
            confirmButtonText: 'Yes',
        }).then((res) => {
            if(res.isConfirmed) {
                $.post(WHMIN_Admin.ajaxurl, {
                    action: 'whmin_toggle_direct_monitoring',
                    nonce: WHMIN_Admin.nonce,
                    user: user,
                    enabled: newEnabled ? 1 : 0
                }).done((r) => { if(r.success) location.reload(); });
            }
        });
    }

    function updateTableView() {
        const term = $('#site-search-input').val().toLowerCase();
        const filtered = term ? allTableRows.filter(row => $(row).text().toLowerCase().includes(term)) : [...allTableRows];
        $(allTableRows).hide();
        $('#direct-sites-table tbody').append(filtered);
        const end = currentPage * rowsPerPage;
        $(filtered).slice(0, end).show();
        $('#load-more-btn').toggle(end < filtered.length);
        $('#no-results-message').toggle(filtered.length === 0 && term);
    }

    function handleSearch() { currentPage = 1; updateTableView(); }
    function handleSortClick() { 
        const $header = $(this);
        const columnIndex = $header.index();
        let direction = 'asc';
        if ($header.hasClass('asc')) direction = 'desc';
        $('.sortable-header').removeClass('asc desc');
        $header.addClass(direction);

        const multiplier = (direction === 'asc') ? 1 : -1;
        allTableRows.sort((a, b) => {
            const valA = $(a).children('td').eq(columnIndex-1).text().trim();
            const valB = $(b).children('td').eq(columnIndex-1).text().trim();
            return valA.localeCompare(valB) * multiplier;
        });
        updateTableView();
    }
    
    function debounce(func, delay) { let t; return function(...args) { clearTimeout(t); t = setTimeout(() => func.apply(this, args), delay); }; }

})(jQuery);