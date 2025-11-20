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

    // --- 2. RENEW SERVICES (NOW WITH EMAIL) ---
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
            confirmButtonText: 'Renew & Send Email',
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                confirmBtn.style.backgroundColor = '#198754';
                confirmBtn.style.boxShadow = 'none';
            },
            preConfirm: () => {
                const renewedServices = [];
                $('.renew-grid-row').each(function() {
                    const index = $(this).data('index');
                    const newDate = $(this).find('.renew-date-input').val();
                    if (items[index].expiration_date !== newDate) {
                        renewedServices.push({
                            index: index,
                            new_expiration: newDate
                        });
                    }
                });
                
                if (renewedServices.length === 0) {
                    Swal.showValidationMessage('No changes detected');
                    return false;
                }
                
                return { renewed_services: renewedServices };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                renewServices(user, result.value.renewed_services);
            }
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
            width: '500px',
            customClass: { 
                popup: 'whmin-swal-modal',
                confirmButton: 'swal2-styled'
            },
            html: `
                <div class="text-start">
                    <p class="text-muted small">Select services to include in the expiration email.</p>
                    <button type="button" id="auto-detect-expired" class="btn btn-sm btn-outline-danger mb-2">
                        <i class="mdi mdi-auto-fix"></i> Auto-select Expired
                    </button>
                    <div class="border rounded p-2 bg-light" style="max-height: 250px; overflow-y: auto;">
                        ${listHtml}
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Send Email',
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                confirmBtn.style.backgroundColor = '#fd7e14';
            },
            preConfirm: () => {
                const selectedIndices = [];
                $('.email-service-check:checked').each(function() {
                    selectedIndices.push(parseInt($(this).val()));
                });
                if (selectedIndices.length === 0) {
                    Swal.showValidationMessage('Please select at least one service');
                    return false;
                }
                return selectedIndices;
            }
        }).then((result) => {
            if (result.isConfirmed) sendServiceEmail(user, result.value);
        });
    }

    // --- 4. TOGGLE MONITORING ---
    function handleMonitoringToggle() {
        const $btn = $(this);
        const user = $btn.data('user');
        const currentlyEnabled = $btn.data('enabled') == 1;
        const newState = !currentlyEnabled;

        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_toggle_direct_monitoring',
            nonce: WHMIN_Admin.nonce,
            user: user,
            enabled: newState
        }).done(response => {
            if (response.success) {
                $btn.data('enabled', newState ? 1 : 0);
                $btn.toggleClass('btn-secondary btn-outline-secondary');
                const icon = $btn.find('i');
                icon.toggleClass('mdi-eye mdi-eye-off');
                $btn.attr('title', newState ? 'Monitoring Active - Click to Disable' : 'Monitoring Disabled - Click to Enable');
                toastr.success(response.data.message);
            } else {
                toastr.error(response.data.message);
            }
        }).fail(() => toastr.error('Network error'));
    }

    // ==========================================
    //  AJAX FUNCTIONS
    // ==========================================

    function saveServicesData(user, data) {
        Swal.showLoading();
        
        // Also update site name
        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_update_site_name',
            nonce: WHMIN_Admin.nonce,
            user: user,
            new_name: data.newName
        });
        
        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_save_site_services',
            nonce: WHMIN_Admin.nonce,
            user: user,
            data: data
        }).done(response => {
            Swal.close();
            if (response.success) {
                toastr.success(response.data.message);
                setTimeout(() => location.reload(), 500);
            } else {
                Swal.fire('Error', response.data.message, 'error');
            }
        }).fail(() => Swal.fire('Error', 'Network error', 'error'));
    }

    function renewServices(user, renewedServices) {
        Swal.showLoading();
        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_renew_site_services',
            nonce: WHMIN_Admin.nonce,
            user: user,
            renewed_services: renewedServices
        }).done(response => {
            Swal.close();
            if (response.success) {
                Swal.fire('Success!', response.data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire('Error', response.data.message, 'error');
            }
        }).fail(() => Swal.fire('Error', 'Network error', 'error'));
    }

    function sendServiceEmail(user, serviceIndices) {
        Swal.showLoading();
        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_send_service_email',
            nonce: WHMIN_Admin.nonce,
            user: user,
            services: serviceIndices
        }).done(response => {
            Swal.close();
            if (response.success) {
                Swal.fire('Sent!', response.data.message, 'success');
            } else {
                Swal.fire('Error', response.data.message, 'error');
            }
        }).fail(() => Swal.fire('Error', 'Network error', 'error'));
    }

    // ==========================================
    //  TABLE MANAGEMENT
    // ==========================================

    function updateTableView() {
        const searchTerm = $('#site-search-input').val().toLowerCase();
        const filteredRows = searchTerm 
            ? allTableRows.filter(row => $(row).text().toLowerCase().includes(searchTerm))
            : [...allTableRows];

        const startIndex = 0;
        const endIndex = currentPage * rowsPerPage;
        const visibleRows = filteredRows.slice(startIndex, endIndex);

        $(allTableRows).hide();
        $(visibleRows).show();

        $('#load-more-btn').toggle(filteredRows.length > endIndex);
        $('#no-results-message').toggle(filteredRows.length === 0 && searchTerm.length > 0);
    }

    function handleSearch() {
        currentPage = 1;
        updateTableView();
    }

    function handleSortClick() {
        const $header = $(this);
        const columnIndex = $header.index();
        const direction = $header.hasClass('asc') ? 'desc' : 'asc';
        const type = $header.data('sort');

        $('.sortable-header').removeClass('asc desc');
        $header.addClass(direction);

        allTableRows.sort((rowA, rowB) => {
            const cellA = $(rowA).find('td, th').eq(columnIndex);
            const cellB = $(rowB).find('td, th').eq(columnIndex);
            let valA = cellA.data('value') !== undefined ? cellA.data('value') : cellA.text().trim();
            let valB = cellB.data('value') !== undefined ? cellB.data('value') : cellB.text().trim();

            if (type === 'number') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
                return direction === 'asc' ? valA - valB : valB - valA;
            } else {
                return direction === 'asc' 
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA);
            }
        });

        currentPage = 1;
        updateTableView();
    }

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

})(jQuery);