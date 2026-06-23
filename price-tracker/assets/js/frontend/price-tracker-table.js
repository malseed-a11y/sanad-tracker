jQuery(document).ready(function ($) {
  $(".price-tracker-table-filter").on("submit", submitPriceTrackerTableFilter);

  function submitPriceTrackerTableFilter(e) {
    e.preventDefault();

    const wrapper = $(this).closest(".price-tracker-table-wrapper");
    const taxonomy = wrapper.data("taxonomy");
    // const category = wrapper.data('category');
    const theDate = $(this).find('[name="the_date"]').val();
    const range = $(this).find('[name="range"]').val();

    wrapper.find(".price-tracker-table-container").html("<p>Loading...</p>");
    let loader = wrapper.find(".price-tracker-loader");
    loader.css("display", "flex");

    $.post(
      PriceTrackerTableAjax.ajax_url,
      {
        action: "price_tracker_table_filter",
        nonce: PriceTrackerTableAjax.nonce,
        taxonomy: taxonomy,
        // category: category,
        date: theDate,
        range: range,
      },
      function (response) {
        wrapper.find(".price-tracker-table-container").html(response.html);
        loader.css("display", "none");
      }
    );
  }

  // Auto-submit once page is loaded
  $(".price-tracker-table-filter").trigger("submit");
});
