(function($) {
    'use strict';

    let serverChart, externalChart; // Store chart instances

    // The main function to render or update a chart
    function renderChart(canvasId, historyData, timeRangeMonths) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const allSiteHistories = Object.values(historyData);
        if (allSiteHistories.length === 0) {
            // Handle case with no sites to monitor
            ctx.parentElement.innerHTML = '<p class="whmin-no-data">No monitoring data available for this category.</p>';
            return null;
        }

        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - timeRangeMonths);
        
        const days = {};
        const dayLabels = [];

        // Pre-fill last N days
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            const dayKey = d.toISOString().split('T')[0];
            dayLabels.push(dayKey);
            days[dayKey] = { operational: 0, total: 0 };
        }

        // Aggregate data from all sites in this category
        allSiteHistories.forEach(siteHistory => {
            siteHistory.forEach(entry => {
                const entryDate = new Date(entry.timestamp * 1000);
                if (entryDate >= startDate && entryDate <= endDate) {
                    const dayKey = entryDate.toISOString().split('T')[0];
                    if(days[dayKey]) {
                        if (entry.status === 'operational') {
                            days[dayKey].operational++;
                        }
                        days[dayKey].total++;
                    }
                }
            });
        });

        const dataPoints = dayLabels.map(day => {
            const dayData = days[day];
            // If total checks for a day is 0, assume 100% uptime, otherwise calculate
            const percentage = dayData.total > 0 ? (dayData.operational / dayData.total) * 100 : 100;
            return {
                x: day,
                y: percentage,
                color: percentage < 99.9 ? '#ffc107' : '#198754'
            };
        });

        // Destroy previous chart instance if it exists
        if (canvasId === 'serverHistoryChart' && serverChart) serverChart.destroy();
        if (canvasId === 'externalHistoryChart' && externalChart) externalChart.destroy();

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dayLabels,
                datasets: [{
                    data: dataPoints,
                    backgroundColor: dataPoints.map(p => p.color),
                    borderWidth: 0,
                    borderRadius: 3,
                    barPercentage: 1.0,
                    categoryPercentage: 0.9,
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }, x: { display: false } },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `Uptime: ${c.raw.y.toFixed(2)}%` } } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    $(document).ready(function() {
        const history = WHMIN_Public_Data.history || { direct: {}, indirect: {} };
        
        // Initial render of both charts with 1-month data
        serverChart = renderChart('serverHistoryChart', history.direct || {}, 1);
        externalChart = renderChart('externalHistoryChart', history.indirect || {}, 1);
        
        // Handle clicks on the time range buttons
        $('.whmin-graph-controls').on('click', '.whmin-range-btn', function() {
            const $this = $(this);
            const range = parseInt($this.data('range'), 10);
            const chartTargetId = $this.parent().data('chart-target');

            // Update active button state
            $this.siblings().removeClass('active');
            $this.addClass('active');

            // Re-render the correct chart
            if (chartTargetId === 'serverHistoryChart') {
                serverChart = renderChart(chartTargetId, history.direct || {}, range);
            } else if (chartTargetId === 'externalHistoryChart') {
                externalChart = renderChart(chartTargetId, history.indirect || {}, range);
            }
        });
    });

})(jQuery);