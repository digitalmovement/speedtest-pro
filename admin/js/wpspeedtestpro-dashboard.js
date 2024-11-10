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

        //$('#server-performance-last-executed').text(latestResults.test_date);

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
                time_range: '90_days',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success && Array.isArray(response.data) && response.data.length > 0) {
                    updateLatencyCard(response.data);
                    createLatencyTrendChart(response.data);
                } else {
                    displayNoLatencyData();
                }
            },
            error: function() {
                console.error('Failed to load latency data');
                displayNoLatencyData();
            }
        });
    }
    
    function displayNoLatencyData() {
        // Clear any existing classes
        $('#selected-region, #current-latency, #fastest-latency, #slowest-latency, #latency-last-updated')
            .removeClass('good warning poor');
    
        // Update card with "No data" messages
        $('#selected-region').text('No region data available');
        $('#current-latency').text('No data available')
            .addClass('no-data');
        $('#fastest-latency').text('No data available');
        $('#slowest-latency').text('No data available');
        $('#latency-last-updated').text('Never');
    
        // Clear the chart if it exists
        if (charts.latency) {
            charts.latency.destroy();
            charts.latency = null;
        }
    
        // Add a "no data" message to the chart container
        const chartContainer = document.getElementById('latency-trend-chart').parentElement;
        const existingMessage = chartContainer.querySelector('.no-data-message');
        
        if (!existingMessage) {
            const message = document.createElement('div');
            message.className = 'no-data-message';
            message.innerHTML = '<p>No latency data available. Run a test to see results.</p>';
            chartContainer.appendChild(message);
        }
    }
    
    function updateLatencyCard(data) {
        const selectedRegion = wpspeedtestpro_ajax.selected_region;
        const regionData = data.find(item => item.region_name === selectedRegion) || data[0];
    
        if (regionData) {
            // Remove any existing no-data messages
            const chartContainer = document.getElementById('latency-trend-chart').parentElement;
            const existingMessage = chartContainer.querySelector('.no-data-message');
            if (existingMessage) {
                existingMessage.remove();
            }
    
            // Clear any existing classes
            $('#selected-region, #current-latency, #fastest-latency, #slowest-latency, #latency-last-updated')
                .removeClass('good warning poor no-data');
    
            // Update with actual data
            $('#selected-region').text(regionData.region_name);
            $('#current-latency')
                .text(`${regionData.latency} ms`)
                .addClass(getLatencyClass(regionData.latency));
            $('#fastest-latency').text(`${regionData.fastest_latency} ms`);
            $('#slowest-latency').text(`${regionData.slowest_latency} ms`);
            $('#latency-last-updated').text(new Date(regionData.test_time).toLocaleString());
        } else {
            displayNoLatencyData();
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
                if (response.success && response.data) {
                    updateSSLCard(response.data);
                } else {
                    displayNoSSLData(response.data || 'No SSL data available');
                }
            },
            error: function() {
                displayNoSSLData('Error loading SSL data');
            }
        });
    }
    
    function displayNoSSLData(message) {
        // Clear any existing classes
        $('#ssl-grade, #ssl-last-checked, #ssl-expiry, #ssl-protocol')
            .removeClass('good warning poor');
    
        // Update card with "No data" messages
        $('#ssl-grade')
            .text('-')
            .addClass('no-data')
            .attr('title', message);
        
        $('#ssl-last-checked').text('Never tested')
            .addClass('no-data');
        
        $('#ssl-expiry').text('No data available')
            .addClass('no-data');
        
        $('#ssl-protocol').text('No data available')
            .addClass('no-data');
    
        // Add an info message to the card
        const $sslCard = $('#ssl-card .card-content');
        const existingMessage = $sslCard.find('.no-data-message');
        
        if (!existingMessage.length) {
            $sslCard.append(
                $('<div/>', {
                    class: 'no-data-message',
                    html: `<p>${message}</p><p>Run an SSL test to see results.</p>`
                })
            );
        } else {
            existingMessage.find('p:first').text(message);
        }
    }
    
    function updateSSLCard(data) {
        // Remove any existing no-data messages
        $('.no-data-message').remove();
        
        // Remove no-data class from all elements
        $('#ssl-grade, #ssl-last-checked, #ssl-expiry, #ssl-protocol')
            .removeClass('no-data');
    
        if (data.status === 'completed' && data.data && data.data.endpoints && data.data.endpoints[0]) {
            const endpoint = data.data.endpoints[0];
            const grade = endpoint.grade;
    
            // Update SSL Grade
            $('#ssl-grade')
                .text(grade)
                .removeClass('good warning poor')
                .addClass(getSSLGradeClass(grade))
                .removeAttr('title');
    
            // Update Last Checked
            $('#ssl-last-checked')
                .text(new Date().toLocaleString());
    
            // Update Certificate Details
            if (data.data.certs && data.data.certs[0]) {
                const cert = data.data.certs[0];
                const expiryDate = new Date(cert.notAfter);
                $('#ssl-expiry').text(expiryDate.toLocaleDateString());
            } else {
                $('#ssl-expiry').text('Certificate data not available')
                    .addClass('no-data');
            }
    
            // Update Protocol
            if (endpoint.details && endpoint.details.protocols && endpoint.details.protocols[0]) {
                const protocol = endpoint.details.protocols[0];
                $('#ssl-protocol').text(protocol.name + ' ' + protocol.version);
            } else {
                $('#ssl-protocol').text('Protocol data not available')
                    .addClass('no-data');
            }
    
        } else if (data.status === 'in_progress') {
            // Handle in-progress state
            displaySSLInProgress();
        } else {
            // Handle invalid or incomplete data
            displayNoSSLData('Invalid or incomplete SSL data received');
        }
    }
    
    function displaySSLInProgress() {
        // Clear any existing classes and messages
        $('.no-data-message').remove();
        $('#ssl-grade, #ssl-last-checked, #ssl-expiry, #ssl-protocol')
            .removeClass('good warning poor no-data');
    
        // Add loading indicators
        $('#ssl-grade').text('...')
            .addClass('loading');
        $('#ssl-last-checked').text('Test in progress...')
            .addClass('loading');
        $('#ssl-expiry').text('Checking...')
            .addClass('loading');
        $('#ssl-protocol').text('Checking...')
            .addClass('loading');
    
        // Add progress message
        const $sslCard = $('#ssl-card .card-content');
        $sslCard.append(
            $('<div/>', {
                class: 'test-progress-message',
                html: '<p>SSL test in progress. This may take a few minutes...</p>'
            })
        );
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
    
        function findLastGoodStatus(monitor) {
            if (!monitor || !monitor.logs) return null;
            
            // Find the last log with "OK" status
            return monitor.logs.find(log => 
                log.type === 2 && // Type 2 indicates "Up" status
                log.reason && 
                log.reason.detail === "OK"
            );
        }
    
        function formatDuration(seconds) {
            // Convert seconds to days, hours, minutes
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
    
            // Build readable string
            const parts = [];
            if (days > 0) parts.push(`${days} day${days !== 1 ? 's' : ''}`);
            if (hours > 0) parts.push(`${hours} hour${hours !== 1 ? 's' : ''}`);
            if (minutes > 0) parts.push(`${minutes} minute${minutes !== 1 ? 's' : ''}`);
    
            // Handle case where duration is less than a minute
            if (parts.length === 0) {
                return 'Less than a minute';
            }
    
            return parts.join(', ');
        }
    
        if (pingMonitor) {
            const lastGoodPing = findLastGoodStatus(pingMonitor);
            if (lastGoodPing) {
                $('#server-uptime')
                    .text(formatDuration(lastGoodPing.duration))
                    .removeClass('no-data')
                    .addClass('good');
            } else {
                $('#server-uptime')
                    .text('No uptime data available')
                    .removeClass('good warning poor')
                    .addClass('no-data');
            }
    
            // Still include average ping time if available
            if (pingMonitor.average_response_time) {
                $('#avg-ping-time')
                    .text(pingMonitor.average_response_time + ' ms')
                    .removeClass('no-data');
            } else {
                $('#avg-ping-time')
                    .text('No data')
                    .addClass('no-data');
            }
        }
    
        if (cronMonitor) {
            const lastGoodCron = findLastGoodStatus(cronMonitor);
            if (lastGoodCron) {
                $('#cron-uptime')
                    .text(formatDuration(lastGoodCron.duration))
                    .removeClass('no-data')
                    .addClass('good');
            } else {
                $('#cron-uptime')
                    .text('No uptime data available')
                    .removeClass('good warning poor')
                    .addClass('no-data');
            }
    
            // Still include average cron time if available
            if (cronMonitor.average_response_time) {
                $('#avg-cron-time')
                    .text(cronMonitor.average_response_time + ' ms')
                    .removeClass('no-data');
            } else {
                $('#avg-cron-time')
                    .text('No data')
                    .addClass('no-data');
            }
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
                action: 'wpspeedtestpro_get_latest_pagespeed',
                nonce: wpspeedtestpro_ajax.pagespeed_nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updatePageSpeedCard(response.data);
                } else {
                    displayNoPageSpeedData();
                }
            },
            error: function() {
                displayNoPageSpeedData('Error loading Page Speed data');
            }
        });
    }
    
    function displayNoPageSpeedData(message = 'No Page Speed data available') {
        // Remove any existing loading states
        $('.pagespeed-metrics .loading').removeClass('loading');
        
        // Clear any existing classes
        $('#performance-score, #fcp-value, #lcp-value, #pagespeed-last-tested')
            .removeClass('good warning poor');
    
        // Update metrics with no data indicators
        $('#performance-score')
            .text('--')
            .addClass('no-data')
            .attr('title', message);
        
        $('#fcp-value')
            .text('--')
            .addClass('no-data');
        
        $('#lcp-value')
            .text('--')
            .addClass('no-data');
        
        $('#pagespeed-last-tested')
            .text('Never tested')
            .addClass('no-data');
    
        // Show message in card
        const $pageSpeedCard = $('#pagespeed-card .card-content');
        const existingMessage = $pageSpeedCard.find('.no-data-message');
        
        if (!existingMessage.length) {
            $pageSpeedCard.append(
                $('<div/>', {
                    class: 'no-data-message',
                    html: `
                        <p>${message}</p>
                        <p class="no-data-hint">Click "Test Page Speed" to analyze your website's performance.</p>
                        <div class="metric-hints">
                            <div class="hint-item">
                                <span class="hint-label">Performance Score:</span> Overall score (0-100)
                            </div>
                            <div class="hint-item">
                                <span class="hint-label">FCP:</span> First Contentful Paint
                            </div>
                            <div class="hint-item">
                                <span class="hint-label">LCP:</span> Largest Contentful Paint
                            </div>
                        </div>
                    `
                })
            );
        }
    }
    
    function updatePageSpeedCard(result) {
        // Remove any existing no-data messages and states
        $('.no-data-message').remove();
        $('.pagespeed-metrics .no-data').removeClass('no-data');
    
        if (!result || !result.status) {
            displayNoPageSpeedData();
            return;
        }
    
        // Update Performance Score
        if (result.performance_score !== null && result.performance_score !== undefined) {
            $('#performance-score')
                .text(result.performance_score)
                .removeClass('good warning poor no-data')
                .addClass(getPerformanceScoreClass(result.performance_score))
                .attr('title', `Performance Score: ${result.performance_score}/100`);
        } else {
            $('#performance-score')
                .text('--')
                .addClass('no-data')
                .attr('title', 'Performance score not available');
        }
    
        // Update First Contentful Paint
        if (result.first_contentful_paint) {
            const fcpSeconds = (result.first_contentful_paint / 1000).toFixed(2);
            $('#fcp-value')
                .text(fcpSeconds + 's')
                .removeClass('no-data')
                .addClass(getFCPClass(fcpSeconds));
        } else {
            $('#fcp-value')
                .text('--')
                .addClass('no-data');
        }
    
        // Update Largest Contentful Paint
        if (result.largest_contentful_paint) {
            const lcpSeconds = (result.largest_contentful_paint / 1000).toFixed(2);
            $('#lcp-value')
                .text(lcpSeconds + 's')
                .removeClass('no-data')
                .addClass(getLCPClass(lcpSeconds));
        } else {
            $('#lcp-value')
                .text('--')
                .addClass('no-data');
        }
    
        // Update Last Tested Time
        if (result.test_date) {
            $('#pagespeed-last-tested')
                .text(new Date(result.test_date).toLocaleString())
                .removeClass('no-data');
        } else {
            $('#pagespeed-last-tested')
                .text('Date not available')
                .addClass('no-data');
        }
    
        // Add status indicator if test is pending
        if (result.status === 'pending' || result.status === 'processing') {
            $('.pagespeed-metrics').addClass('loading');
            $('#pagespeed-card .card-content').append(
                $('<div/>', {
                    class: 'test-status-message',
                    html: '<p><i class="fas fa-spinner fa-spin"></i> Test in progress...</p>'
                })
            );
        }
    }
    
    // Helper functions for metric classifications
    function getPerformanceScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'warning';
        return 'poor';
    }
    
    function getFCPClass(seconds) {
        const value = parseFloat(seconds);
        if (value <= 1.8) return 'good';
        if (value <= 3) return 'warning';
        return 'poor';
    }
    
    function getLCPClass(seconds) {
        const value = parseFloat(seconds);
        if (value <= 2.5) return 'good';
        if (value <= 4) return 'warning';
        return 'poor';
    }
    
    // Helper functions for metric classifications
    function getFCPClass(seconds) {
        const value = parseFloat(seconds);
        if (value <= 1.8) return 'good';
        if (value <= 3) return 'warning';
        return 'poor';
    }
    
    function getLCPClass(seconds) {
        const value = parseFloat(seconds);
        if (value <= 2.5) return 'good';
        if (value <= 4) return 'warning';
        return 'poor';
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
