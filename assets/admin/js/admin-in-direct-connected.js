/**
 * WHM Info In-direct Connected Sites JavaScript
 *
 * Handles all interactions for the in-direct sites tab.
 *
 * @package WHM_Info
 */
(function($) {
    'use strict';

    // State variables (for search, sort, pagination)
    let currentPage = 1;
    const rowsPerPage = 25;
    let currentSort = { column: 0, direction: 'asc', type: 'number' };
    let allTableRows = [];

    $(document).ready(function() {
        const $table = $('#indirect-sites-table');
        if (!$table.length) return;

        allTableRows = $table.find('tbody tr').toArray();
        if (allTableRows.length > 0) {
            $(allTableRows).hide();
            updateTableView();
        }

        // --- EVENT LISTENERS ---
        $('#add-new-indirect-site-btn').on('click', () => showAddOrEditModal());
        $table.on('click', '.modify-indirect-site-btn', handleModifyClick);
        $table.on('click', '.remove-indirect-site-btn', handleRemoveClick);
        $('#indirect-site-search-input').on('keyup', debounce(handleSearch, 300));
        $('#indirect-sites-table .sortable-header').on('click', handleSortClick);
        $('#pagination-controls #load-more-btn').on('click', handleLoadMoreClick);
    });

    /**
     * Shows a beautiful, two-column SweetAlert2 modal to add or edit a website.
     * @param {object|null} siteData - The data of the site to edit, or null to add.
     */
    function showAddOrEditModal(siteData = null) {
        const isEdit = siteData !== null;
        const hostingOptions = $('#hosting-options').html();

        Swal.fire({
            title: isEdit ? 'Modify Website Details' : 'Add New Website',
            width: '700px',
            customClass: {
                popup: 'whmin-swal-modal',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false,
            html: `
                <div class="swal2-form-grid">
                    <input type="hidden" id="swal-uid" value="${isEdit ? siteData.uid : ''}">
                    
                    <!-- Left Column -->
                    <div class="swal2-grid-item">
                        <div class="swal2-input-group">
                            <label for="swal-name">Website Name</label>
                            <input id="swal-name" class="swal2-input" placeholder="e.g., My Client's Blog" value="${isEdit ? siteData.name : ''}">
                        </div>
                        <div class="swal2-input-group">
                            <label for="swal-url">Website URL</label>
                            <input id="swal-url" type="url" class="swal2-input" placeholder="https://example.com" value="${isEdit ? siteData.url : ''}">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="swal2-grid-item">
                        <div class="swal2-input-group">
                            <label for="swal-connection">Connection Modality</label>
                            <select id="swal-connection" class="swal2-select">
                                <option value="Standard API Connection">Standard API Connection</option>
                                <option value="Without API" selected>Without API</option>
                            </select>
                        </div>
                        <div class="swal2-input-group">
                            <label for="swal-hosting">Hosting Provider</label>
                            <select id="swal-hosting" class="swal2-select">
                                ${hostingOptions}
                            </select>
                        </div>
                    </div>
                    
                    <!-- Full Width Item -->
                    <div class="swal2-grid-item full-width" id="swal-hosting-other-wrapper" style="display: none;">
                        <div class="swal2-input-group">
                            <label for="swal-hosting-other">Specify Hosting Provider</label>
                            <input id="swal-hosting-other" class="swal2-input" placeholder="e.g., My Local Server">
                        </div>
                    </div>
                </div>`,
            didOpen: () => {
                const hostingSelect = document.getElementById('swal-hosting');
                const otherWrapper = document.getElementById('swal-hosting-other-wrapper');
                const otherInput = document.getElementById('swal-hosting-other');

                const toggleOtherInput = () => {
                    otherWrapper.style.display = (hostingSelect.value === 'other') ? 'block' : 'none';
                };

                if (isEdit) {
                    // Pre-select the hosting provider
                    if (hostingSelect.querySelector(`option[value="${siteData.hosting}"]`)) {
                        hostingSelect.value = siteData.hosting;
                    } else {
                        hostingSelect.value = 'other';
                        otherInput.value = siteData.hosting;
                    }
                }
                toggleOtherInput();
                hostingSelect.addEventListener('change', toggleOtherInput);
            },
            preConfirm: () => {
                const hostingVal = document.getElementById('swal-hosting').value;
                const hosting = (hostingVal === 'other') ? document.getElementById('swal-hosting-other').value : hostingVal;
                
                // Validation
                const name = document.getElementById('swal-name').value;
                const url = document.getElementById('swal-url').value;
                if (!name || !url) {
                    Swal.showValidationMessage('Website Name and URL are required');
                    return false;
                }

                return {
                    uid: document.getElementById('swal-uid').value,
                    name: name,
                    url: url,
                    connection: document.getElementById('swal-connection').value,
                    hosting: hosting
                };
            },
            showCancelButton: true,
            confirmButtonText: '<i class="mdi mdi-content-save me-2"></i>Save',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed) {
                $.post(WHMIN_Admin.ajaxurl, {
                    action: 'whmin_save_indirect_site',
                    nonce: WHMIN_Admin.nonce,
                    site_data: result.value
                }).done(response => {
                    if (response.success) {
                        toastr.success(response.message);
                        // --- CHANGE: Reload the page on success ---
                        setTimeout(() => location.reload(), 500);
                    } else {
                        Swal.fire('Error!', response.data.message, 'error');
                    }
                }).fail(() => Swal.fire('Error!', 'An unknown error occurred.', 'error'));
            }
        });
    }

    function handleModifyClick() {
        const siteData = $(this).closest('tr').data('site-data');
        showAddOrEditModal(siteData);
    }
    
    function handleRemoveClick() {
        const row = $(this).closest('tr');
        const uid = row.attr('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This website will be permanently removed from this list.",
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
                    action: 'whmin_delete_indirect_site',
                    nonce: WHMIN_Admin.nonce,
                    uid: uid
                }).done(response => {
                    if(response.success) {
                        toastr.success(response.message);
                        // --- CHANGE: Reload the page on success ---
                        setTimeout(() => location.reload(), 500);
                    } else {
                        Swal.fire('Error!', response.data.message, 'error');
                    }
                }).fail(() => Swal.fire('Error!', 'An unknown error occurred.', 'error'));
            }
        });
    }

    // --- Search, Sort, and Paginate functions (remain unchanged) ---
    function updateTableView() {
        const searchTerm = $('#indirect-site-search-input').val().toLowerCase();
        const filteredRows = searchTerm ? allTableRows.filter(row => $(row).text().toLowerCase().includes(searchTerm)) : [...allTableRows];
        const { column, direction, type } = currentSort;
        const multiplier = (direction === 'asc') ? 1 : -1;
        filteredRows.sort((rowA, rowB) => {
            const cellA = $(rowA).find('td, th').eq(column);
            const cellB = $(rowB).find('td, th').eq(column);
            let valA = cellA.data('value') !== undefined ? cellA.data('value') : cellA.text().trim();
            let valB = cellB.data('value') !== undefined ? cellB.data('value') : cellB.text().trim();
            if (type === 'number') { return ( (parseFloat(valA) || 0) - (parseFloat(valB) || 0) ) * multiplier; } 
            else { return valA.localeCompare(valB) * multiplier; }
        });
        
        $(allTableRows).hide();
        $('#indirect-sites-table tbody').empty().append(filteredRows);
        
        const end = currentPage * rowsPerPage;
        $(filteredRows).slice(0, end).show();
        
        $('#pagination-controls #load-more-btn').toggle(end < filteredRows.length);
        $('#no-results-message').toggle(filteredRows.length === 0 && searchTerm.length > 0);
        $('#pagination-controls').toggle(filteredRows.length > 0 && !searchTerm);
    }
    function handleSearch() { currentPage = 1; updateTableView(); }
    function handleSortClick() {
        const $header = $(this);
        const columnIndex = $header.index();
        let direction = 'asc';
        if ($header.hasClass('asc') && currentSort.column === columnIndex) { direction = 'desc'; }
        currentSort = { column: columnIndex, direction: direction, type: $header.data('sort') };
        $('.sortable-header').removeClass('asc desc');
        $header.addClass(direction);
        currentPage = 1;
        updateTableView();
    }
    function handleLoadMoreClick() { currentPage++; updateTableView(); }
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

})(jQuery);