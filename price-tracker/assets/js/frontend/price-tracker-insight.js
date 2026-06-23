jQuery(document).ready(function ($) {
  const AXIS = "#243c69";
  const GRID = "#e5e7eb";

  const i18n = PriceTrackerInsightAjax.i18n;
  const chartLocale = PriceTrackerInsightAjax.locale;

  const fmtDate = (s) => {
    const d = new Date(s);
    return isNaN(d)
      ? String(s).slice(0, 10)
      : d.toLocaleDateString(chartLocale, { month: "short", day: "numeric" });
  };

  function styleDatasets(datasets) {
    return (datasets || []).map((ds) => ({
      ...ds,
      type: "line",
      borderWidth: 2,
      fill: false,
      tension: 0.4,
      cubicInterpolationMode: "monotone",
      pointRadius: 0,
      pointHoverRadius: 0,
      pointHitRadius: 24,
    }));
  }

  function computeYRange(dsets) {
    const vals = dsets
      .flatMap((d) => d.data || [])
      .map(Number)
      .filter(Number.isFinite);
    const min = vals.length ? Math.min(...vals) : 0;
    const max = vals.length ? Math.max(...vals) : 1;
    const pad = (max - min || 1) * 0.08;
    return { min: Math.floor(min - pad), max: Math.ceil(max + pad) };
  }

  $(".price-tracker-insight-filter").on("submit", handleInsightFilterSubmit);

  function handleInsightFilterSubmit(e) {
    e.preventDefault();

    const wrapper = $(this).closest(".price-tracker-insight-wrapper");
    const taxonomy = wrapper
      .find(".price-tracker-insight-table-wrapper")
      .data("taxonomy");
    const date = $(this).find('[name="the_date"]').val();
    const range = $(this).find('[name="range"]').val();

    const table = wrapper.find(".price-tracker-insight-table");
    const loader = wrapper.find(".price-tracker-loader");
    const chartCanvas = wrapper.find("canvas")[0];
    const infoArea = wrapper.find(
      ".price-tracker-insight-chart-information-area"
    );

    table.html(`<p>${i18n.loading}</p>`);
    infoArea.html(i18n.loading);
    loader.css("display", "flex");
    chartCanvas.style.display = "none";

    // AJAX
    $.post(
      PriceTrackerInsightAjax.ajax_url,
      {
        action: "get_price_tracker_insight",
        nonce: PriceTrackerInsightAjax.nonce,
        taxonomy,
        date,
        range,
      },
      function (response) {
        loader.css("display", "none");

        if (response.success) {
          table.html(response.data.table_html);

          const d = response.data.chart;
          if (d && d.labels && d.labels.length > 0) {
            const ctx = chartCanvas.getContext("2d");
            if (window.insightChart) window.insightChart.destroy();

            const datasets = styleDatasets(d.datasets);
            const { min, max } = computeYRange(datasets);

            chartCanvas.style.display = "block";
            infoArea.html("");

            window.insightChart = new Chart(ctx, {
              type: "line",
              data: {
                labels: d.labels,
                datasets,
              },
              options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: "index", intersect: false },
                plugins: {
                  legend: {
                    position: "top",
                    labels: {
                      pointStyle: "rect",
                      usePointStyle: true,
                      boxWidth: 10,
                      boxHeight: 10,
                      color: AXIS,
                    },
                  },
                  title: {
                    display: !!d.title,
                    text: d.title || i18n.chart_title,
                  },
                  tooltip: {
                    displayColors: false,
                    callbacks: {
                      title: (items) => fmtDate(items?.[0]?.label ?? ""),
                      label: (c) =>
                        c.parsed?.y == null
                          ? ""
                          : `${
                              c.dataset.label || ""
                            }: ${c.parsed.y.toLocaleString(chartLocale, {
                              minimumFractionDigits: 3,
                              maximumFractionDigits: 3,
                            })}`,
                    },
                  },
                },
                scales: {
                  x: {
                    grid: { display: false },
                    ticks: {
                      color: AXIS,
                      maxRotation: 0,
                      callback: (v) => fmtDate(d.labels[v]),
                    },
                    title: { display: true, text: i18n.axis_date },
                  },
                  y: {
                    min,
                    max,
                    grid: { color: GRID, drawBorder: false },
                    ticks: {
                      color: AXIS,
                      maxTicksLimit: 6,
                      callback: (v) =>
                        v.toLocaleString(chartLocale, {
                          minimumFractionDigits: 0,
                          maximumFractionDigits: 3,
                        }),
                    },
                    title: { display: true, text: i18n.axis_price },
                  },
                },
              },
            });
          } else {
            infoArea.html(i18n.no_data);
          }
        } else {
          table.html(`<p>${i18n.network_error}</p>`);
          infoArea.html(i18n.network_error);
        }
      }
    ).fail(function () {
      loader.css("display", "none");
      table.html(`<p>${i18n.network_error}</p>`);
      infoArea.html(i18n.network_error);
    });
  }

  // Auto-load insights on page load
  $(".price-tracker-insight-filter").trigger("submit");
});
