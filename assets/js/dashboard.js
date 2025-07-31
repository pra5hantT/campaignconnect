/* Dashboard JS */
$(document).ready(function () {
    // Example: Chart for opens and clicks over last 7 days
    if ($('#opensClicksChart').length) {
        const ctx = document.getElementById('opensClicksChart').getContext('2d');
        const data = {
            labels: JSON.parse($('#opensClicksChart').attr('data-labels') || '[]'),
            datasets: [
                {
                    label: 'Opens',
                    data: JSON.parse($('#opensClicksChart').attr('data-opens') || '[]'),
                },
                {
                    label: 'Clicks',
                    data: JSON.parse($('#opensClicksChart').attr('data-clicks') || '[]'),
                },
            ],
        };
        new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Email Engagement (Last 7 Days)'
                    }
                },
            },
        });
    }
});