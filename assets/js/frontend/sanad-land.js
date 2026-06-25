(function () {
  const AXIS = "#243c69";
  const GRID = "#e5e7eb";
  const COLORS = {
    shell_core: "#3b82f6",
    fully_finished: "#10b981",
  };

  function formatMonth(ym) {
    const d = new Date(ym + "-01");
    return d.toLocaleDateString("en-US", { month: "short", year: "2-digit" });
  }

  function indicatorHtml(dir, pct) {
    if (dir === "up") {
      return '<span class="sanad-indicator-badge up">\u25B2 ' + Math.abs(pct).toFixed(1) + '%</span>';
    }
    if (dir === "down") {
      return '<span class="sanad-indicator-badge down">\u25BC ' + Math.abs(pct).toFixed(1) + '%</span>';
    }
    if (pct !== null && pct === 0.0) {
      return '<span class="sanad-indicator-badge neutral">0.0%</span>';
    }
    return '<span class="sanad-indicator-badge neutral">\u2014</span>';
  }

  function buildTable(data) {
    const sc = data.shell_core;
    const ff = data.fully_finished;

    const scPrice =
      sc.latest_price !== null
        ? parseFloat(sc.latest_price).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        : "\u2014";
    const ffPrice =
      ff.latest_price !== null
        ? parseFloat(ff.latest_price).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        : "\u2014";

    return (
      '<table class="sanad-land-table">' +
      "<thead>" +
      "<tr>" +
      "<th>" +
      SanadTrackerLand.i18n.type +
      "</th>" +
      "<th>" +
      SanadTrackerLand.i18n.latest_price +
      "</th>" +
      "<th>" +
      SanadTrackerLand.i18n.indicator +
      "</th>" +
      "</tr>" +
      "</thead>" +
      "<tbody>" +
      "<tr>" +
      "<td>" +
      SanadTrackerLand.i18n.shell_core +
      "</td>" +
      "<td>" +
      scPrice +
      "</td>" +
      "<td>" +
      indicatorHtml(sc.indicator_dir, sc.indicator_pct) +
      "</td>" +
      "</tr>" +
      "<tr>" +
      "<td>" +
      SanadTrackerLand.i18n.fully_finished +
      "</td>" +
      "<td>" +
      ffPrice +
      "</td>" +
      "<td>" +
      indicatorHtml(ff.indicator_dir, ff.indicator_pct) +
      "</td>" +
      "</tr>" +
      "</tbody>" +
      "</table>" +
      '<canvas class="sanad-land-chart" width="400" height="200"></canvas>'
    );
  }

  function buildChart(canvas, data) {
    const scChart = data.shell_core.chart_data || [];
    const ffChart = data.fully_finished.chart_data || [];

    const allMonths = [];
    const scMap = {};
    const ffMap = {};

    scChart.forEach(function (d) { scMap[d.month] = d.avg_price; });
    ffChart.forEach(function (d) { ffMap[d.month] = d.avg_price; });

    Object.keys(scMap).forEach(function (m) {
      if (allMonths.indexOf(m) === -1) allMonths.push(m);
    });
    Object.keys(ffMap).forEach(function (m) {
      if (allMonths.indexOf(m) === -1) allMonths.push(m);
    });
    allMonths.sort();

    const labels = [];
    const scData = [];
    const ffData = [];

    allMonths.forEach(function (m) {
      labels.push(formatMonth(m));
      scData.push(scMap[m] !== undefined ? parseFloat(scMap[m]) : null);
      ffData.push(ffMap[m] !== undefined ? parseFloat(ffMap[m]) : null);
    });

    if (window.sanadLandChart) {
      window.sanadLandChart.destroy();
    }

    const ctx = canvas.getContext("2d");
    window.sanadLandChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: SanadTrackerLand.i18n.shell_core,
            data: scData,
            borderColor: COLORS.shell_core,
            backgroundColor: COLORS.shell_core,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 2,
            fill: false,
          },
          {
            label: SanadTrackerLand.i18n.fully_finished,
            data: ffData,
            borderColor: COLORS.fully_finished,
            backgroundColor: COLORS.fully_finished,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 2,
            fill: false,
          },
        ],
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
          tooltip: {
            displayColors: true,
            callbacks: {
              label: function (context) {
                if (context.parsed.y == null) return "";
                return (
                  context.dataset.label +
                  ": " +
                  context.parsed.y.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })
                );
              },
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: AXIS, maxRotation: 0 },
          },
          y: {
            grid: { color: GRID, drawBorder: false },
            ticks: { color: AXIS, maxTicksLimit: 6 },
          },
        },
      },
    });
  }

  function getRegionId(el) {
    const val = el.value;
    return val ? parseInt(val, 10) : 0;
  }

  async function fetchRegion(regionId, wrapper) {
    const container = wrapper.querySelector(".sanad-land-table-container");
    const loader = wrapper.querySelector(".sanad-loader");
    if (loader) loader.style.display = "block";
    container.innerHTML = "";

    const formData = new FormData();
    formData.append("action", "sanad_tracker_get_land_table");
    formData.append("region_id", regionId);
    formData.append("nonce", SanadTrackerLand.nonce);

    try {
      const response = await fetch(SanadTrackerLand.ajax_url, {
        method: "POST",
        body: formData,
      });
      const json = await response.json();

      if (loader) loader.style.display = "none";

      if (json.success) {
        container.innerHTML = buildTable(json.data);
        const canvas = container.querySelector(".sanad-land-chart");
        if (canvas) {
          buildChart(canvas, json.data);
        }
      } else {
        container.innerHTML =
          '<p class="sanad-error">' +
          (json.data.message || SanadTrackerLand.i18n.error) +
          "</p>";
      }
    } catch (err) {
      if (loader) loader.style.display = "none";
      container.innerHTML = '<p class="sanad-error">' + SanadTrackerLand.i18n.error + "</p>";
    }
  }

  async function resolveSlug(slug) {
    const formData = new FormData();
    formData.append("action", "sanad_tracker_get_regions_list");
    formData.append("nonce", SanadTrackerLand.nonce);

    try {
      const response = await fetch(SanadTrackerLand.ajax_url, {
        method: "POST",
        body: formData,
      });
      const json = await response.json();

      if (json.success && json.data.regions) {
        for (let i = 0; i < json.data.regions.length; i++) {
          if (json.data.regions[i].slug === slug) {
            return parseInt(json.data.regions[i].id, 10);
          }
        }
      }
    } catch (err) {
      // silent
    }
    return 0;
  }

  async function populateRegions(select, wrapper) {
    const formData = new FormData();
    formData.append("action", "sanad_tracker_get_regions_list");
    formData.append("nonce", SanadTrackerLand.nonce);

    try {
      const response = await fetch(SanadTrackerLand.ajax_url, {
        method: "POST",
        body: formData,
      });
      const json = await response.json();

      if (json.success && json.data.regions && json.data.regions.length > 0) {
        select.innerHTML = '';
        json.data.regions.forEach(function (r) {
          const opt = document.createElement("option");
          opt.value = r.id;
          opt.textContent = r.name;
          select.appendChild(opt);
        });
        select.value = json.data.regions[0].id;
        fetchRegion(json.data.regions[0].id, wrapper);
      }
    } catch (err) {
      // silently fail
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".sanad-land-wrapper");
    wrappers.forEach(function (wrapper) {
      const regionSlug = wrapper.dataset.region;

      if (regionSlug) {
        resolveSlug(regionSlug).then(function (id) {
          if (id) {
            fetchRegion(id, wrapper);
          } else {
            const container = wrapper.querySelector(".sanad-land-table-container");
            container.innerHTML =
              '<p class="sanad-error">' + SanadTrackerLand.i18n.no_data + "</p>";
          }
        });
      } else {
        const select = wrapper.querySelector(".sanad-region-select");
        if (!select) return;

        populateRegions(select, wrapper);

        select.addEventListener("change", function () {
          const id = getRegionId(select);
          if (id) {
            fetchRegion(id, wrapper);
          }
        });
      }
    });
  });
})();
