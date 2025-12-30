// Store chart instances globally to allow cleanup
let assetChartInstances = {
    typeDistribution: null,
    profitLossTrend: null,
    ownerDistribution: null,
    countByType: null
};

function initAssetCharts(typeDistributionData, profitLossTrendData, ownerDistributionData, countByTypeData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }

    // Ensure data arrays exist
    typeDistributionData = typeDistributionData || [];
    profitLossTrendData = profitLossTrendData || [];
    ownerDistributionData = ownerDistributionData || [];
    countByTypeData = countByTypeData || [];

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
    Object.keys(assetChartInstances).forEach(key => {
        if (assetChartInstances[key]) {
            assetChartInstances[key].destroy();
            assetChartInstances[key] = null;
        }
    });

    // Asset Type Distribution Chart (Donut Chart)
    const typeDistributionChartEl = document.getElementById('assetTypeDistributionChart');
    if (typeDistributionChartEl && typeDistributionData.length > 0) {
        const total = typeDistributionData.reduce((sum, item) => sum + item.total_value, 0);

        const typeDistributionOptions = {
            series: typeDistributionData.map(item => item.total_value),
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
            labels: typeDistributionData.map(item => item.type),
            colors: typeDistributionData.map((_, idx) => palette[idx % palette.length]),
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
                    formatter: function(value) {
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

        assetChartInstances.typeDistribution = new ApexCharts(typeDistributionChartEl, typeDistributionOptions);
        assetChartInstances.typeDistribution.render();
    }

    // Asset Count by Type Chart (Bar Chart)
    const countByTypeChartEl = document.getElementById('assetCountByTypeChart');
    if (countByTypeChartEl && countByTypeData.length > 0) {
        const countByTypeOptions = {
            series: [{
                name: 'Count',
                data: countByTypeData.map(item => item.count)
            }],
            chart: {
                type: 'bar',
                height: 400,
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                toolbar: {
                    show: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(value) {
                    return value;
                },
                style: {
                    fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                    fontSize: '12px',
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: countByTypeData.map(item => item.type),
                labels: {
                    style: {
                        colors: colors.textSecondary,
                        fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                    },
                    rotate: countByTypeData.length > 6 ? -45 : 0,
                    rotateAlways: countByTypeData.length > 6
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        return value;
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
                    formatter: function(value) {
                        return value + ' assets';
                    }
                }
            },
            colors: [colors.primary]
        };

        assetChartInstances.countByType = new ApexCharts(countByTypeChartEl, countByTypeOptions);
        assetChartInstances.countByType.render();
    }

    // Profit/Loss Trend Chart (Column Chart - better for showing profit vs loss)
    const profitLossTrendChartEl = document.getElementById('assetProfitLossTrendChart');
    if (profitLossTrendChartEl && profitLossTrendData.length > 0) {
        // Sort by month to ensure chronological order
        const sortedData = [...profitLossTrendData].sort((a, b) => a.month.localeCompare(b.month));
        
        // Extract profit/loss values
        const profitLossValues = sortedData.map(item => parseFloat(item.profit_loss) || 0);

        const profitLossTrendOptions = {
            series: [{
                name: 'Profit/Loss',
                data: profitLossValues
            }],
            chart: {
                type: 'bar',
                height: 400,
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                toolbar: {
                    show: true,
                    tools: {
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 4,
                    colors: {
                        ranges: [{
                            from: -Infinity,
                            to: 0,
                            color: colors.error
                        }, {
                            from: 0,
                            to: Infinity,
                            color: colors.success
                        }]
                    },
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: false // Disable data labels to avoid overlap and improve readability
            },
            xaxis: {
                categories: sortedData.map(item => item.month),
                labels: {
                    style: {
                        colors: colors.textSecondary,
                        fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                        fontSize: '11px'
                    },
                    rotate: sortedData.length > 12 ? -45 : 0,
                    rotateAlways: sortedData.length > 12,
                    hideOverlappingLabels: true,
                    showDuplicates: false,
                    maxHeight: 60
                },
                // Show fewer labels for better readability when there are many months
                tickAmount: sortedData.length > 24 ? Math.ceil(sortedData.length / 12) : (sortedData.length > 12 ? Math.ceil(sortedData.length / 6) : undefined)
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        return '₹' + value.toLocaleString('en-IN', {maximumFractionDigits: 0});
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
                    formatter: function(value) {
                        const sign = value >= 0 ? '+' : '';
                        return sign + '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            },
            grid: {
                borderColor: colors.borderPrimary,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            annotations: {
                yaxis: [{
                    y: 0,
                    borderColor: colors.textSecondary,
                    borderWidth: 2,
                    strokeDashArray: 4,
                    label: {
                        text: 'Break Even',
                        style: {
                            color: colors.textSecondary,
                            fontSize: '11px',
                            fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif'
                        }
                    }
                }]
            }
        };

        assetChartInstances.profitLossTrend = new ApexCharts(profitLossTrendChartEl, profitLossTrendOptions);
        assetChartInstances.profitLossTrend.render();
    }

    // Owner-wise Distribution Chart (Donut Chart)
    const ownerDistributionChartEl = document.getElementById('assetOwnerDistributionChart');
    if (ownerDistributionChartEl && ownerDistributionData.length > 0) {
        const total = ownerDistributionData.reduce((sum, item) => sum + item.total_value, 0);

        const ownerDistributionOptions = {
            series: ownerDistributionData.map(item => item.total_value),
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
            labels: ownerDistributionData.map(item => item.owner_name),
            colors: ownerDistributionData.map((_, idx) => palette[idx % palette.length]),
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
                    formatter: function(value) {
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

        assetChartInstances.ownerDistribution = new ApexCharts(ownerDistributionChartEl, ownerDistributionOptions);
        assetChartInstances.ownerDistribution.render();
    }
}











