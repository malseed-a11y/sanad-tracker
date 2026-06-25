jQuery(document).ready(function ($) {

    $('#add-region-form').on('submit', function (e) {
        e.preventDefault();

        const name = $('#region_name').val();

        $.post(SanadTrackerRegionsAjax.ajax_url, {
            action: 'sanad_tracker_add_region',
            nonce: SanadTrackerRegionsAjax.nonce,
            name: name
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: SanadTrackerRegionsAjax.i18n.added,
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: SanadTrackerRegionsAjax.i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $('.edit-region-btn').on('click', function (e) {
        e.preventDefault();

        const btn = $(this);
        const id = btn.data('id');
        const name = btn.data('name');

        $('#edit_region_id').val(id);
        $('#edit_region_name').val(name);

        $('#edit-region-wrapper').slideDown();
        $('html, body').animate({
            scrollTop: $('#edit-region-wrapper').offset().top - 50
        }, 300);
    });

    $('#cancel-edit-region').on('click', function () {
        $('#edit-region-wrapper').slideUp();
        $('#edit_region_id').val('');
        $('#edit_region_name').val('');
    });

    $('#edit-region-form').on('submit', function (e) {
        e.preventDefault();

        const id = $('#edit_region_id').val();
        const name = $('#edit_region_name').val();

        $.post(SanadTrackerRegionsAjax.ajax_url, {
            action: 'sanad_tracker_edit_region',
            nonce: SanadTrackerRegionsAjax.nonce,
            id: id,
            name: name
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: SanadTrackerRegionsAjax.i18n.updated,
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: SanadTrackerRegionsAjax.i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $('.delete-region-btn').on('click', function (e) {
        e.preventDefault();

        const btn = $(this);
        const id = btn.data('id');

        Swal.fire({
            title: SanadTrackerRegionsAjax.i18n.confirm_delete_title,
            text: SanadTrackerRegionsAjax.i18n.confirm_delete_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: SanadTrackerRegionsAjax.i18n.confirm_delete_yes,
            cancelButtonText: SanadTrackerRegionsAjax.i18n.cancel
        }).then(function (result) {
            if (result.isConfirmed) {
                $.post(SanadTrackerRegionsAjax.ajax_url, {
                    action: 'sanad_tracker_delete_region',
                    nonce: SanadTrackerRegionsAjax.nonce,
                    id: id
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: SanadTrackerRegionsAjax.i18n.deleted,
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: SanadTrackerRegionsAjax.i18n.error,
                            text: response.data.message
                        });
                    }
                });
            }
        });
    });
});
