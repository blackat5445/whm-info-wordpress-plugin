/**
 * WHM Info Notification Settings JavaScript
 *
 * Handles all interactions for the notification settings tab,
 * including search, sorting, and pagination for recipients.
 */
(function($) {
    'use strict';

    // State variables for the table
    let currentPage = 1;
    const rowsPerPage = 25;
    let currentSort = { column: 0, direction: 'asc', type: 'number' };
    let allTableRows = [];

    $(document).ready(function() {
        const $table = $('#recipients-table');
        if (!$table.length) return;

        allTableRows = $table.find('tbody tr').toArray();
        if (allTableRows.length > 0) {
            $(allTableRows).hide();
            updateTableView();
        }

        // --- EVENT LISTENERS ---
        $('#add-new-recipient-btn').on('click', () => showAddOrEditModal());
        $table.on('click', '.modify-recipient-btn', handleModifyClick);
        $table.on('click', '.remove-recipient-btn', handleRemoveClick);
        $('#recipient-search-input').on('keyup', debounce(handleSearch, 300));
        $('#recipients-table .sortable-header').on('click', handleSortClick);
        $('#whmin-send-test-notification').on('click', handleSendTestNotification);

        // Note: The notification settings page doesn't have a "load more" button in the provided HTML.
        // If you were to add one, its handler would go here.
    });

    /**
     * Shows a SweetAlert2 modal to add or edit a recipient.
     */
    function showAddOrEditModal(recipientData = null) {
        const isEdit = recipientData !== null;
    
        const emailEnabled    = isEdit ? !!recipientData.notify_email : true;
        const telegramEnabled = isEdit ? !!recipientData.notify_telegram : false;
        const telegramChat    = isEdit && recipientData.telegram_chat ? recipientData.telegram_chat : '';
    
        Swal.fire({
            title: isEdit ? 'Modify Recipient' : 'Add New Recipient',
            width: '550px',
            customClass: { 
                popup: 'whmin-swal-modal',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false,
            html: `
                <div class="swal2-form-container">
                    <input type="hidden" id="swal-uid" value="${isEdit ? recipientData.uid : ''}">
                    <div class="swal2-input-group">
                        <label for="swal-name">Full Name</label>
                        <input id="swal-name" class="swal2-input" placeholder="John Doe" value="${isEdit ? recipientData.name : ''}">
                    </div>
                    <div class="swal2-input-group">
                        <label for="swal-email">Email Address</label>
                        <input id="swal-email" type="email" class="swal2-input" placeholder="john.doe@example.com" value="${isEdit ? recipientData.email : ''}">
                    </div>
                    <div class="swal2-input-group">
                        <label for="swal-telephone">Telephone (Optional)</label>
                        <input id="swal-telephone" type="tel" class="swal2-input" placeholder="+1 (555) 123-4567" value="${isEdit ? recipientData.telephone : ''}">
                    </div>
                    <hr class="my-3" />
                    <div class="swal2-input-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="swal-notify-email" ${emailEnabled ? 'checked' : ''}>
                            <label class="form-check-label" for="swal-notify-email">Receive email notifications</label>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="swal-notify-telegram" disabled>
                            <label class="form-check-label text-muted" for="swal-notify-telegram">
                                Telegram notifications
                                <span class="badge bg-light text-muted border ms-1">Coming soon</span>
                            </label>
                        </div>
                    </div>
                    <div class="swal2-input-group">
                        <label class="text-muted" for="swal-telegram-chat">Telegram chat ID (coming soon)</label>
                        <input id="swal-telegram-chat" class="swal2-input" placeholder="Coming soon" value="" disabled>
                    </div>
                </div>`,
            preConfirm: () => {
                const name     = document.getElementById('swal-name').value;
                const email    = document.getElementById('swal-email').value;
                const tel      = document.getElementById('swal-telephone').value;
                const notifyEmail    = document.getElementById('swal-notify-email').checked ? 1 : 0;
                const notifyTelegram = 0;
                const telegramChatId = '';
    
                if (!name || !email) {
                    Swal.showValidationMessage('Name and Email are required');
                    return false;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    Swal.showValidationMessage('Please enter a valid email address');
                    return false;
                }
                return {
                    uid: document.getElementById('swal-uid').value,
                    name: name,
                    email: email,
                    telephone: tel,
                    notify_email: notifyEmail,
                    notify_telegram: notifyTelegram,
                    telegram_chat: telegramChatId
                };
            },
            showCancelButton: true,
            confirmButtonText: '<i class="mdi mdi-content-save me-2"></i>Save',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed) {
                $.post(WHMIN_Admin.ajaxurl, {
                    action: 'whmin_save_recipient',
                    nonce: WHMIN_Admin.nonce,
                    recipient_data: result.value
                }).done(response => {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 500);
                    } else {
                        Swal.fire('Error!', response.data.message, 'error');
                    }
                }).fail(() => Swal.fire('Error!', 'An unknown error occurred.', 'error'));
            }
        });
    }

    function handleModifyClick() {
        const recipientData = $(this).closest('tr').data('recipient-data');
        showAddOrEditModal(recipientData);
    }
    
    function handleRemoveClick() {
        const row = $(this).closest('tr');
        const uid = row.attr('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This recipient will be permanently removed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove it!',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(WHMIN_Admin.ajaxurl, {
                    action: 'whmin_delete_recipient',
                    nonce: WHMIN_Admin.nonce,
                    uid: uid
                }).done(response => {
                    if(response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 500);
                    } else {
                        Swal.fire('Error!', response.data.message, 'error');
                    }
                }).fail(() => Swal.fire('Error!', 'An unknown error occurred.', 'error'));
            }
        });
    }

    function handleSendTestNotification(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Send test notification?',
            text: 'A test notification will be sent to all recipients who have Email and/or Telegram enabled.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Send now',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            Swal.showLoading();

            $.post(WHMIN_Admin.ajaxurl, {
                action: 'whmin_send_test_notification',
                nonce: WHMIN_Admin.nonce
            }).done(response => {
                Swal.close();

                if (response.success) {
                    toastr.success(response.data.message || 'Test notification sent.');
                } else {
                    const msg = response.data && response.data.message ? response.data.message : 'An error occurred while sending test notifications.';
                    Swal.fire('Error', msg, 'error');
                }
            }).fail(() => {
                Swal.close();
                Swal.fire('Error', 'An unknown error occurred while sending test notifications.', 'error');
            });
        });
    }

    // --- FULL IMPLEMENTATION OF SEARCH, SORT, and PAGINATION ---

    /**
     * Central function to update the table view based on current state.
     */
    function updateTableView() {
        // 1. Get filtered rows based on search
        const searchTerm = $('#recipient-search-input').val().toLowerCase();
        const filteredRows = searchTerm ? allTableRows.filter(row => $(row).text().toLowerCase().includes(searchTerm)) : [...allTableRows];

        // 2. Sort the filtered rows
        const { column, direction, type } = currentSort;
        const multiplier = (direction === 'asc') ? 1 : -1;
        filteredRows.sort((rowA, rowB) => {
            const cellA = $(rowA).find('td, th').eq(column);
            const cellB = $(rowB).find('td, th').eq(column);
            let valA = cellA.data('value') !== undefined ? cellA.data('value') : cellA.text().trim();
            let valB = cellB.data('value') !== undefined ? cellB.data('value') : cellB.text().trim();

            if (type === 'number') {
                return ((parseFloat(valA) || 0) - (parseFloat(valB) || 0)) * multiplier;
            } else {
                return valA.localeCompare(valB) * multiplier;
            }
        });
        
        // 3. Hide all master rows and then re-append the sorted/filtered ones to the DOM
        $(allTableRows).hide();
        $('#recipients-table tbody').empty().append(filteredRows);
        
        // 4. Show all filtered rows (since there's no pagination on this page)
        $(filteredRows).show();

        // 5. Update UI controls
        $('#no-results-message').toggle(filteredRows.length === 0 && searchTerm.length > 0);
        $('#recipients-table-container').toggle(allTableRows.length > 0);
        $('#no-recipients-placeholder').toggle(allTableRows.length === 0);
    }

    function handleSearch() {
        updateTableView();
    }

    function handleSortClick() {
        const $header = $(this);
        const columnIndex = $header.index();
        let direction = 'asc';
        
        if ($header.hasClass('asc') && currentSort.column === columnIndex) {
            direction = 'desc';
        }
        
        currentSort = { column: columnIndex, direction: direction, type: $header.data('sort') };
        
        // Update header styles
        $('#recipients-table .sortable-header').removeClass('asc desc');
        $header.addClass(direction);
        
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