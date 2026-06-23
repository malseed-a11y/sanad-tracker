function initSelect2($select) {
    $select.select2({
        placeholder: 'Select an option',
        width: '350px'
    });
}

jQuery(document).ready(function ($) {
    initSelect2($('.support-select2'));
});


jQuery(document).ready(function ($) {

    // Handle Edit button click
    $(document).on('click', '.edit-category', function (e) {
        e.preventDefault();

        let btn = $(this);
        let categoryId = btn.data('category-id');
        let categoryName = btn.data('name');
        let categoryTaxonomies = btn.data('taxonomies').toString().split(',');

        // Fill the hidden edit form
        $('#edit_category_id').val(categoryId);
        $('#edit_category_name').val(categoryName);

        // Reset taxonomy select
        $('#edit_category_taxonomies option').prop('selected', false);

        // Select assigned taxonomies
        $('#edit_category_taxonomies option').each(function () {
            if (categoryTaxonomies.includes($(this).val())) {
                $(this).prop('selected', true);
            }
        });

        // Show the form
        $('#edit-category-wrapper').slideDown();
        initSelect2($('.support-select2'));
    });

    // Cancel edit
    $(document).on('click', '#cancel-edit', function (e) {
        e.preventDefault();
        $('#edit-category-wrapper').slideUp();
    });

});




