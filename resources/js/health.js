document.addEventListener('DOMContentLoaded', () => {
    const visitChart = document.getElementById('healthVisitChart');
    if (visitChart && window.Chart) {
        const stats = JSON.parse(visitChart.dataset.visitStats || '{}');
        const labels = Object.keys(stats).map((key) => key.charAt(0).toUpperCase() + key.slice(1));
        const data = Object.values(stats);

        new Chart(visitChart, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data,
                        backgroundColor: ['#10b981', '#3b82f6', '#f97316', '#ef4444'],
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        });
    }
});

