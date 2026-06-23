jQuery(document).ready(function ($) {
    // === ADD ITEM ===
    // $('.add-item-form').on('submit', function (e) {
    //     e.preventDefault();

    //     $.post(priceTrackerItemAjax.ajax_url, {
    //         action: 'pt_add_item',
    //         security: priceTrackerItemAjax.item_nonce,
    //         taxonomy_category_id: $('#taxonomy_category_id').val(),
    //         buy_price: $('#buy_price').val(),
    //         sell_price: $('#sell_price').val(),
    //         price_date: $('#price_date').val()
    //     }, function (response) {
    //         if (response.success) {
    //             alert(response.data.message);
    //             location.reload();
    //         } else {
    //             alert(response.data.message);
    //         }
    //     });
    // });

    // Handle Add per row
    $(document).on('click', '.add-item-single', function (e) {
        e.preventDefault();

        const row = $(this).closest('.item-row');
        const taxonomyCategoryId = row.find('.taxonomy-category-id').val();
        const buyPrice = row.find('.buy-price').val();
        const sellPrice = row.find('.sell-price').val();
        const priceDate = row.find('.price-date').val();

        if (!buyPrice || !sellPrice) {
            Swal.fire('Missing Data', 'Please enter both Buy and Sell prices.', 'warning');
            return;
        }

        $.post(priceTrackerItemAjax.ajax_url, {
            action: 'pt_add_item',
            security: priceTrackerItemAjax.item_nonce,
            taxonomy_category_id: taxonomyCategoryId,
            buy_price: buyPrice,
            sell_price: sellPrice,
            price_date: priceDate
        }, function (response) {
            if (response.success) {
                // Swal.fire('Added!', response.data.message, 'success');
                row.find('.label-success').remove();
                const label = $('<label class="label-success">' + response.data.message + '</l>');
                row.append(label);
                // Optionally reset inputs
                row.find('.buy-price').val('');
                row.find('.sell-price').val('');
            } else {
                Swal.fire('Error', response.data.message || 'Failed to add item.', 'error');
            }
        }).fail(function () {
            Swal.fire('Error', 'AJAX request failed.', 'error');
        });
    });

    // === DELETE ITEM ===
    $('.delete-item').on('click', function (e) {
        e.preventDefault();

        let id = $(this).data('item-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(priceTrackerItemAjax.ajax_url, {
                    action: 'pt_delete_item',
                    security: priceTrackerItemAjax.item_nonce,
                    item_id: id
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


    // === EDIT ITEM (open form with values) ===
    $('.edit-item').on('click', function () {
        $('#edit_item_id').val($(this).data('item-id'));

        $('#edit_buy_price').val($(this).data('buy-price'));
        $('#edit_sell_price').val($(this).data('sell-price'));
        $('#edit_price_date').val($(this).data('date'));



        $('#edit-item-wrapper').show();
    });

    // Cancel edit
    $('#cancel-edit-item').on('click', function () {
        $('#edit-item-wrapper').hide();
    });

    // === SUBMIT EDIT FORM ===
    $('.edit-item-form').on('submit', function (e) {
        e.preventDefault();

        $.post(priceTrackerItemAjax.ajax_url, {
            action: 'pt_edit_item',
            security: priceTrackerItemAjax.item_nonce,
            item_id: $('#edit_item_id').val(),
            taxonomy_category_id: $('#edit_taxonomy_category_id').val(),
            buy_price: $('#edit_buy_price').val(),
            sell_price: $('#edit_sell_price').val(),
            price_date: $('#edit_price_date').val()
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
