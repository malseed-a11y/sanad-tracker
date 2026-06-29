document.addEventListener('DOMContentLoaded', function () {
    const wrappers = document.querySelectorAll('.sanad-materials-wrapper');
    if (wrappers.length === 0) return;

    wrappers.forEach(function (wrapper) {
        const regionSlug = wrapper.dataset.region;
        const instanceId = Math.random().toString(36).substr(2, 9);
        const select = wrapper.querySelector('.sanad-region-select');
        const container = wrapper.querySelector('.sanad-materials-table-container');
        const loader = wrapper.querySelector('.sanad-loader');
        const i18n = SanadTrackerFrontend.i18n;

        function showLoader() {
            loader.style.display = 'flex';
        }

        function hideLoader() {
            loader.style.display = 'none';
        }

        async function fetchRegionsList() {
            const formData = new FormData();
            formData.append('action', 'sanad_tracker_get_regions_list');
            formData.append('nonce', SanadTrackerFrontend.nonce);

            try {
                const response = await fetch(SanadTrackerFrontend.ajax_url, { method: 'POST', body: formData });
                const json = await response.json();
                if (json.success) {
                    return json.data.regions;
                }
            } catch (e) {
                container.innerHTML = '<p>' + i18n.error + '</p>';
            }
            return [];
        }

        async function fetchTableData(regionId) {
            showLoader();
            container.innerHTML = '';

            const formData = new FormData();
            formData.append('action', 'sanad_tracker_get_materials_table');
            formData.append('nonce', SanadTrackerFrontend.nonce);
            formData.append('region_id', regionId);

            try {
                const response = await fetch(SanadTrackerFrontend.ajax_url, { method: 'POST', body: formData });
                const json = await response.json();
                if (json.success && json.data.rows) {
                    renderTable(json.data.rows);
                } else {
                    container.innerHTML = '<p>' + i18n.no_data + '</p>';
                }
            } catch (e) {
                container.innerHTML = '<p>' + i18n.error + '</p>';
            } finally {
                hideLoader();
            }
        }

        function formatIndicator(dir, pct) {
            if (dir === 'up') {
                return '<span class="sanad-indicator-badge up">\u25B2 ' + Math.abs(pct).toFixed(1) + '%</span>';
            }
            if (dir === 'down') {
                return '<span class="sanad-indicator-badge down">\u25BC ' + Math.abs(pct).toFixed(1) + '%</span>';
            }
            if (pct !== null && pct === 0.0) {
                return '<span class="sanad-indicator-badge neutral">0.0%</span>';
            }
            return '<span class="sanad-indicator-badge neutral">\u2014</span>';
        }

        function renderTable(rows) {
            const thead = '<thead><tr>' +
                '<th>' + i18n.indicator + '</th>' +
                '<th>' + i18n.price_chart + '</th>' +
                '<th>' + i18n.latest_price + '</th>' +
                '<th>' + i18n.material + '</th>' +
                '</tr></thead>';

            const tbody = '<tbody>' + rows.map(function (row, index) {
                const price = row.latest_price !== null
                    ? parseFloat(row.latest_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '\u2014';
                return '<tr>' +
                    '<td>' + formatIndicator(row.indicator_dir, row.indicator_pct) + '</td>' +
                    '<td class="sanad-chart-cell">' +
                    '<div class="sanad-sparkline-wrapper">' +
                    '<canvas class="sanad-mini-chart" id="spark-' + instanceId + '-' + index + '"></canvas>' +
                    '</div>' +
                    '</td>' +
                    '<td class="sanad-price-cell">' + price + '</td>' +
                    '<td>' + row.material_name + '</td>' +
                    '</tr>';
            }).join('') + '</tbody>';

            container.innerHTML = '<table class="sanad-materials-table">' + thead + tbody + '</table>';

            rows.forEach(function (row, index) {
                const canvas = document.getElementById('spark-' + instanceId + '-' + index);
                if (!canvas || !row.chart_data || row.chart_data.length === 0) return;

                const prices = row.chart_data.map(function (d) { return parseFloat(d.avg); });

                new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: prices.map(function (_, idx) { return idx; }),
                        datasets: [{
                            data: prices,
                            borderColor: '#425C61',
                            backgroundColor: 'transparent',
                            borderWidth: 1.75,
                            borderJoinStyle: 'miter',
                            tension: 0,
                            pointRadius: 0,
                            pointHitRadius: 0,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        events: [],
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false, grace: '10%' }
                        }
                    }
                });
            });
        }

        if (regionSlug) {
            const regionSelector = wrapper.querySelector('.sanad-region-selector');
            if (regionSelector) regionSelector.style.display = 'none';

            fetchRegionsList().then(function (regions) {
                const region = regions.find(function (r) { return r.slug === regionSlug; });
                if (region) {
                    fetchTableData(region.id);
                } else {
                    container.innerHTML = '<p>' + i18n.no_data + '</p>';
                }
            });
        } else {
            fetchRegionsList().then(function (regions) {
                if (regions.length > 0) {
                    select.innerHTML = '';
                    regions.forEach(function (r) {
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = r.name;
                        select.appendChild(opt);
                    });
                    select.value = regions[0].id;
                    fetchTableData(regions[0].id);
                } else {
                    select.innerHTML = '<option value="">' + i18n.no_data + '</option>';
                }
            });

            select.addEventListener('change', function () {
                const regionId = this.value;
                if (regionId) {
                    fetchTableData(regionId);
                }
            });
        }
    });
});