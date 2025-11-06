/**
 * WHM Monitor - Private Dashboard JavaScript Extension
 * Extends public dashboard JS with private-specific features
 */

(function($) {
    'use strict';

    const WHMINPrivateDashboard = {
        charts: {},
        
        /**
         * Initialize private dashboard features
         */
        init: function() {
            this.initSkeletons();
            this.initProgressBars();
            this.initCounterAnimations();
            this.initPrivateCharts();
            this.initServiceBadges();
        },

        /**
         * Handle skeleton loader for the private dashboard
         * - The template adds "whmin-loading" to the main wrapper
         * - Once JS initialises, we remove it so real content appears
         */
        initSkeletons: function() {
            var $page = $('.whmin-private-dashboard-page');
            if (!$page.length) return;

            // Small delay so skeleton is visible briefly on fast loads,
            // but disappears quickly once everything is ready.
            setTimeout(function() {
                $page.removeClass('whmin-loading');
            }, 300);
        },

        /**
         * Animate progress bars
         */
        initProgressBars: function() {
            $('.whmin-progress-fill').each(function() {
                const $bar = $(this);
                const percentage = parseFloat($bar.data('percentage'));
                
                // Animate on scroll into view
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            setTimeout(() => {
                                $bar.css('width', Math.min(percentage, 100) + '%');
                            }, 100);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe($bar[0]);
            });
        },

        /**
         * Animate counter numbers
         */
        initCounterAnimations: function() {
            $('.animated-counter, .whmin-stat-big-inline').each(function() {
                const $counter = $(this);
                const target = parseInt($counter.text()) || parseInt($counter.data('target'), 10);
                
                if (target === 0 || isNaN(target)) return;
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !$counter.hasClass('counted')) {
                            $counter.addClass('counted');
                            WHMINPrivateDashboard.animateCounter($counter, target);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe($counter[0]);
            });
        },

        /**
         * Animate counter to target value
         */
        animateCounter: function($element, target) {
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            const stepDuration = duration / steps;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    $element.text(target);
                    clearInterval(timer);
                } else {
                    $element.text(Math.floor(current));
                }
            }, stepDuration);
        },

        /**
         * Initialize private dashboard charts (System Load and Packages)
         */
        initPrivateCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            // Set global chart defaults to match public style
            Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            Chart.defaults.color = '#6c757d';
            
            this.initSystemLoadChart();
            this.initPackagesChart();
        },

        /**
         * Initialize System Load Average Chart
         */
        initSystemLoadChart: function() {
            const canvas = document.getElementById('systemLoadChart');
            if (!canvas || !window.whminServerData || !window.whminServerData.systemLoad) return;

            const ctx = canvas.getContext('2d');
            const loadData = window.whminServerData.systemLoad;

            this.charts.systemLoad = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['1 Minute', '5 Minutes', '15 Minutes'],
                    datasets: [{
                        label: 'Load Average',
                        data: [
                            loadData['1min'] || 0, 
                            loadData['5min'] || 0, 
                            loadData['15min'] || 0
                        ],
                        backgroundColor: [
                            'rgba(7, 91, 99, 0.8)',
                            'rgba(25, 135, 84, 0.8)',
                            'rgba(108, 117, 125, 0.8)'
                        ],
                        borderColor: [
                            'rgb(7, 91, 99)',
                            'rgb(25, 135, 84)',
                            'rgb(108, 117, 125)'
                        ],
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return 'Load: ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Initialize Packages Distribution Chart
         */
        initPackagesChart: function() {
            const canvas = document.getElementById('packagesChart');
            if (!canvas || !window.whminServerData || !window.whminServerData.packages) return;

            const ctx = canvas.getContext('2d');
            const packages = window.whminServerData.packages;
            
            const labels = Object.keys(packages);
            const data = Object.values(packages);
            
            const colors = [
                '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', 
                '#F44336', '#00BCD4', '#FFC107', '#E91E63'
            ];

            this.charts.packages = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        },

        /**
         * Add visual indicators to service badges
         */
        initServiceBadges: function() {
            $('.whmin-service-item').each(function() {
                const $item = $(this);
                // Additional effects could go here
            });
        },

        /**
         * Refresh chart data (for future AJAX updates)
         */
        refreshCharts: function(newData) {
            if (this.charts.systemLoad && newData.systemLoad) {
                this.charts.systemLoad.data.datasets[0].data = [
                    newData.systemLoad['1min'],
                    newData.systemLoad['5min'],
                    newData.systemLoad['15min']
                ];
                this.charts.systemLoad.update();
            }
            
            if (this.charts.packages && newData.packages) {
                this.charts.packages.data.labels = Object.keys(newData.packages);
                this.charts.packages.data.datasets[0].data = Object.values(newData.packages);
                this.charts.packages.update();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.whmin-private-dashboard-page').length > 0) {
            WHMINPrivateDashboard.init();
        }
    });

    // Make it globally accessible
    window.WHMINPrivateDashboard = WHMINPrivateDashboard;
    function formatNumber(num) {
        if (isNaN(num)) return num;
        var fixed = parseFloat(num).toFixed(2);
        return fixed.replace(/\.?0+$/, '');
    }

    function applyUnitToCard(cardKey, unit) {
        var $card = $('[data-unit-card="'+cardKey+'"]');
        if (!$card.length) return;

        $card.find('[data-mb]').each(function() {
            var $el = $(this);
            var mb = parseFloat($el.data('mb'));
            var gb = parseFloat($el.data('gb'));
            var noData = (!mb && !gb && !$el.data('mb'));

            if (noData) return;

            if (unit === 'GB') {
                var val = gb ? gb : (mb / 1024);
                $el.text(formatNumber(val) + ' GB').attr('data-unit-label', 'GB');
            } else {
                $el.text(formatNumber(mb) + ' MB').attr('data-unit-label', 'MB');
            }
        });
    }

    $(document).ready(function(){
        $('.whmin-unit-toggle span').on('click', function(){
            var $btn = $(this);
            var unit = $btn.data('unit');
            var $toggle = $btn.closest('.whmin-unit-toggle');
            var target = $toggle.data('unit-target');

            $toggle.find('span').removeClass('active');
            $btn.addClass('active');

            applyUnitToCard(target, unit);
        });
    });
})(jQuery);
