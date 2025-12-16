// Store chart instances globally to allow cleanup
let inventoryChartInstances = {
    categoryDistribution: null,
    stockStatus: null
};

function initInventoryCharts(categoryData, stockStatusData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }

    // Ensure data arrays exist
    categoryData = categoryData || [];
    stockStatusData = stockStatusData || [];

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

    // Color palette for charts
    const palette = [
        colors.primary,
        colors.secondary,
        colors.success,
        colors.warning,
        colors.error,
        colors.info,
    ];

    // Destroy existing charts
    Object.keys(inventoryChartInstances).forEach(key => {
        if (inventoryChartInstances[key]) {
            inventoryChartInstances[key].destroy();
            inventoryChartInstances[key] = null;
        }
    });

    // Category-wise Distribution Chart (Donut Chart)
    const categoryChartEl = document.getElementById('categoryDistributionChart');
    if (categoryChartEl && categoryData.length > 0) {
        const total = categoryData.reduce((sum, item) => sum + item.total_qty, 0);

        const categoryOptions = {
            series: categoryData.map(item => item.total_qty),
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
            labels: categoryData.map(item => item.category_name),
            colors: categoryData.map((_, idx) => palette[idx % palette.length]),
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
                        return value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + percentage + '%)';
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

        inventoryChartInstances.categoryDistribution = new ApexCharts(categoryChartEl, categoryOptions);
        inventoryChartInstances.categoryDistribution.render();
    }

    // Stock Status Overview Chart (Donut Chart)
    const stockStatusChartEl = document.getElementById('stockStatusChart');
    if (stockStatusChartEl && stockStatusData.length > 0) {
        const total = stockStatusData.reduce((sum, item) => sum + item.count, 0);

        // Map status to colors
        const statusColors = {
            'healthy': colors.success,
            'low_stock': colors.warning,
            'out_of_stock': colors.error,
        };

        const stockStatusOptions = {
            series: stockStatusData.map(item => item.count),
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
            labels: stockStatusData.map(item => item.label),
            colors: stockStatusData.map(item => statusColors[item.status] || colors.primary),
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
                        return value + ' items (' + percentage + '%)';
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

        inventoryChartInstances.stockStatus = new ApexCharts(stockStatusChartEl, stockStatusOptions);
        inventoryChartInstances.stockStatus.render();
    }
}
