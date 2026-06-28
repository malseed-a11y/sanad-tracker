jQuery(document).ready(function ($) {
    const regionSelect = $('#lp_region_id');
    const listTable    = $('#land-prices-list-table tbody');
    const form         = $('#land-prices-form');
    const i18n         = SanadTrackerLandPrices.i18n;

    regionSelect.select2({
        placeholder: i18n.select_region,
        allowClear: true,
        width: '350px'
    });

    function escHtml(str) {
        if (str === null || str === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function loadAdminList(regionId) {
        listTable.html('<tr><td colspan="5">' + i18n.loading + '</td></tr>');

        $.get(SanadTrackerLandPrices.ajax_url, {
            action: 'sanad_tracker_get_land_prices_admin_list',
            nonce: SanadTrackerLandPrices.nonce,
            region_id: regionId
        }, function (response) {
            if (response.success && response.data.entries && response.data.entries.length) {
                renderAdminList(response.data.entries);
            } else {
                listTable.html('<tr><td colspan="5">' + i18n.no_entries + '</td></tr>');
            }
        });
    }

    function renderAdminList(entries) {
        let html = '';
        entries.forEach(function (entry) {
            html += '<tr data-id="' + entry.id + '">' +
                '<td>' + entry.id + '</td>' +
                '<td class="lp-price-cell sc-price" data-original="' + escHtml(entry.shell_core_price) + '">' + entry.shell_core_price + '</td>' +
                '<td class="lp-price-cell ff-price" data-original="' + escHtml(entry.fully_finished_price) + '">' + entry.fully_finished_price + '</td>' +
                '<td>' + entry.date + '</td>' +
                '<td>' +
                    '<button type="button" class="button-secondary edit-land-price-btn">' + i18n.edit + '</button> ' +
                    '<button type="button" class="button-secondary delete-land-price-btn" data-id="' + entry.id + '">' + i18n.delete + '</button>' +
                '</td>' +
            '</tr>';
        });
        listTable.html(html);
    }

    regionSelect.on('change', function () {
        const regionId = $(this).val();
        if (regionId) {
            loadAdminList(regionId);
        } else {
            listTable.html('<tr><td colspan="5">' + i18n.select_region + '</td></tr>');
        }
    });

    form.on('submit', function (e) {
        e.preventDefault();

        const regionId = regionSelect.val();
        const date     = $('#lp_date').val();
        const shellCore = $('#lp_shell_core').val();
        const fullyFinished = $('#lp_fully_finished').val();

        if (!regionId) {
            Swal.fire({
                icon: 'warning',
                title: i18n.warning,
                text: i18n.select_region
            });
            return;
        }

        $.post(SanadTrackerLandPrices.ajax_url, {
            action: 'sanad_tracker_save_land_prices',
            nonce: SanadTrackerLandPrices.nonce,
            region_id: regionId,
            date: date,
            shell_core_price: shellCore,
            fully_finished_price: fullyFinished
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: i18n.saved,
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    loadAdminList(regionId);
                    $('#lp_shell_core').val('');
                    $('#lp_fully_finished').val('');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $(document).on('click', '.delete-land-price-btn', function () {
        const btn = $(this);
        const id  = btn.data('id');
        const row = btn.closest('tr');

        Swal.fire({
            title: i18n.confirm_delete_title,
            text: i18n.confirm_delete_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: i18n.confirm_delete_yes,
            cancelButtonText: i18n.cancel
        }).then(function (result) {
            if (result.isConfirmed) {
                $.post(SanadTrackerLandPrices.ajax_url, {
                    action: 'sanad_tracker_delete_land_price',
                    nonce: SanadTrackerLandPrices.nonce,
                    id: id
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: i18n.deleted,
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        row.remove();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: i18n.error,
                            text: response.data.message
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.edit-land-price-btn', function () {
        const row = $(this).closest('tr');

        row.find('.lp-price-cell').each(function () {
            const original = $(this).data('original');
            $(this).html('<input type="number" step="0.01" class="lp-inline-price" value="' + escHtml(original) + '">');
        });

        $(this).text(i18n.save).removeClass('edit-land-price-btn').addClass('save-land-price-btn');
        row.find('.delete-land-price-btn').text(i18n.cancel).removeClass('delete-land-price-btn').addClass('cancel-land-price-btn');
    });

    $(document).on('click', '.save-land-price-btn', function () {
        const row                 = $(this).closest('tr');
        const id                  = row.data('id');
        const shellCorePrice      = row.find('.sc-price .lp-inline-price').val();
        const fullyFinishedPrice  = row.find('.ff-price .lp-inline-price').val();

        Swal.fire({
            title: i18n.saving,
            text: i18n.loading,
            allowOutsideClick: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        $.post(SanadTrackerLandPrices.ajax_url, {
            action: 'sanad_tracker_update_land_prices',
            nonce: SanadTrackerLandPrices.nonce,
            id: id,
            shell_core_price: shellCorePrice,
            fully_finished_price: fullyFinishedPrice
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: i18n.saved,
                    timer: 1500,
                    showConfirmButton: false
                });
                const regionId = regionSelect.val();
                loadAdminList(regionId);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: i18n.error,
                    text: response.data.message
                });
            }
        });
    });

    $(document).on('click', '.cancel-land-price-btn', function () {
        const row = $(this).closest('tr');

        row.find('.lp-price-cell').each(function () {
            const original = $(this).data('original');
            $(this).text(original);
        });

        row.find('.save-land-price-btn').text(i18n.edit).removeClass('save-land-price-btn').addClass('edit-land-price-btn');
        $(this).text(i18n.delete).removeClass('cancel-land-price-btn').addClass('delete-land-price-btn');
    });
});
