jQuery(document).ready(function ($) {

    const regionSelect   = $('#mp_region_id');
    const rowsWrapper    = $('#material-prices-rows-wrapper');
    const listBody       = $('#sanad-material-prices-recent-tbody');
    const listHeader     = $('#material-prices-matrix-header');
    const listTableWrap  = $('#material-prices-list-table');
    const placeholder    = $('#sanad-matrix-placeholder');
    const form           = $('#material-prices-form');
    const i18n           = SanadTrackerMaterialPrices.i18n;
    let materials        = [];

    function escHtml(str) {
        if (str === null || str === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function toMaterialId(key) {
        return String(key);
    }

    regionSelect.select2({
        placeholder: i18n.select_region,
        allowClear: true,
        width: '350px'
    });

    function renderTableHeaders() {
        let html = '<div class="matrix-cell col-date">' + i18n.date + '</div>';
        const colWidths = materials.map(function () { return '1fr'; }).join(' ');
        $.each(materials, function (i, mat) {
            html += '<div class="matrix-cell col-material">' + escHtml(mat.name) + '</div>';
        });
        html += '<div class="matrix-cell col-actions">' + i18n.actions + '</div>';
        listHeader.html(html);
        listTableWrap.css('--grid-cols', '120px ' + colWidths + ' 130px');
    }

    function loadMaterials(regionId) {
        if (!regionId) {
            rowsWrapper.html('<p class="description">' + i18n.select_region + '</p>');
            listTableWrap.hide();
            placeholder.show();
            return;
        }
        listTableWrap.show();
        placeholder.hide();

        rowsWrapper.html('<p class="description">' + i18n.loading + '</p>');

        $.get(SanadTrackerMaterialPrices.ajax_url, {
            action: 'sanad_tracker_get_materials',
            nonce:  SanadTrackerMaterialPrices.materials_nonce,
            region_id: regionId
        }).done(function (response) {
            if (response.success && response.data.materials && response.data.materials.length) {
                materials = response.data.materials;
                renderMaterialRows(materials);
                renderTableHeaders();
                loadAdminList(regionId);
            } else {
                rowsWrapper.html('<p class="description">' + i18n.no_materials + '</p>');
                listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">' + i18n.no_materials + '</div></div>');
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.warn('sanad_tracker_get_materials failed:', textStatus, errorThrown);
            rowsWrapper.html('<p class="description" style="color:red;">Error: ' + textStatus + '</p>');
            listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">Request failed: ' + errorThrown + '</div></div>');
        });
    }

    function renderMaterialRows(materials) {
        let html = '<table class="form-table">';
        $.each(materials, function (i, mat) {
            html += '<tr class="material-price-row">' +
                '<th scope="row">' + escHtml(mat.name) + '</th>' +
                '<td>' +
                    '<input type="hidden" class="mp-material-id" value="' + toMaterialId(mat.id) + '">' +
                    '<input type="number" step="0.01" class="mp-material-price regular-text" placeholder="' + i18n.price_placeholder + '">' +
                '</td>' +
            '</tr>';
        });
        html += '</table>';
        rowsWrapper.html(html);
    }

    function loadAdminList(regionId) {
        listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">' + i18n.loading + '</div></div>');

        $.get(SanadTrackerMaterialPrices.ajax_url, {
            action: 'sanad_tracker_get_material_prices_admin_list',
            nonce: SanadTrackerMaterialPrices.nonce,
            region_id: regionId
        }).done(function (response) {
            if (response.success && response.data.matrix) {
                if (response.data.materials) {
                    materials = response.data.materials;
                    renderTableHeaders();
                }
                renderAdminList(response.data.matrix);
            } else {
                listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">' + i18n.no_entries + '</div></div>');
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.warn('sanad_tracker_get_material_prices_admin_list failed:', textStatus, errorThrown);
            listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">Request failed: ' + errorThrown + '</div></div>');
        });
    }

    function renderAdminList(matrix) {
        if (!matrix || !matrix.length || !materials.length) {
            listBody.html('<div class="matrix-data-row"><div class="matrix-cell" style="grid-column:1/-1;text-align:center;padding:10px;">' + i18n.no_entries + '</div></div>');
            return;
        }

        let html = '';
        $.each(matrix, function (i, row) {
            html += '<div class="matrix-data-row" data-date="' + escHtml(row.date) + '">';
            html += '<div class="matrix-cell cell-date">' + escHtml(row.date) + '</div>';

            $.each(materials, function (j, mat) {
                const matIdKey = toMaterialId(mat.id);
                const price = row.prices[matIdKey] != null ? row.prices[matIdKey] : '';
                html += '<div class="matrix-cell mp-price-cell" data-material-id="' + matIdKey + '" data-original="' + escHtml(price) + '">' + escHtml(price) + '</div>';
            });

            html += '<div class="matrix-cell cell-actions">' +
                '<button type="button" class="button-secondary edit-row-btn">' + i18n.edit + '</button> ' +
                '<button type="button" class="button-secondary delete-date-row-btn">' + i18n.delete + '</button>' +
            '</div>';
            html += '</div>';
        });

        listBody.html(html);
    }

    regionSelect.on('change', function () {
        loadMaterials($(this).val());
    });

    form.on('submit', function (e) {
        e.preventDefault();

        const regionId = regionSelect.val();
        const date     = $('#mp_date').val();
        const prices   = [];

        $('.material-price-row').each(function () {
            const materialId = $(this).find('.mp-material-id').val();
            const price      = $(this).find('.mp-material-price').val();
            if (price !== '') {
                prices.push({ material_id: materialId, price: price });
            }
        });

        if (prices.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: i18n.warning,
                text: i18n.no_prices
            });
            return;
        }

        $.post(SanadTrackerMaterialPrices.ajax_url, {
            action: 'sanad_tracker_save_material_prices',
            nonce: SanadTrackerMaterialPrices.nonce,
            region_id: regionId,
            date: date,
            prices: prices
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

    $(document).on('click', '.edit-row-btn', function () {
        const row = $(this).closest('.matrix-data-row');

        row.find('.mp-price-cell').each(function () {
            const original = $(this).data('original');
            $(this).html('<input type="number" step="0.01" class="mp-inline-price" value="' + escHtml(original) + '">');
        });

        $(this).text(i18n.save).removeClass('edit-row-btn').addClass('save-row-btn');
        row.find('.delete-date-row-btn').text(i18n.cancel).removeClass('delete-date-row-btn').addClass('cancel-row-btn');
    });

    $(document).on('click', '.save-row-btn', function () {
        const row     = $(this).closest('.matrix-data-row');
        const date    = row.data('date');
        const regionId = regionSelect.val();
        const prices  = [];

        row.find('.mp-price-cell').each(function () {
            const materialId = $(this).data('material-id');
            const price      = $(this).find('.mp-inline-price').val();
            prices.push({ material_id: materialId, price: price });
        });

        Swal.fire({
            title: i18n.saving,
            text: i18n.loading,
            allowOutsideClick: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        $.post(SanadTrackerMaterialPrices.ajax_url, {
            action: 'sanad_tracker_update_material_prices',
            nonce: SanadTrackerMaterialPrices.nonce,
            region_id: regionId,
            date: date,
            prices: prices
        }, function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: i18n.saved,
                    timer: 1500,
                    showConfirmButton: false
                });
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

    $(document).on('click', '.cancel-row-btn', function () {
        const row = $(this).closest('.matrix-data-row');

        row.find('.mp-price-cell').each(function () {
            const original = $(this).data('original');
            $(this).text(original);
        });

        row.find('.save-row-btn').text(i18n.edit).removeClass('save-row-btn').addClass('edit-row-btn');
        $(this).text(i18n.delete).removeClass('cancel-row-btn').addClass('delete-date-row-btn');
    });

    $(document).on('click', '.delete-date-row-btn', function () {
        const row     = $(this).closest('.matrix-data-row');
        const date    = row.data('date');
        const regionId = regionSelect.val();

        Swal.fire({
            title: i18n.confirm_delete_date_title,
            text: i18n.confirm_delete_date_text + ' (' + date + ')',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: i18n.confirm_delete_yes,
            cancelButtonText: i18n.cancel
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: i18n.deleting,
                    allowOutsideClick: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                $.post(SanadTrackerMaterialPrices.ajax_url, {
                    action: 'sanad_tracker_update_material_prices',
                    nonce: SanadTrackerMaterialPrices.nonce,
                    region_id: regionId,
                    date: date,
                    prices: []
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: i18n.deleted,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadAdminList(regionId);
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
});
