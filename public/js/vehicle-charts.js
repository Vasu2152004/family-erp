// Store chart instances globally to allow cleanup
let vehicleChartInstances = {
    fuelConsumption: null
};

function initVehicleCharts(fuelConsumptionData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }
    function nodeInDocument(el) { return el && document.body && document.body.contains(el); }
    function safeRender(elOrId, options, key, dimensionRetries) {
        dimensionRetries = dimensionRetries || 0;
        var el = (typeof elOrId === 'string') ? document.getElementById(elOrId) : (elOrId && elOrId.id ? document.getElementById(elOrId.id) : elOrId);
        if (!el || !document.body || !document.body.contains(el)) return;
        try {
            var w = el.offsetWidth || (el.parentElement && el.parentElement.offsetWidth) || 400;
            var h = el.offsetHeight || (el.parentElement && el.parentElement.offsetHeight) || 400;
            if (w <= 0 || h <= 0) {
                if (dimensionRetries >= 40) return;
                setTimeout(function() { safeRender(elOrId, options, key, dimensionRetries + 1); }, 150);
                return;
            }
            var id = el.id;
            setTimeout(function() {
                el = id ? document.getElementById(id) : el;
                if (!el || !document.body.contains(el)) return;
                try {
                    if (!options.chart) options.chart = {}; options.chart.width = el.offsetWidth || w; options.chart.height = options.chart.height || 400;
                    var chart = new ApexCharts(el, options); vehicleChartInstances[key] = chart; chart.render();
                } catch (e) { if (console && console.warn) console.warn('Chart render skipped:', e.message); }
            }, 100);
        } catch (e) { if (console && console.warn) console.warn('Chart render skipped:', e.message); }
    }

    // Ensure data arrays exist
    fuelConsumptionData = fuelConsumptionData || [];

    // Get theme colors from CSS variables
    const styles = getComputedStyle(document.documentElement);
    const getColor = (token, fallback) => {
        const value = styles.getPropertyValue(`--color-${token}`)?.trim();
        return value || fallback;
    };

    const colors = {
        primary: getColor('primary', '#2563eb'),
        secondary: getColor('secondary', '#0ea5e9'),
        success: getColor('success', '#10b981'),
        warning: getColor('warning', '#f59e0b'),
        error: getColor('error', '#ef4444'),
        info: getColor('info', '#0284c7'),
        textPrimary: getColor('text-primary', '#0f172a'),
        textSecondary: getColor('text-secondary', '#475569'),
        borderPrimary: getColor('border-primary', '#e2e8f0'),
        bgSecondary: getColor('bg-secondary', '#ffffff'),
    };

    // Destroy existing charts
    Object.keys(vehicleChartInstances).forEach(key => {
        if (vehicleChartInstances[key]) {
            vehicleChartInstances[key].destroy();
            vehicleChartInstances[key] = null;
        }
    });

    // Fuel Consumption Trends Chart (Area Chart)
    const fuelConsumptionChartEl = document.getElementById('fuelConsumptionChart');
    if (nodeInDocument(fuelConsumptionChartEl) && fuelConsumptionData.length > 0) {
        const fuelConsumptionOptions = {
            series: [{
                name: 'Fuel Consumption',
                data: fuelConsumptionData.map(item => item.fuel_amount)
            }],
            chart: {
                type: 'area',
                height: 400,
                toolbar: {
                    show: false
                },
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            colors: [colors.info],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.3,
                    opacityTo: 0,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: fuelConsumptionData.map(item => item.month_name),
                labels: {
                    style: {
                        colors: colors.textSecondary,
                        fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        return value.toLocaleString('en-IN', {maximumFractionDigits: 2}) + ' L';
                    },
                    style: {
                        colors: colors.textSecondary,
                        fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                    }
                }
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                },
                y: {
                    formatter: function(value, { dataPointIndex, w }) {
                        const cost = fuelConsumptionData[dataPointIndex]?.total_cost || 0;
                        return value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' L (₹' + cost.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')';
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: colors.borderPrimary,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            markers: {
                size: 5,
                hover: {
                    size: 7
                }
            }
        };

        safeRender(fuelConsumptionChartEl, fuelConsumptionOptions, 'fuelConsumption');
    }
}
