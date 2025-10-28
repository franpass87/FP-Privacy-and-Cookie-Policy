/**
 * Analytics Dashboard JavaScript
 * QUICK WIN #3: Grafici Chart.js per consent analytics
 *
 * @package FP\Privacy
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        if (typeof Chart === 'undefined' || typeof fpPrivacyAnalytics === 'undefined') {
            console.warn('Chart.js or analytics data not loaded');
            return;
        }

        var data = fpPrivacyAnalytics;

        // ===================================
        // 1. TREND CHART (Line Chart)
        // ===================================
        var trendCtx = document.getElementById('fp-consent-trend-chart');
        if (trendCtx) {
            var trendData = processTrendData(data.trend);

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [
                        {
                            label: 'Accetta Tutti',
                            data: trendData.accept_all,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Rifiuta Tutti',
                            data: trendData.reject_all,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Personalizzato',
                            data: trendData.consent,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // ===================================
        // 2. TYPE BREAKDOWN (Doughnut Chart)
        // ===================================
        var typeCtx = document.getElementById('fp-consent-type-chart');
        if (typeCtx) {
            var types = data.types || {};

            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Accetta Tutti', 'Rifiuta Tutti', 'Personalizzato'],
                    datasets: [{
                        data: [
                            types.accept_all || 0,
                            types.reject_all || 0,
                            types.consent || 0
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#ef4444',
                            '#3b82f6'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // ===================================
        // 3. CATEGORIES CHART (Bar Chart)
        // ===================================
        var categoriesCtx = document.getElementById('fp-consent-categories-chart');
        if (categoriesCtx) {
            var categories = data.categories || {};

            new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(categories).map(function(key) {
                        return key.charAt(0).toUpperCase() + key.slice(1);
                    }),
                    datasets: [{
                        label: 'Consensi Dati',
                        data: Object.values(categories),
                        backgroundColor: [
                            '#10b981',
                            '#3b82f6',
                            '#8b5cf6',
                            '#f59e0b',
                            '#ec4899'
                        ],
                        borderRadius: 8,
                        barThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Consensi: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // ===================================
        // 4. LANGUAGES CHART (Pie Chart)
        // ===================================
        var langCtx = document.getElementById('fp-consent-lang-chart');
        if (langCtx) {
            var languages = data.languages || [];

            var langLabels = languages.map(function(item) {
                return item.lang || 'Unknown';
            });

            var langData = languages.map(function(item) {
                return parseInt(item.count) || 0;
            });

            var langColors = [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ec4899',
                '#8b5cf6',
                '#06b6d4',
                '#84cc16',
                '#f97316',
                '#14b8a6',
                '#a855f7'
            ];

            new Chart(langCtx, {
                type: 'pie',
                data: {
                    labels: langLabels,
                    datasets: [{
                        data: langData,
                        backgroundColor: langColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 12,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    /**
     * Process trend data per giorno
     */
    function processTrendData(trend) {
        var dates = {};
        var labels = [];

        // Raggruppa per data
        trend.forEach(function(item) {
            var date = item.date;
            if (!dates[date]) {
                dates[date] = {
                    accept_all: 0,
                    reject_all: 0,
                    consent: 0
                };
            }

            var count = parseInt(item.count) || 0;
            if (item.event === 'accept_all') {
                dates[date].accept_all = count;
            } else if (item.event === 'reject_all') {
                dates[date].reject_all = count;
            } else if (item.event === 'consent') {
                dates[date].consent = count;
            }
        });

        // Converti in array per Chart.js
        var sortedDates = Object.keys(dates).sort();
        var accept_all = [];
        var reject_all = [];
        var consent = [];

        sortedDates.forEach(function(date) {
            labels.push(formatDate(date));
            accept_all.push(dates[date].accept_all);
            reject_all.push(dates[date].reject_all);
            consent.push(dates[date].consent);
        });

        return {
            labels: labels,
            accept_all: accept_all,
            reject_all: reject_all,
            consent: consent
        };
    }

    /**
     * Format date for display
     */
    function formatDate(dateString) {
        var date = new Date(dateString);
        var day = date.getDate();
        var month = date.getMonth() + 1;
        return day + '/' + month;
    }

})(jQuery);

