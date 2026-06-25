jQuery(document).ready(function ($) {

    const addForm = $('#add-material-form');
    const editWrapper = $('#edit-material-wrapper');
    const editForm = $('#edit-material-form');

    addForm.on('submit', function (e) {
        e.preventDefault();

        const name = $('#material_name').val();

        $.post(SanadTrackerMaterialsAjax.ajax_url, {
            action: 'sanad_tracker_add_material',
            nonce: SanadTrackerMaterialsAjax.nonce,
            name: name
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: SanadTrackerMaterialsAjax.i18n.added,
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: SanadTrackerMaterialsAjax.i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $(document).on('click', '.edit-material-btn', function (e) {
        e.preventDefault();

        const btn = $(this);
        const id = btn.data('id');
        const name = btn.data('name');

        $('#edit_material_id').val(id);
        $('#edit_material_name').val(name);

        editWrapper.slideDown();
        $('html, body').animate({
            scrollTop: editWrapper.offset().top - 50
        }, 300);
    });

    $('#cancel-edit-material').on('click', function () {
        editWrapper.slideUp();
        $('#edit_material_id').val('');
        $('#edit_material_name').val('');
    });

    editForm.on('submit', function (e) {
        e.preventDefault();

        const id = $('#edit_material_id').val();
        const name = $('#edit_material_name').val();

        $.post(SanadTrackerMaterialsAjax.ajax_url, {
            action: 'sanad_tracker_edit_material',
            nonce: SanadTrackerMaterialsAjax.nonce,
            id: id,
            name: name
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: SanadTrackerMaterialsAjax.i18n.updated,
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: SanadTrackerMaterialsAjax.i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $(document).on('click', '.delete-material-btn', function (e) {
        e.preventDefault();

        const btn = $(this);
        const id = btn.data('id');

        Swal.fire({
            title: SanadTrackerMaterialsAjax.i18n.confirm_delete_title,
            text: SanadTrackerMaterialsAjax.i18n.confirm_delete_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: SanadTrackerMaterialsAjax.i18n.confirm_delete_yes,
            cancelButtonText: SanadTrackerMaterialsAjax.i18n.cancel
        }).then(function (result) {
            if (result.isConfirmed) {
                $.post(SanadTrackerMaterialsAjax.ajax_url, {
                    action: 'sanad_tracker_delete_material',
                    nonce: SanadTrackerMaterialsAjax.nonce,
                    id: id
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: SanadTrackerMaterialsAjax.i18n.deleted,
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: SanadTrackerMaterialsAjax.i18n.error,
                            text: response.data.message
                        });
                    }
                });
            }
        });
    });
});
