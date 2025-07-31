/* Reports JS */
$(document).ready(function () {
    // Initialize charts on reports page
    $('.campaign-chart').each(function () {
        const ctx = this.getContext('2d');
        const labels = JSON.parse($(this).attr('data-labels') || '[]');
        const opens = JSON.parse($(this).attr('data-opens') || '[]');
        const clicks = JSON.parse($(this).attr('data-clicks') || '[]');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Opens', data: opens },
                    { label: 'Clicks', data: clicks }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: $(this).attr('data-title') }
                }
            }
        });
    });
});