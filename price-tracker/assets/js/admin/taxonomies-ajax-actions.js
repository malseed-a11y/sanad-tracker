jQuery(document).ready(function ($) {
    // Add taxonomy
    $('.add-taxonomy-form').on('submit', function (e) {
        e.preventDefault();
        let name = $('#taxonomy_name').val();
        let categories = $('#taxonomy_categories').val();

        $.post(priceTrackerTaxonomyAjax.ajax_url, {
            action: 'pt_add_taxonomy',
            security: priceTrackerTaxonomyAjax.taxonomy_nonce,
            name: name,
            categories: categories
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // === DELETE TAXONOMY ===
    $('.delete-taxonomy').on('click', function (e) {
        e.preventDefault();

        let id = $(this).data('taxonomy-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This taxonomy will be permanently deleted and its items will be deleted as well.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(priceTrackerTaxonomyAjax.ajax_url, {
                    action: 'pt_delete_taxonomy',
                    security: priceTrackerTaxonomyAjax.taxonomy_nonce,
                    id: id
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message
                        });
                    }
                });
            }
        });
    });


    // Trigger edit form
    $('.edit-taxonomy-trigger').on('click', function (e) {
        e.preventDefault();
        let btn = $(this);
        let id = btn.data('id');
        let name = btn.data('name');
        let categories = btn.data('categories').toString().split(',');

        // Fill the hidden edit form
        $('#edit_taxonomy_id').val(id);
        $('#edit_taxonomy_name').val(name);

        // Reset taxonomy select
        $('#edit_taxonomy_categories option').prop('selected', false);

        // Select assigned taxonomies
        $('#edit_taxonomy_categories option').each(function () {
            if (categories.includes($(this).val())) {
                $(this).prop('selected', true);
            }
        });

        // Show the form
        $('.edit-taxonomy-form').slideDown();
        initSelect2($('.support-select2'));
    });

    // Cancel edit
    $('#cancel-edit-taxonomy').on('click', function () {
        $('.edit-taxonomy-form').slideUp();
    });

    // Submit edit form
    $('.edit-taxonomy-form').on('submit', function (e) {
        e.preventDefault();

        let id = $('#edit_taxonomy_id').val();
        let name = $('#edit_taxonomy_name').val();
        let categories = $('#edit_taxonomy_categories').val();

        $.post(priceTrackerTaxonomyAjax.ajax_url, {
            action: 'pt_edit_taxonomy',
            security: priceTrackerTaxonomyAjax.taxonomy_nonce,
            id: id,
            name: name,
            categories: categories
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });
});
