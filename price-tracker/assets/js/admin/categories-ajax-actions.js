jQuery(document).ready(function ($) {
    // Add category
    $('.add-category-from').on('submit', function (e) {
        e.preventDefault();
        let name = $('#category_name').val();
        let taxonomies = $('#category_taxonomies').val();

        $.post(priceTrackerCategoryAjax.ajax_url, {
            action: 'pt_add_category',
            security: priceTrackerCategoryAjax.category_nonce,
            name: name,
            taxonomies: taxonomies
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // === DELETE CATEGORY ===
    $('.delete-category').on('click', function (e) {
        e.preventDefault();

        let id = $(this).data('category-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This category will be permanently deleted and its items will be deleted as well.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(priceTrackerCategoryAjax.ajax_url, {
                    action: 'pt_delete_category',
                    security: priceTrackerCategoryAjax.category_nonce,
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


    // Submit edit form
    $('.edit-category-form').on('submit', function (e) {
        e.preventDefault();

        let id = $('#edit_category_id').val();
        let name = $('#edit_category_name').val();
        let taxonomies = $('#edit_category_taxonomies').val();

        $.post(priceTrackerCategoryAjax.ajax_url, {
            action: 'pt_edit_category',
            security: priceTrackerCategoryAjax.category_nonce,
            id: id,
            name: name,
            taxonomies: taxonomies
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // or update the row dynamically
            } else {
                alert(response.data.message);
            }
        });
    });
});