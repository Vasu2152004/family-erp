function initFinanceCharts(monthlyData, memberWiseData, categoryWiseData) {
    // Monthly Expenses & Income Chart
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month_name),
                datasets: [
                    {
                        label: 'Income',
                        data: monthlyData.map(item => item.income),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Expenses',
                        data: monthlyData.map(item => item.expenses),
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Member-wise Spending Chart
    const memberWiseCtx = document.getElementById('memberWiseChart');
    if (memberWiseCtx && memberWiseData.length > 0) {
        new Chart(memberWiseCtx, {
            type: 'pie',
            data: {
                labels: memberWiseData.map(item => item.member_name),
                datasets: [{
                    label: 'Spending',
                    data: memberWiseData.map(item => item.amount),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }

    // Category-wise Expenses Chart
    const categoryWiseCtx = document.getElementById('categoryWiseChart');
    if (categoryWiseCtx && categoryWiseData.length > 0) {
        new Chart(categoryWiseCtx, {
            type: 'bar',
            data: {
                labels: categoryWiseData.map(item => item.category_name),
                datasets: [{
                    label: 'Expenses',
                    data: categoryWiseData.map(item => item.amount),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}




