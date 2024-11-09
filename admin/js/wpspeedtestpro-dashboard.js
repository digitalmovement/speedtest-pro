jQuery(document).ready(function($) {
    'use strict';

    // Chart.js default configuration
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.point.radius = 2;
    Chart.defaults.plugins.legend.display = false;

    // Storage for chart instances
    const charts = {
        performance: null,
        latency: null,
        uptime: null
    };

    // Initialize dashboard
    function initDashboard() {
        console.log('Initializing dashboard');
        loadServerPerformance();
        loadLatencyData();
        loadSSLData();
        loadUptimeData();
        loadPageSpeedData();
        setupEventHandlers();
    }

    // Server Performance Functions
    function loadServerPerformance() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_get_results',
                nonce: wpspeedtestpro_ajax.performance_nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePerformanceCard(response.data);
                    createPerformanceTrendChart(response.data);
                }
            }
        });
    }

    function updatePerformanceCard(data) {
        const latestResults = data.latest_results;
        const industryAvg = data.industry_avg;

        $('#server-performance-last-executed').text(new Date(latestResults.test_date).toLocaleString());

        // Calculate percentages compared to industry averages
        updateMetricWithPercentage('#math-performance', latestResults.math, industryAvg.math);
        updateMetricWithPercentage('#string-performance', latestResults.string, industryAvg.string);
        updateMetricWithPercentage('#mysql-performance', latestResults.mysql, industryAvg.mysql);
        updateMetricWithPercentage('#wp-performance', latestResults.wordpress_performance.time, industryAvg.wordpress_performance.time);
    }

    function updateMetricWithPercentage(selector, value, benchmark) {
        const percentage = ((benchmark - value) / benchmark * 100).toFixed(1);
        const isGood = percentage > 0;
        $(selector)
            .text(`${percentage}% ${isGood ? 'faster' : 'slower'}`)
            .removeClass('good warning poor')
            .addClass(getPerformanceClass(percentage));
    }

    function createPerformanceTrendChart(data) {
        const ctx = document.getElementById('performance-trend-chart').getContext('2d');
        
        if (charts.performance) {
            charts.performance.destroy();
        }

        charts.performance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.math.map(item => new Date(item.test_date).toLocaleDateString()),
                datasets: [{
                    data: data.math.map(item => item.math),
                    borderColor: '#2271b1',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Performance: ${context.parsed.y.toFixed(3)}s`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Latency Functions
    function loadLatencyData() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_results_for_time_range',
                time_range: '24_hours',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateLatencyCard(response.data);
                    createLatencyTrendChart(response.data);
                }
            }
        });
    }

    function updateLatencyCard(data) {
        const selectedRegion = wpspeedtestpro_ajax.selected_region;
        const regionData = data.find(item => item.region_name === selectedRegion) || data[0];

        if (regionData) {
            $('#selected-region').text(regionData.region_name);
            $('#current-latency').text(`${regionData.latency} ms`)
                .addClass(getLatencyClass(regionData.latency));
            $('#fastest-latency').text(`${regionData.fastest_latency} ms`);
            $('#slowest-latency').text(`${regionData.slowest_latency} ms`);
            $('#latency-last-updated').text(new Date(regionData.test_time).toLocaleString());
        }
    }

    function createLatencyTrendChart(data) {
        const selectedRegion = wpspeedtestpro_ajax.selected_region;
        const regionData = data.filter(item => item.region_name === selectedRegion);

        const ctx = document.getElementById('latency-trend-chart').getContext('2d');
        
        if (charts.latency) {
            charts.latency.destroy();
        }

        charts.latency = new Chart(ctx, {
            type: 'line',
            data: {
                labels: regionData.map(item => new Date(item.test_time).toLocaleDateString()),
                datasets: [{
                    data: regionData.map(item => item.latency),
                    borderColor: '#2271b1',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Latency: ${context.parsed.y.toFixed(1)} ms`;
                            }
                        }
                    }
                }
            }
        });
    }

    // SSL Functions
    function loadSSLData() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_ssl_test_status',
                nonce: wpspeedtestpro_ajax.ssl_nonce
            },
            success: function(response) {
                if (response.success) {
                    updateSSLCard(response.data);
                }
            }
        });
    }

    function updateSSLCard(data) {
        if (data.status === 'completed' && data.data) {
            const grade = data.data.endpoints[0].grade;
            $('#ssl-grade').text(grade).addClass(getSSLGradeClass(grade));
            $('#ssl-last-checked').text(new Date().toLocaleString());
            
            const cert = data.data.certs[0];
            const expiryDate = new Date(cert.notAfter);
            $('#ssl-expiry').text(expiryDate.toLocaleDateString());
            $('#ssl-protocol').text(data.data.endpoints[0].details.protocols[0].name + ' ' + 
                                  data.data.endpoints[0].details.protocols[0].version);
        }
    }

    // Uptime Functions
    function loadUptimeData() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_uptimerobot_get_monitor_data',
                nonce: wpspeedtestpro_ajax.uptime_nonce
            },
            success: function(response) {
                if (response.success) {
                    updateUptimeCard(response.data);
                    createUptimeTrendChart(response.data);
                }
            }
        });
    }

    function updateUptimeCard(data) {
        const pingMonitor = data.find(m => m.friendly_name.includes('Ping'));
        const cronMonitor = data.find(m => m.friendly_name.includes('Cron'));

        if (pingMonitor) {
            $('#server-uptime').text(formatUptime(pingMonitor.custom_uptime_ratio) + '%')
                .addClass(getUptimeClass(pingMonitor.custom_uptime_ratio));
            $('#avg-ping-time').text(pingMonitor.average_response_time + ' ms');
        }

        if (cronMonitor) {
            $('#cron-uptime').text(formatUptime(cronMonitor.custom_uptime_ratio) + '%')
                .addClass(getUptimeClass(cronMonitor.custom_uptime_ratio));
            $('#avg-cron-time').text(cronMonitor.average_response_time + ' ms');
        }
    }

    function createUptimeTrendChart(data) {
        const ctx = document.getElementById('uptime-trend-chart').getContext('2d');
        
        if (charts.uptime) {
            charts.uptime.destroy();
        }

        // Process data for chart
        const pingData = data.find(m => m.friendly_name.includes('Ping'));
        const responseTimes = pingData ? pingData.response_times.slice(-24) : [];

        charts.uptime = new Chart(ctx, {
            type: 'line',
            data: {
                labels: responseTimes.map(item => new Date(item.datetime * 1000).toLocaleTimeString()),
                datasets: [{
                    data: responseTimes.map(item => item.value),
                    borderColor: '#2271b1',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Page Speed Functions
    function loadPageSpeedData() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'speedvitals_probe_for_updates',
                nonce: wpspeedtestpro_ajax.pagespeed_nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePageSpeedCard(response.data);
                }
            }
        });
    }

    function updatePageSpeedCard(data) {
        if (data.updated_tests && data.updated_tests.length > 0) {
            const latestTest = data.updated_tests[0];
            $('#performance-score').text(latestTest.metrics.performance_score)
                .addClass(getPerformanceScoreClass(latestTest.metrics.performance_score));
            $('#fcp-value').text((latestTest.metrics.first_contentful_paint / 1000).toFixed(2) + 's');
            $('#lcp-value').text((latestTest.metrics.largest_contentful_paint / 1000).toFixed(2) + 's');
            $('#pagespeed-last-tested').text(new Date(latestTest.created_at).toLocaleString());
        }
    }

    // Event Handlers
    function setupEventHandlers() {
        $('#test-latency').on('click', function() {
            $(this).prop('disabled', true);
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_run_once_test',
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function() {
                    setTimeout(loadLatencyData, 30000);
                    $('#test-latency').prop('disabled', false);
                }
            });
        });

        $('#test-ssl').on('click', function() {
            $(this).prop('disabled', true);
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'start_ssl_test',
                    nonce: wpspeedtestpro_ajax.ssl_nonce
                },
                success: function() {
                    setTimeout(loadSSLData, 30000);
                    $('#test-ssl').prop('disabled', false);
                }
            });
        });

        $('#test-performance').on('click', function() {
            $(this).prop('disabled', true);
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_performance_run_test',
                    nonce: wpspeedtestpro_ajax.performance_nonce
                },
                success: function() {
                    setTimeout(loadServerPerformance, 30000);
                    $('#test-performance').prop('disabled', false);
                }
            });
        });

        $('#test-pagespeed').on('click', function() {
            $(this).prop('disabled', true);
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'speedvitals_run_test',
                    nonce: wpspeedtestpro_ajax.pagespeed_nonce,
                    url: window.location.origin,
                    device: 'desktop',
                    location: 'us'
                },
                success: function() {
                    setTimeout(loadPageSpeedData, 30000);
                    $('#test-pagespeed').prop('disabled', false);
                }
            });
        });
    }

    // Utility Functions
    function getPerformanceClass(percentage) {
        if (percentage > 20) return 'good';
        if (percentage > -10) return 'warning';
        return 'poor';
    }

    function getLatencyClass(latency) {
        if (latency < 100) return 'good';
        if (latency < 300) return 'warning';
        return 'poor';
    }

    function getSSLGradeClass(grade) {
        if (grade === 'A+' || grade === 'A') return 'good';
        if (grade === 'B') return 'warning';
        return 'poor';
    }

    function getUptimeClass(uptime) {
        if (uptime >= 99.9) return 'good';
        if (uptime >= 99) return 'warning';
        return 'poor';
    }

    function getPerformanceScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'warning';
        return 'poor';
    }

    function formatUptime(uptime) {
        return parseFloat(uptime).toFixed(2);
    }

    // Initialize the dashboard
    initDashboard();
});
