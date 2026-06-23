document.addEventListener("DOMContentLoaded", () => {
  const AXIS = "#243c69";
  const GRID = "#e5e7eb";

  const i18n = PriceTrackerChartAjax.i18n;
  const chartLocale = PriceTrackerChartAjax.locale;

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

  document.querySelectorAll(".price-tracker-chart-wrapper").forEach((wrap) => {
    const taxonomy = wrap.dataset.taxonomy || "";
    const category = wrap.dataset.category || "";

    const form = wrap.querySelector(".price-tracker-chart-filter");
    const loader = wrap.querySelector(".price-tracker-loader");
    const info = wrap.querySelector(".price-tracker-chart-information-area");
    const canvas = wrap.querySelector("canvas");
    const ctx = canvas.getContext("2d");
    let chart;

    async function loadChart() {
      const from = form?.querySelector("[name='from']")?.value || "";
      const to = form?.querySelector("[name='to']")?.value || "";

      if (loader) loader.style.display = "flex";
      canvas.style.display = "none";
      if (info) info.textContent = i18n.loading;

      const fd = new FormData();
      fd.append("action", "get_price_tracker_chart");
      fd.append("taxonomy", taxonomy);
      fd.append("category", category);
      fd.append("from", from);
      fd.append("to", to);

      fd.append("nonce", PriceTrackerChartAjax.nonce);

      let json;
      try {
        const res = await fetch(PriceTrackerChartAjax.ajax_url, {
          method: "POST",
          body: fd,
        });
        json = await res.json();
      } catch {
        if (loader) loader.style.display = "none";
        if (info) info.textContent = i18n.network_error;
        return;
      }

      if (loader) loader.style.display = "none";

      if (!json?.success || !json.data?.labels?.length) {
        if (info) info.textContent = i18n.no_data;
        return;
      }

      const d = json.data;
      const datasets = styleDatasets(d.datasets);
      const { min, max } = computeYRange(datasets);

      if (chart) chart.destroy();

      canvas.style.display = "block";
      if (info) info.textContent = "";

      chart = new Chart(ctx, {
        type: "line",
        data: { labels: d.labels, datasets },
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
                    : `${c.dataset.label || ""}: ${c.parsed.y.toLocaleString(
                        chartLocale,
                        { minimumFractionDigits: 3, maximumFractionDigits: 3 },
                      )}`,
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
              position: "right",
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
    }

    loadChart();
    form?.addEventListener("submit", (e) => {
      e.preventDefault();
      loadChart();
    });
  });
});
