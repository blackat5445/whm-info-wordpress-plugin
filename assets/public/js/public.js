(function($) {
    'use strict';

    let serverChart, externalChart;

    /**
     * Render chart with ACTUAL status data from the 15-min checker
     */
    function renderChart(canvasId, historyData, timeRange, settings) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        // historyData = { 'username1': [{status: 'operational', timestamp: 123456}, ...], 'username2': [...] }
        const allSiteHistories = Object.values(historyData);
        
        if (allSiteHistories.length === 0) {
            ctx.parentElement.innerHTML = '<p class="whmin-no-data">No monitoring data available yet. First check runs within 15 minutes.</p>';
            return null;
        }

        const endDate = new Date();
        const startDate = new Date();
        let groupBy = 'day';

        // Determine time range
        if (timeRange.endsWith('h')) {
            const hours = parseInt(timeRange.replace('h', ''), 10);
            startDate.setHours(endDate.getHours() - hours);
            groupBy = 'hour';
        } else if (timeRange.endsWith('d')) {
            const days = parseInt(timeRange.replace('d', ''), 10);
            startDate.setDate(endDate.getDate() - days);
            groupBy = (days <= 7) ? 'hour' : 'day';
        } else if (timeRange.endsWith('m')) {
            const months = parseInt(timeRange.replace('m', ''), 10);
            startDate.setMonth(endDate.getMonth() - months);
            groupBy = 'day';
        }

        // Create time slots
        const timeSlots = {};
        const labels = [];
        const startTimestamp = Math.floor(startDate.getTime() / 1000);
        const endTimestamp = Math.floor(endDate.getTime() / 1000);

        if (groupBy === 'hour') {
            for (let d = new Date(startDate); d <= endDate; d.setHours(d.getHours() + 1)) {
                const key = `${d.toISOString().split('T')[0]} ${String(d.getHours()).padStart(2, '0')}:00`;
                labels.push(key);
                timeSlots[key] = { operational: 0, total: 0 };
            }
        } else {
            for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                const key = d.toISOString().split('T')[0];
                labels.push(key);
                timeSlots[key] = { operational: 0, total: 0 };
            }
        }

        // Process status history from the checker
        allSiteHistories.forEach(siteHistory => {
            if (!Array.isArray(siteHistory)) return;
            
            siteHistory.forEach(entry => {
                const entryTimestamp = entry.timestamp;
                
                if (entryTimestamp < startTimestamp || entryTimestamp > endTimestamp) {
                    return;
                }
                
                const entryDate = new Date(entryTimestamp * 1000);
                let key;
                
                if (groupBy === 'hour') {
                    key = `${entryDate.toISOString().split('T')[0]} ${String(entryDate.getHours()).padStart(2, '0')}:00`;
                } else {
                    key = entryDate.toISOString().split('T')[0];
                }
                
                if (timeSlots[key]) {
                    timeSlots[key].total++;
                    if (entry.status === 'operational') {
                        timeSlots[key].operational++;
                    }
                }
            });
        });

        // Calculate uptime percentages
        const dataPoints = labels.map(key => {
            const slotData = timeSlots[key];
            const percentage = slotData.total > 0 ? (slotData.operational / slotData.total) * 100 : 100;
            return { x: key, y: percentage };
        });

        // Destroy old chart
        if (canvasId === 'serverHistoryChart' && serverChart) serverChart.destroy();
        if (canvasId === 'externalHistoryChart' && externalChart) externalChart.destroy();

        // Create new chart
        const newChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: dataPoints,
                    backgroundColor: dataPoints.map(p => p.y < 99.9 ? '#ffc107' : settings.bar_color),
                    hoverBackgroundColor: dataPoints.map(p => p.y < 99.9 ? '#ffca2c' : settings.bar_color),
                    borderWidth: 0,
                    borderRadius: 3,
                    barPercentage: 1.0,
                    categoryPercentage: 0.9,
                }]
            },
            options: {
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        max: 100, 
                        ticks: { 
                            callback: v => v + '%',
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }, 
                    x: { 
                        display: false 
                    } 
                },
                plugins: { 
                    legend: { display: false }, 
                    tooltip: { 
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        cornerRadius: 6,
                        callbacks: { 
                            title: (items) => items[0].label, 
                            label: (c) => `Uptime: ${c.raw.y.toFixed(2)}%` 
                        } 
                    } 
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        return newChart;
    }

    /**
     * Animate counters
     */
    function animateCounters() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = +counter.getAttribute('data-target');
                    if (target === 0) return;
                    
                    let current = 0;
                    const increment = target / 100;

                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            counter.innerText = Math.ceil(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.innerText = target;
                        }
                    };
                    updateCounter();
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.animated-counter').forEach(counter => observer.observe(counter));
    }

    /**
     * Apply custom button colors
     */
    function applyCustomStyles(settings) {
        let css = '';
        css += `
            [data-chart-target="serverHistoryChart"] .whmin-range-btn {
                background-color: ${settings.server_graph_button_bg}33;
                color: ${settings.server_graph_button_bg};
            }
            [data-chart-target="serverHistoryChart"] .whmin-range-btn.active,
            [data-chart-target="serverHistoryChart"] .whmin-range-btn:hover {
                background-color: ${settings.server_graph_button_bg};
                color: ${settings.server_graph_button_text};
            }
            [data-chart-target="externalHistoryChart"] .whmin-range-btn {
                background-color: ${settings.managed_graph_button_bg}33;
                color: ${settings.managed_graph_button_bg};
            }
            [data-chart-target="externalHistoryChart"] .whmin-range-btn.active,
            [data-chart-target="externalHistoryChart"] .whmin-range-btn:hover {
                background-color: ${settings.managed_graph_button_bg};
                color: ${settings.managed_graph_button_text};
            }
        `;
        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = css;
        document.head.appendChild(styleSheet);
    }

    $(document).ready(function() {
        if (typeof WHMIN_Public_Data === 'undefined') {
            console.warn('WHMIN_Public_Data not loaded');
            return;
        }

        const { history, settings } = WHMIN_Public_Data;
        
        console.log('Status History Data:', history); // Debug
        
        applyCustomStyles(settings);
        
        // Render charts with REAL status data
        if (settings.enable_server_graph) {
            const serverSettings = { bar_color: settings.server_graph_bar_color };
            serverChart = renderChart('serverHistoryChart', history.direct || {}, '1m', serverSettings);
        }
        
        if (settings.enable_managed_graph) {
            const managedSettings = { bar_color: settings.managed_graph_bar_color };
            externalChart = renderChart('externalHistoryChart', history.indirect || {}, '1m', managedSettings);
        }
        
        // Handle time range button clicks
        $('.whmin-graph-controls').on('click', '.whmin-range-btn', function() {
            const $this = $(this);
            const range = $this.data('range');
            const chartTargetId = $this.parent().data('chart-target');

            $this.siblings().removeClass('active');
            $this.addClass('active');

            if (chartTargetId === 'serverHistoryChart') {
                const serverSettings = { bar_color: settings.server_graph_bar_color };
                serverChart = renderChart(chartTargetId, history.direct || {}, range, serverSettings);
            } else if (chartTargetId === 'externalHistoryChart') {
                const managedSettings = { bar_color: settings.managed_graph_bar_color };
                externalChart = renderChart(chartTargetId, history.indirect || {}, range, managedSettings);
            }
        });

        animateCounters();
    });

})(jQuery);