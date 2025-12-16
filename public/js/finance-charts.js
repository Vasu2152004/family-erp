// Store chart instances globally to allow cleanup
let chartInstances = {
    monthly: null,
    memberWise: null,
    categoryWise: null,
    savingsTrend: null,
    accountBalanceTrends: null,
    incomeSources: null,
    expensePatterns: null
};

function initFinanceCharts(monthlyData, memberWiseData, categoryWiseData, savingsTrendData, accountBalanceTrends, incomeSourcesData, expensePatternsData) {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }

    // Ensure data arrays exist
    monthlyData = monthlyData || [];
    memberWiseData = memberWiseData || [];
    categoryWiseData = categoryWiseData || [];
    savingsTrendData = savingsTrendData || [];
    accountBalanceTrends = accountBalanceTrends || [];
    incomeSourcesData = incomeSourcesData || [];
    expensePatternsData = expensePatternsData || [];

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
    Object.keys(chartInstances).forEach(key => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            chartInstances[key] = null;
        }
    });

    // Monthly Expenses & Income Chart (Area/Line Chart)
    const monthlyChartEl = document.getElementById('monthlyChart');
    if (monthlyChartEl) {
        // Ensure we have data for all 12 months
        const currentYear = new Date().getFullYear();
        let chartData = monthlyData.length > 0 ? monthlyData : Array.from({length: 12}, (_, i) => ({
            month: i + 1,
            month_name: new Date(currentYear, i).toLocaleString('default', { month: 'short' }),
            expenses: 0,
            income: 0
        }));
        
        // Ensure chartData has exactly 12 months
        if (chartData.length < 12) {
            const existingMonths = chartData.map(d => d.month);
            for (let i = 1; i <= 12; i++) {
                if (!existingMonths.includes(i)) {
                    chartData.push({
                        month: i,
                        month_name: new Date(currentYear, i - 1).toLocaleString('default', { month: 'short' }),
                        expenses: 0,
                        income: 0
                    });
                }
            }
            chartData.sort((a, b) => a.month - b.month);
        }

        const monthlyOptions = {
            series: [
                {
                    name: 'Income',
                    data: chartData.map(item => item.income)
                },
                {
                    name: 'Expenses',
                    data: chartData.map(item => item.expenses)
                }
            ],
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
            colors: [colors.success, colors.error],
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
                    opacityFrom: 0.2,
                    opacityTo: 0,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: chartData.map(item => item.month_name),
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
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            }
        };

        chartInstances.monthly = new ApexCharts(monthlyChartEl, monthlyOptions);
        chartInstances.monthly.render();
    }

    // Member-wise Spending Chart (Doughnut Chart)
    const memberWiseChartEl = document.getElementById('memberWiseChart');
    if (memberWiseChartEl && memberWiseData.length > 0) {
        const total = memberWiseData.reduce((sum, item) => sum + item.amount, 0);

        const memberWiseOptions = {
            series: memberWiseData.map(item => item.amount),
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
            labels: memberWiseData.map(item => item.member_name),
            colors: memberWiseData.map((_, idx) => palette[idx % palette.length]),
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
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + percentage + '%)';
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

        chartInstances.memberWise = new ApexCharts(memberWiseChartEl, memberWiseOptions);
        chartInstances.memberWise.render();
    }

    // Category-wise Expenses Chart (Bar Chart)
    const categoryWiseChartEl = document.getElementById('categoryWiseChart');
    if (categoryWiseChartEl && categoryWiseData.length > 0) {
        const total = categoryWiseData.reduce((sum, item) => sum + item.amount, 0);

        const categoryWiseOptions = {
            series: [{
                name: 'Expenses',
                data: categoryWiseData.map(item => item.amount)
            }],
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
            colors: [colors.error],
            plotOptions: {
                bar: {
                    borderRadius: 12,
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: categoryWiseData.map(item => item.category_name),
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
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + percentage + '%)';
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
            }
        };

        chartInstances.categoryWise = new ApexCharts(categoryWiseChartEl, categoryWiseOptions);
        chartInstances.categoryWise.render();
    }

    // Savings Trend Chart (Line Chart)
    const savingsTrendChartEl = document.getElementById('savingsTrendChart');
    if (savingsTrendChartEl && savingsTrendData.length > 0) {
        const savingsOptions = {
            series: [{
                name: 'Savings',
                data: savingsTrendData.map(item => item.savings)
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
                categories: savingsTrendData.map(item => item.month_name),
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
                        const sign = value >= 0 ? '+' : '';
                        return sign + '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

        chartInstances.savingsTrend = new ApexCharts(savingsTrendChartEl, savingsOptions);
        chartInstances.savingsTrend.render();
    }

    // Account Balance Trends Chart (Multi-line Chart)
    const accountBalanceTrendsChartEl = document.getElementById('accountBalanceTrendsChart');
    if (accountBalanceTrendsChartEl && accountBalanceTrends.length > 0) {
        const series = accountBalanceTrends.map(account => ({
            name: account.account_name,
            data: account.balances.map(b => b.balance)
        }));

        const categories = accountBalanceTrends[0]?.balances.map(b => b.month_name) || [];

        const accountBalanceOptions = {
            series: series,
            chart: {
                type: 'line',
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
            colors: palette,
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                categories: categories,
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
                size: 4,
                hover: {
                    size: 6
                }
            }
        };

        chartInstances.accountBalanceTrends = new ApexCharts(accountBalanceTrendsChartEl, accountBalanceOptions);
        chartInstances.accountBalanceTrends.render();
    }

    // Income Sources Breakdown Chart (Donut Chart)
    const incomeSourcesChartEl = document.getElementById('incomeSourcesChart');
    if (incomeSourcesChartEl && incomeSourcesData.length > 0) {
        const total = incomeSourcesData.reduce((sum, item) => sum + item.amount, 0);

        const incomeSourcesOptions = {
            series: incomeSourcesData.map(item => item.amount),
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
            labels: incomeSourcesData.map(item => item.source_name),
            colors: incomeSourcesData.map((_, idx) => palette[idx % palette.length]),
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
                        return '₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + percentage + '%)';
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

        chartInstances.incomeSources = new ApexCharts(incomeSourcesChartEl, incomeSourcesOptions);
        chartInstances.incomeSources.render();
    }

    // Expense Patterns by Day of Week Chart (Bar Chart)
    const expensePatternsChartEl = document.getElementById('expensePatternsChart');
    if (expensePatternsChartEl && expensePatternsData.length > 0) {
        // Sort by day order (Monday to Sunday)
        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sortedData = expensePatternsData.sort((a, b) => {
            return dayOrder.indexOf(a.day) - dayOrder.indexOf(b.day);
        });

        const expensePatternsOptions = {
            series: [{
                name: 'Expenses',
                data: sortedData.map(item => item.amount)
            }],
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
            colors: [colors.warning],
            plotOptions: {
                bar: {
                    borderRadius: 12,
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: sortedData.map(item => item.day_short),
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
            }
        };

        chartInstances.expensePatterns = new ApexCharts(expensePatternsChartEl, expensePatternsOptions);
        chartInstances.expensePatterns.render();
    }
}
