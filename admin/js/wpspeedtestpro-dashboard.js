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
        const regionData = data.filter(item => item.region === selectedRegion);

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
                action: 'wpspeedtestpro_get_ssl_data',
                nonce: wpspeedtestpro_ajax.ssl_nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Create the expected data structure
                    const sslData = {
                        success: true,
                         data: response.data // If response.data is already an object
                    };
                    updateSSLCard(sslData);
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
    
        if (data && data.data && data.data.endpoints && data.data.endpoints[0]) {
            const endpoint = data.data.endpoints[0];
            const grade = endpoint.grade || 'N/A';
            
            // Format timestamp to local date/time
            const testTime = new Date(data.data.testTime);
            const formattedTestTime = testTime.toLocaleString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
    
            // Update SSL Grade with visual indicator
            const $sslGrade = $('#ssl-grade');
            $sslGrade
                .text(grade)
                .removeClass('good warning poor')
                .addClass(getSSLGradeClass(grade));
    
            // Add tooltip for grade explanation
            let gradeExplanation = getGradeExplanation(grade);
            $sslGrade.attr('title', gradeExplanation);
    
            // Update Last Checked with formatted time
            $('#ssl-last-checked')
                .text(formattedTestTime)
                .attr('title', 'Last SSL check performed');
    
            // Update Certificate Details
            if (data.data.certs && data.data.certs[0]) {
                const cert = data.data.certs[0];
                const expiryDate = new Date(cert.notAfter);
                const today = new Date();
                const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                
                const formattedExpiry = expiryDate.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
    
                const $sslExpiry = $('#ssl-expiry');
                $sslExpiry.text(formattedExpiry);
                
                // Add expiry warning classes and tooltip
                if (daysUntilExpiry <= 30) {
                    $sslExpiry.addClass('warning');
                    $sslExpiry.attr('title', `Certificate expires in ${daysUntilExpiry} days!`);
                } else {
                    $sslExpiry.removeClass('warning');
                    $sslExpiry.attr('title', `Valid for ${daysUntilExpiry} days`);
                }
            } else {
                $('#ssl-expiry')
                    .text('Not Available')
                    .addClass('no-data')
                    .attr('title', 'Certificate expiry information not available');
            }
    
            // Update Protocol Information
            if (endpoint.details && endpoint.details.protocols) {
                const protocols = endpoint.details.protocols
                    .map(p => `${p.name} ${p.version}`)
                    .join(', ');
                
                $('#ssl-protocol')
                    .text(protocols)
                    .attr('title', 'Supported SSL/TLS protocols');
            } else {
                $('#ssl-protocol')
                    .text('Not Available')
                    .addClass('no-data')
                    .attr('title', 'Protocol information not available');
            }
    
            // Update additional details if available
           // updateAdditionalSSLDetails(endpoint);
    
        } else {
            // Handle invalid or incomplete data
            displayNoSSLData('Unable to retrieve SSL information');
        }
    }
    
    // Helper function to determine grade class
    function getSSLGradeClass(grade) {
        switch (grade.toUpperCase()) {
            case 'A+':
            case 'A':
            case 'A-':
                return 'good';
            case 'B':
            case 'C':
                return 'warning';
            default:
                return 'poor';
        }
    }
    
    // Helper function to get grade explanation
    function getGradeExplanation(grade) {
        const explanations = {
            'A+': 'Exceptional SSL configuration',
            'A': 'Very good SSL configuration',
            'A-': 'Good SSL configuration',
            'B': 'Adequate SSL configuration with minor weaknesses',
            'C': 'SSL configuration needs improvement',
            'D': 'SSL configuration has significant weaknesses',
            'F': 'SSL configuration is inadequate',
            'T': 'Certificate not trusted',
            'M': 'Certificate name mismatch',
            'N/A': 'Grade not available'
        };
        return explanations[grade] || 'Unknown grade';
    }
    
    // Helper function to update additional SSL details
    function updateAdditionalSSLDetails(endpoint) {
        if (endpoint.details) {
            // Add cipher suites information
            if (endpoint.details.suites) {
                const cipherInfo = endpoint.details.suites
                    .flatMap(suite => suite.list)
                    .map(cipher => cipher.name)
                    .join(', ');
                
                $('#ssl-ciphers')
                    .text(cipherInfo || 'Not Available')
                    .attr('title', 'Supported cipher suites');
            }
    
            // Add certificate chain information
            if (endpoint.details.certChains) {
                const chainInfo = endpoint.details.certChains
                    .map(chain => `Chain of ${chain.certIds.length} certificates`)
                    .join(', ');
                
                $('#ssl-cert-chain')
                    .text(chainInfo || 'Not Available')
                    .attr('title', 'Certificate chain information');
            }
        }
    }
    

    function displayNoSSLData(message) {
        const elements = ['#ssl-grade', '#ssl-last-checked', '#ssl-expiry', '#ssl-protocol'];
        elements.forEach(element => {
            $(element)
                .text('N/A')
                .addClass('no-data')
                .attr('title', message);
        });
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
                action: 'wpspeedtestpro_get_pagespeed_data',
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
                displayNoPageSpeedData('Error loading PageSpeed data');
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
    
    function updatePageSpeedCard(data) {
        if (!data.desktop && !data.mobile) {
            displayNoPageSpeedData();
            return;
        }
    
        // Update desktop metrics if available
        if (data.desktop) {
            updateScoreMetric('desktop-performance', data.desktop.performance_score);
            updateScoreMetric('desktop-accessibility', data.desktop.accessibility_score);
            updateScoreMetric('desktop-best-practices', data.desktop.best_practices_score);
            updateScoreMetric('desktop-seo', data.desktop.seo_score);
        }
    
        // Update mobile metrics if available
        if (data.mobile) {
            updateScoreMetric('mobile-performance', data.mobile.performance_score);
            updateScoreMetric('mobile-accessibility', data.mobile.accessibility_score);
            updateScoreMetric('mobile-best-practices', data.mobile.best_practices_score);
            updateScoreMetric('mobile-seo', data.mobile.seo_score);
        }
    
        // Update common information
        $('#tested-url').text(truncateUrl(data.test_url)).attr('title', data.test_url);
        $('#pagespeed-last-tested').text(data.last_tested ? new Date(data.last_tested).toLocaleString() : 'Never');
    }
    
    
    function displayNoPageSpeedData(message = 'No PageSpeed data available') {
        const metrics = [
            'desktop-performance', 'desktop-accessibility', 'desktop-best-practices', 'desktop-seo',
            'mobile-performance', 'mobile-accessibility', 'mobile-best-practices', 'mobile-seo'
        ];
        
        metrics.forEach(metric => {
            $(`#${metric}`)
                .text('--')
                .removeClass('good warning poor')
                .addClass('no-data');
        });
        
        $('#tested-url')
            .text('No data')
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
                    `
                })
            );
        }
    }

    function updateScoreMetric(elementId, score) {
        const $element = $(`#${elementId}`);
        if (score === null || score === undefined) {
            $element
                .text('N/A')
                .removeClass('good warning poor')
                .addClass('no-data');
            return;
        }
    
        $element
            .text(score + '%')
            .removeClass('good warning poor no-data')
            .addClass(getScoreClass(score));
    }
    
    
    function getScoreClass(score) {
        if (!score || score === 'N/A') return '';
        score = parseInt(score);
        if (score >= 90) return 'good';
        if (score >= 50) return 'warning';
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
                    nonce: wpspeedtestpro_ajax.nonce
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
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function() {
                    setTimeout(loadServerPerformance, 30000);
                    $('#test-performance').prop('disabled', false);
                }
            });
        });

        $('#test-pagespeed').on('click', function() {
            var urlToTest = "https://" + $('#tested-url').text();
            if (urlToTest === 'No data') {
                urlToTest = window.location.origin;
            }

            $(this).prop('disabled', true);
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pagespeed_run_test',
                    nonce: wpspeedtestpro_ajax.nonce,
                    url: urlToTest,
                    device: 'both',
                    frequency : 'once'
                },
                success: function() {
                    
                    setTimeout(loadPageSpeedData, 30000);
                    $('#test-pagespeed').prop('disabled', false);
                }
            });
        });

        $('.send-diagnostics-link').on('click', function(e) {
            e.preventDefault(); // Prevent the default link behavior
            
            const $link = $(this);
            const $spinner = $link.find('.spinner');
            const $siteKey = $('#site-key');
            
            // Add sending class to show spinner and disable link
            $link.addClass('sending');
            // Make Ajax call to trigger sync_data
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_sync_diagnostics',
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $siteKey.html("Site key: <strong>" + response.data.site_key + "</strong>");
                        $siteKey.show();
                        alert('Diagnostics data sent successfully!\n\nGive the Site Key to your support representative located on the dashboard.');

                    } else {
                        alert('Failed to send diagnostics data. Please try again or contact support.');
                    }
                },
                error: function() {
                    alert('Failed to send diagnostics data. Please try again or contact support.');
                },
                complete: function() {
                    // Remove sending class to hide spinner and re-enable link
                    $link.removeClass('sending');
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

    function truncateUrl(url) {
        if (!url) return 'N/A';
        const maxLength = 40;
        url = url.replace(/^https?:\/\//, '');
        return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
    }
    
        // Advertisement Rotation System
    function initAdvertisements() {
        const advertData = wpspeedtestpro_advertisers.data;
        if (!advertData || !advertData.banners || advertData.banners.length < 2) {
            return;
        }

        const banners = advertData.banners;
        let currentIndices = [0, 1];
        const controls = advertData.controls;

        function updateAdvert(cardIndex, bannerIndex) {
            const card = $(`#advert-${cardIndex + 1}`);
            const banner = banners[bannerIndex];

            // Start fade out
            card.css('opacity', 0);

            // Update content after brief delay
            setTimeout(() => {
                card.find('.advertisement-container').html('<img src="' + banner.imageUrl + '" alt="Advertisement">');
                card.find('.advert-title').text(banner.title);
                card.find('.advert-description').text(banner.description);
                card.find('.advert-button')
                    .text(banner.buttonText)
                    .attr('href', banner.clickUrl);
                card.find('.advert-link').attr('href', banner.clickUrl);

                // Fade back in
                card.css('opacity', 1);
            }, 300);
        }

        // Initialize adverts
        updateAdvert(0, currentIndices[0]);
        updateAdvert(1, currentIndices[1]);

        // Set up rotation if autoplay is enabled
        if (controls.autoplay) {
            const interval = controls.interval || 5000;

            setInterval(() => {
                currentIndices = currentIndices.map(index => 
                    (index + 2) % banners.length
                );

                updateAdvert(0, currentIndices[0]);
                updateAdvert(1, currentIndices[1]);
            }, interval);

            // Handle pause on hover if enabled
            if (controls.pauseOnHover) {
                $('.advert-card').hover(
                    function() {
                        $(this).css('transition', 'none');
                    },
                    function() {
                        $(this).css('transition', '');
                    }
                );
            }
        }
    }



    // Initialize the dashboard
    initDashboard();
    initAdvertisements();
});
