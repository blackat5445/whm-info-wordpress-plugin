/**
 * WHM Info Direct Connected Sites JavaScript
 *
 * Handles interactions on the Direct Connected Websites settings tab,
 * including search, sorting, and pagination.
 *
 * @package WHM_Info
 */
(function($) {
    'use strict';

    // State variables
    let currentPage = 1;
    const rowsPerPage = 25;
    let currentSort = { column: 0, direction: 'asc', type: 'number' };
    let allTableRows = [];

    $(document).ready(function() {
        const $table = $('#direct-sites-table');
        if (!$table.length) return;

        // Store all rows for manipulation and hide them initially
        allTableRows = $table.find('tbody tr').toArray();
        $(allTableRows).hide();

        // Initial setup
        updateTableView();
        
        // --- EVENT LISTENERS ---
        $table.on('click', '.edit-site-name-btn', handleEditButtonClick);
        $('#site-search-input').on('keyup', debounce(handleSearch, 300));
        $('.sortable-header').on('click', handleSortClick);
        $('#load-more-btn').on('click', handleLoadMoreClick);
    });

    /**
     * Central function to update the entire table view based on current state.
     * It handles sorting, filtering, and pagination.
     */
    function updateTableView() {
        // 1. Get filtered rows based on search
        const searchTerm = $('#site-search-input').val().toLowerCase();
        const filteredRows = searchTerm ? allTableRows.filter(row => $(row).text().toLowerCase().includes(searchTerm)) : [...allTableRows];

        // 2. Sort the filtered rows
        const { column, direction, type } = currentSort;
        const multiplier = (direction === 'asc') ? 1 : -1;
        filteredRows.sort((rowA, rowB) => {
            const cellA = $(rowA).find('td, th').eq(column);
            const cellB = $(rowB).find('td, th').eq(column);
            let valA = cellA.data('value') !== undefined ? cellA.data('value') : cellA.text().trim();
            let valB = cellB.data('value') !== undefined ? cellB.data('value') : cellB.text().trim();

            if (type === 'number' || type === 'date') {
                return ( (parseFloat(valA) || 0) - (parseFloat(valB) || 0) ) * multiplier;
            } else {
                return valA.localeCompare(valB) * multiplier;
            }
        });
        
        // 3. Hide all master rows and then re-append the sorted/filtered ones to the DOM
        // This ensures the DOM order matches the sorted order.
        $(allTableRows).hide();
        $('#direct-sites-table tbody').append(filteredRows);
        
        // 4. Apply pagination to the visible (filtered) set
        const end = currentPage * rowsPerPage;
        $(filteredRows).slice(0, end).show();

        // 5. Update UI controls
        $('#load-more-btn').toggle(end < filteredRows.length);
        $('#no-results-message').toggle(filteredRows.length === 0 && searchTerm);
        $('#pagination-controls').toggle(filteredRows.length > 0);
    }

    function handleSearch() {
        currentPage = 1; // Reset to first page on new search
        updateTableView();
    }

    function handleSortClick() {
        const $header = $(this);
        const columnIndex = $header.index();
        let direction = 'asc';
        
        // If clicking the same column, toggle direction
        if ($header.hasClass('asc') && currentSort.column === columnIndex) {
            direction = 'desc';
        }
        
        currentSort = { column: columnIndex, direction: direction, type: $header.data('sort') };
        
        $('.sortable-header').removeClass('asc desc');
        $header.addClass(direction);
        
        currentPage = 1; // Reset to first page on new sort
        updateTableView();
    }
    
    function handleLoadMoreClick() {
        currentPage++;
        updateTableView();
    }

    function handleEditButtonClick() {
        const button = $(this);
        showEditNameModal(button.data('user'), button.data('current-name'));
    }

    function showEditNameModal(user, currentName) {
        Swal.fire({
            title: 'Edit Website Name',
            input: 'text',
            inputValue: currentName,
            inputPlaceholder: 'Enter a friendly name',
            showCancelButton: true,
            confirmButtonText: 'Save Name',
            showLoaderOnConfirm: true,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false,
            preConfirm: (newName) => {
                if (!newName || newName === currentName) return false;
                return $.post(WHMIN_Admin.ajaxurl, {
                    action: 'whmin_update_site_name',
                    nonce: WHMIN_Admin.nonce,
                    user: user,
                    new_name: newName
                }).fail(() => Swal.showValidationMessage('Request failed. Please try again.'));
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value.success) {
                const newName = result.value.data.newName;
                const $row = $(`#site-${user}`);
                $row.find('.site-name').text(newName);
                $row.find('.edit-site-name-btn').data('current-name', newName);

                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: result.value.data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else if (result.value && !result.value.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.value.data.message || 'Could not save the name.',
                });
            }
        });
    }

    /**
     * Debounce function to limit how often a function can run.
     */
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

})(jQuery);