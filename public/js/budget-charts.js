// Store chart instances globally to allow cleanup
let budgetChartInstances = {
    budgetVsActual: null
};

function initBudgetCharts(budgetVsActualData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }

    // Ensure data arrays exist
    budgetVsActualData = budgetVsActualData || [];

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
    Object.keys(budgetChartInstances).forEach(key => {
        if (budgetChartInstances[key]) {
            budgetChartInstances[key].destroy();
            budgetChartInstances[key] = null;
        }
    });

    // Budget vs Actual Spending Chart (Grouped Bar Chart)
    const budgetVsActualChartEl = document.getElementById('budgetVsActualChart');
    if (budgetVsActualChartEl && budgetVsActualData.length > 0) {
        // Determine if we should use horizontal bars (if many categories)
        const useHorizontal = budgetVsActualData.length > 6;

        const budgetVsActualOptions = {
            series: [
                {
                    name: 'Budgeted',
                    data: budgetVsActualData.map(item => item.budgeted_amount)
                },
                {
                    name: 'Spent',
                    data: budgetVsActualData.map(item => item.spent_amount)
                }
            ],
            chart: {
                type: 'bar',
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
            colors: [colors.success, colors.error],
            plotOptions: {
                bar: {
                    horizontal: useHorizontal,
                    borderRadius: 12,
                    columnWidth: '60%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: budgetVsActualData.map(item => item.category_name),
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
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
                labels: {
                    colors: colors.textPrimary
                }
            },
            grid: {
                borderColor: colors.borderPrimary,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: !useHorizontal
                    }
                },
                yaxis: {
                    lines: {
                        show: useHorizontal
                    }
                }
            }
        };

        budgetChartInstances.budgetVsActual = new ApexCharts(budgetVsActualChartEl, budgetVsActualOptions);
        budgetChartInstances.budgetVsActual.render();
    }
}
