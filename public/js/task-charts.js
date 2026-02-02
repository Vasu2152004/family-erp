// Store chart instances globally to allow cleanup
let taskChartInstances = {
    taskStatus: null
};

function initTaskCharts(taskStatusData) {
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
                    var chart = new ApexCharts(el, options); taskChartInstances[key] = chart; chart.render();
                } catch (e) { if (console && console.warn) console.warn('Chart render skipped:', e.message); }
            }, 100);
        } catch (e) { if (console && console.warn) console.warn('Chart render skipped:', e.message); }
    }

    // Ensure data arrays exist
    taskStatusData = taskStatusData || [];

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
    Object.keys(taskChartInstances).forEach(key => {
        if (taskChartInstances[key]) {
            taskChartInstances[key].destroy();
            taskChartInstances[key] = null;
        }
    });

    // Task Status Distribution Chart (Donut Chart)
    const taskStatusChartEl = document.getElementById('taskStatusChart');
    if (nodeInDocument(taskStatusChartEl) && taskStatusData.length > 0) {
        const total = taskStatusData.reduce((sum, item) => sum + item.count, 0);

        // Map status to colors
        const statusColors = {
            'pending': colors.warning,
            'in_progress': colors.info,
            'done': colors.success,
        };

        const taskStatusOptions = {
            series: taskStatusData.map(item => item.count),
            chart: {
                type: 'donut',
                height: 400,
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            labels: taskStatusData.map(item => item.label),
            colors: taskStatusData.map(item => statusColors[item.status] || colors.primary),
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    const value = opts.w.globals.series[opts.seriesIndex];
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return percentage + '%';
                },
                style: {
                    fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                    fontSize: '12px',
                    fontWeight: 600
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%'
                    }
                }
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                },
                y: {
                    formatter: function(value, { seriesIndex, w }) {
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return value + ' tasks (' + percentage + '%)';
                    }
                }
            },
            legend: {
                position: 'right',
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                labels: {
                    colors: colors.textPrimary
                }
            }
        };

        safeRender(taskStatusChartEl, taskStatusOptions, 'taskStatus');
    }
}
