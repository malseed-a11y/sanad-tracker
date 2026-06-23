jQuery(document).ready(function ($) {
    const taxonomySelect = $('#taxonomy_id');
    const rowsWrapper = $('#category-rows-wrapper');

    // initialize Select2
    taxonomySelect.select2({
        placeholder: "-- Select Taxonomy --",
        allowClear: true,
        width: '350px'
    });


    taxonomySelect.on('change', function () {
        const taxId = $(this).val();
        rowsWrapper.empty();

        if (categoriesByTax[taxId]) {
            const today = new Date().toISOString().split('T')[0];

            categoriesByTax[taxId].forEach(tc => {
                const rowHtml = `
                    <div class="item-row" id="row-${tc.id}" style="margin-bottom:15px; border-bottom:1px solid #ddd; padding:10px;">
                        <strong>${tc.category_name}</strong>
                        <input type="hidden" class="taxonomy-category-id" value="${tc.id}">
                        <div style="margin-top:5px;">
                            <label>Buy Price: </label>
                            <input type="number" step="0.01" class="buy-price" style="width:100px;">
                            <label>Sell Price: </label>
                            <input type="number" step="0.01" class="sell-price" style="width:100px;">
                            <label>Date: </label>
                            <input type="date" class="price-date" value="${today}" style="width:150px;">
                            <button class="add-item-single button button-primary" data-row-id="${tc.id}">Add</button>
                        </div>
                    </div>
                `;
                rowsWrapper.append(rowHtml);
            });
        }
    });
});