// Store chart instances globally to allow cleanup
let taskChartInstances = {
    taskStatus: null
};

function initTaskCharts(taskStatusData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
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
    if (taskStatusChartEl && taskStatusData.length > 0) {
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

        taskChartInstances.taskStatus = new ApexCharts(taskStatusChartEl, taskStatusOptions);
        taskChartInstances.taskStatus.render();
    }
}
