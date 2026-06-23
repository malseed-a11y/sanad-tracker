jQuery(document).ready(function ($) {
    // Handle Permanently Delete
    $(document).on('click', '.delete-unassigned', function (e) {
        e.preventDefault();

        var rowId = $(this).data('unassigned-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This relation will be permanently deleted!, All linked items will be deleted as well.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(priceTrackerUnassignedAjax.ajax_url, {
                    action: 'price_tracker_delete_unassigned',
                    nonce: priceTrackerUnassignedAjax.unassigned_nonce,
                    id: rowId
                }, function (response) {
                    if (response.success) {
                        $('#unassigned-row-' + rowId).fadeOut(300, function () {
                            $(this).remove();
                        });
                        Swal.fire('Deleted!', response.data.message, 'success');
                    } else {
                        Swal.fire('Error!', response.data || 'Error occurred.', 'error');
                    }
                }).fail(function () {
                    Swal.fire('Error!', 'AJAX request failed.', 'error');
                });
            }
        });
    });

    // Handle Re-Assign
    $(document).on('click', '.reassign-unassigned', function (e) {
        e.preventDefault();

        var rowId = $(this).data('unassigned-id');

        Swal.fire({
            title: 'Re-assign relation?',
            text: "This will set the relation back to active.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, re-assign it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(priceTrackerUnassignedAjax.ajax_url, {
                    action: 'price_tracker_reassign_unassigned',
                    nonce: priceTrackerUnassignedAjax.unassigned_nonce,
                    id: rowId
                }, function (response) {
                    if (response.success) {
                        $('#unassigned-row-' + rowId).fadeOut(300, function () {
                            $(this).remove();
                        });
                        Swal.fire('Restored!', response.data.message, 'success');
                    } else {
                        Swal.fire('Error!', response.data || 'Error occurred.', 'error');
                    }
                }).fail(function () {
                    Swal.fire('Error!', 'AJAX request failed.', 'error');
                });
            }
        });
    });
});
