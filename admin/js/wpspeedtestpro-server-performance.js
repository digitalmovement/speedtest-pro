jQuery(document).ready(function($) {
    'use strict';

    var testStatus = wpspeedtestpro_performance.testStatus;
    // var continuousTestStatus = wpspeedtestpro_performance.continuousTestStatus;
    var charts = {};
    var continuousTestStatus = wpspeedtestpro_performance.wpspeedtestpro_continuous_data.continuousTestStatus;
    var timeRemaining = wpspeedtestpro_performance.wpspeedtestpro_continuous_data.timeRemaining;

    $('#performance-info-banner .notice-dismiss').on('click', function(e) {
        e.preventDefault();
        
        const $banner = $(this).closest('#performance-info-banner');
        
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_dismiss_performance_info',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $banner.slideUp(200, function() {
                        $banner.remove();
                    });
                }
            },
            error: function() {
                console.error('Failed to dismiss performance info banner');
            }
        });
    });


    // Initialize tabs
 //   $('#server-performance-tabs').tabs();

    function updateButtonState(status) {
        var $button = $('#start-stop-test');
        var $continuousButton = $('#continuous-test');
        $button.data('status', status);
        $button.text(status === 'running' ? 'Stop Test' : 'Start Test');
        $('#test-progress').toggle(status === 'running');

        if (continuousTestStatus === 'running') {
            $button.prop('disabled', true);
            $continuousButton.text('Stop Continuous Test');
        } else {
            $button.prop('disabled', false);
            $continuousButton.text('Continuous Testing');
        }
    }

    function displayErrorMessage(message) {
        $('#error-message').text(message).show();
    }

    function updateTestProgress(message) {
        $('#test-progress').html(message + '<div class="test-progress"></div>');
    }

    function toggleNotice($element, type = 'info') {
        const baseClass = 'notice-';
        $element.removeClass(baseClass + 'info ' + baseClass + 'error' + baseClass + 'success')
                .addClass(baseClass + type);
                $element.show();
    }

    function updateContinuousTestInfo() {
        if (continuousTestStatus === 'running') {
            $('#continuous-test-info').show();
            updateTimeRemaining();
            updateNextTestTime();
        } else {
            $('#continuous-test-info').hide();
        }
    }

    function updateNextTestTime() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_get_next_test_time',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#next-test-time').text(escapeHtml(response.data));
                }
            }
        });
    }
    
    // Helper function to safely escape HTML
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function updateTimeRemaining() {
        var hours = Math.floor(timeRemaining / 3600);
        var minutes = Math.floor((timeRemaining % 3600) / 60);
        var seconds = timeRemaining % 60;

        var timeString = hours + 'h ' + minutes + 'm ' + seconds + 's';
        $('#time-remaining').text(timeString);

        if (timeRemaining > 0) {
            timeRemaining--;
            setTimeout(updateTimeRemaining, 1000);
        } else {
            continuousTestStatus = 'stopped';
            updateButtonState(testStatus);
            updateContinuousTestInfo();
        }
    }

    $('#continuous-test').on('click', function() {
        if (continuousTestStatus === 'running') {
            stopContinuousTest();
        } else {
            $('#continuous-test-modal').show();
        }
    });

    $('#continue-test').on('click', function() {
        $('#continuous-test-modal').hide();
        startContinuousTest();
    });

    $('#cancel-test').on('click', function() {
        $('#continuous-test-modal').hide();
    });

    function startContinuousTest() {
        $.ajax({
            url: wpspeedtestpro_performance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_start_continuous_test',
                _ajax_nonce: wpspeedtestpro_performance.nonce
            },
            success: function(response) {
                if (response.success) {
                    continuousTestStatus = 'running';
                    updateButtonState(testStatus);
                    updateContinuousTestInfo();
                } else {
                    displayErrorMessage('Failed to start continuous test: ' + response.data);
                }
            },
            error: function() {
                displayErrorMessage('An error occurred while starting the continuous test. Please try again.');
            }
        });
    }

    function stopContinuousTest() {
        $.ajax({
            url: wpspeedtestpro_performance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_stop_continuous_test',
                _ajax_nonce: wpspeedtestpro_performance.nonce
            },
            success: function(response) {
                if (response.success) {
                    continuousTestStatus = 'stopped';
                    updateButtonState(testStatus);
                    updateContinuousTestInfo();
                } else {
                    displayErrorMessage('Failed to stop continuous test: ' + response.data);
                }
            },
            error: function() {
                displayErrorMessage('An error occurred while stopping the continuous test. Please try again.');
            }
        });
    }

    $('#start-stop-test').on('click', function() {
        var status = $(this).data('status');
        var newStatus = status === 'running' ? 'stopped' : 'running';

        $.ajax({
            url: wpspeedtestpro_performance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_toggle_test',
                status: newStatus,
                _ajax_nonce: wpspeedtestpro_performance.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateButtonState(newStatus);
                    if (newStatus === 'running') {
                        startBackgroundTest();
                    }
                } else {
                    displayErrorMessage('Failed to toggle test status. Please try again.');
                }
            },
            error: function() {
                displayErrorMessage('An error occurred while communicating with the server. Please try again.');
            }
        });
    });

    function startBackgroundTest() {
        updateTestProgress('Starting performance tests...');
        $.ajax({
            url: wpspeedtestpro_performance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_run_test',
                _ajax_nonce: wpspeedtestpro_performance.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateTestProgress('Tests started. Fetching results...');
                    setTimeout(loadResults, 5000); // Poll for results after 5 seconds
                } else {
                    displayErrorMessage('Failed to start performance tests: ' + response.data);
                    updateButtonState('stopped');
                }
            },
            error: function() {
                displayErrorMessage('An error occurred while starting the tests. Please try again.');
                updateButtonState('stopped');
            }
        });
    }

    function loadResults() {
        $.ajax({
            url: wpspeedtestpro_performance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpspeedtestpro_performance_get_results',
                _ajax_nonce: wpspeedtestpro_performance.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayResults(response.data);
                    updateButtonState('stopped');
                    updateTestProgress('Tests completed successfully.');
                } else if (response.data && response.data.status === 'running') {
                    updateTestProgress('Tests in progress. Checking again in 5 seconds...');
                    setTimeout(loadResults, 5000); // Keep polling if results are not ready
                } else {
                    displayErrorMessage('Failed to retrieve test results. Please try again.');
                    updateButtonState('stopped');
                }
            },
            error: function() {
                displayErrorMessage('An error occurred while fetching the results. Please try again.');
                updateButtonState('stopped');
            }
        });
    }

    function displayResults(data) {
        if (!data || !data.latest_results) {
            console.error('No data available');
            return;
        }
    
        displayLatestPerformanceResults(data.latest_results, data.industry_avg || {});
        displayLatestNetworkResults(data.latest_results, data.industry_avg || {});
        
        if (data.math) displayHistoricalResults('math', data.math, data.industry_avg || {});
        if (data.string) displayHistoricalResults('string', data.string, data.industry_avg || {});
        if (data.loops) displayHistoricalResults('loops', data.loops, data.industry_avg || {});
        if (data.conditionals) displayHistoricalResults('conditionals', data.conditionals, data.industry_avg || {});
        if (data.mysql) displayHistoricalResults('mysql', data.mysql, data.industry_avg || {});
        if (data.wordpress_performance) displayWordPressPerformance(data.wordpress_performance, data.industry_avg || {});
        if (data.speed_test) displaySpeedTestHistory(data.speed_test, (data.industry_avg || {}).speed_tests || {});
    }

    function displayLatestNetworkResults(data, industryAvg) {
        var ctx = document.getElementById('latest-network-chart').getContext('2d');
    
        if (charts.latestNetwork) {
            charts.latestNetwork.destroy();
        }

        document.getElementById('speed-test-location').textContent = data.speed_test.location || 'N/A';
        document.getElementById('speed-test-ip').textContent = data.speed_test.ip_address || 'N/A';
        document.getElementById('speed-test-ping').textContent = data.speed_test.ping_latency ? data.speed_test.ping_latency.toFixed(2) : 'N/A';


    
        const labels = [
            'Upload 10K', 'Upload 100K', 'Upload 1MB', 'Upload 10MB',
            'Download 10K', 'Download 100K', 'Download 1MB', 'Download 10MB'
        ];
    
        const latestData = [
            data.speed_test.upload_10k,
            data.speed_test.upload_100k,
            data.speed_test.upload_1mb,
            data.speed_test.upload_10mb,
            data.speed_test.download_10k,
            data.speed_test.download_100k,
            data.speed_test.download_1mb,
            data.speed_test.download_10mb
        ];
    
        const avgData = [
            industryAvg.speed_tests.upload['10K'].average,
            industryAvg.speed_tests.upload['100K'].average,
            industryAvg.speed_tests.upload['1MB'].average,
            industryAvg.speed_tests.upload['10MB'].average,
            industryAvg.speed_tests.download['10K'].average,
            industryAvg.speed_tests.download['100K'].average,
            industryAvg.speed_tests.download['1MB'].average,
            industryAvg.speed_tests.download['10MB'].average
        ];
    
        charts.latestNetwork = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Your Results',
                        data: latestData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Industry Average',
                        data: avgData,
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: false,
                        title: {
                            display: true,
                            text: 'Network Speed Tests'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Speed (MB/s)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Latest Network Performance'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)} MB/s`;
                            }
                        }
                    }
                }
            }
        });
    }
    

    function displayLatestPerformanceResults(data, industryAvg) {
        var ctx = document.getElementById('latest-performance-chart').getContext('2d');
    
        if (charts.latestPerformance) {
            charts.latestPerformance.destroy();
        }
    
        const labels = [
            'Math', 'String', 'Loops', 'Conditionals', 'MySQL', 'WordPress Time'
        ];
    
        const latestData = [
            data.math,
            data.string,
            data.loops,
            data.conditionals,
            data.mysql,
            data.wordpress_performance.time
        ];
    
        const avgData = [
            industryAvg.math,
            industryAvg.string,
            industryAvg.loops,
            industryAvg.conditionals,
            industryAvg.mysql,
            industryAvg.wordpress_performance.time
        ];
    
        charts.latestPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Your Results',
                        data: latestData,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Industry Average',
                        data: avgData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: false,
                        title: {
                            display: true,
                            text: 'Performance Metrics'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Latest Performance Results'
                    }
                }
            }
        });
    }


function displayLatestResults(data, industryAvg) {
    var ctx = document.getElementById('latest-results-chart').getContext('2d');

    if (charts.latestResults) {
        charts.latestResults.destroy();
    }

    // Add speed test metrics to the latest results
    const labels = [
        'Math', 'String', 'Loops', 'Conditionals', 'MySQL', 'WordPress Time',
        'Upload 10K', 'Upload 1MB', 'Upload 10MB',
        'Download 10K', 'Download 1MB', 'Download 10MB'
    ];

    const latestData = [
        data.math,
        data.string,
        data.loops,
        data.conditionals,
        data.mysql,
        data.wordpress_performance.time,
        data.speed_test.upload_10k,
        data.speed_test.upload_1mb,
        data.speed_test.upload_10mb,
        data.speed_test.download_10k,
        data.speed_test.download_1mb,
        data.speed_test.download_10mb
    ];

    const avgData = [
        industryAvg.math,
        industryAvg.string,
        industryAvg.loops,
        industryAvg.conditionals,
        industryAvg.mysql,
        industryAvg.wordpress_performance.time,
        industryAvg.speed_tests.upload['10K'].average,
        industryAvg.speed_tests.upload['1MB'].average,
        industryAvg.speed_tests.upload['10MB'].average,
        industryAvg.speed_tests.download['10K'].average,
        industryAvg.speed_tests.download['1MB'].average,
        industryAvg.speed_tests.download['10MB'].average
    ];

    charts.latestResults = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Your Results',
                    data: latestData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Industry Average',
                    data: avgData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: false,
                    title: {
                        display: true,
                        text: 'Performance Metrics'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Time / Speed (seconds / MB/s)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Latest Results vs Industry Average'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            const index = context.dataIndex;
                            // Add MB/s to speed test metrics
                            if (index >= 6) { // Speed test metrics start at index 6
                                return `${label}: ${value.toFixed(2)} MB/s`;
                            }
                            return `${label}: ${value.toFixed(3)} s`;
                        }
                    }
                }
            }
        }
    });

    // Update speed test info display
    document.getElementById('speed-test-location').textContent = data.speed_test.location || 'N/A';
    document.getElementById('speed-test-ip').textContent = data.speed_test.ip_address || 'N/A';
    document.getElementById('speed-test-ping').textContent = 
        data.speed_test.ping_latency ? data.speed_test.ping_latency.toFixed(2) : 'N/A';
}
    
function displaySpeedTestHistory(data, industryAvg) {
  
    var ctx = document.getElementById('speed-test-chart').getContext('2d');

    if (charts.speedTest) {
        charts.speedTest.destroy();
    }

    // Check if data exists and is valid
    if (!data || !Array.isArray(data) || data.length === 0) {
        charts.speedTest = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Speed Test History - No Data Available'
                    }
                }
            }
        });
        return;
    }

    // Safely process the data with null checks
    const processedData = data.map(item => {
        const speedTest = item.speed_test || {};
        return {
            x: new Date(item.test_date || new Date()),
            upload10k: speedTest.upload_10k || 0,
            upload100k: speedTest.upload_100k || 0,
            upload1m: speedTest.upload_1mb || 0,
            upload10m: speedTest.upload_10mb || 0,
            download10k: speedTest.download_10k || 0,
            download100k: speedTest.download_100k || 0,
            download1m: speedTest.download_1mb || 0,
            download10m: speedTest.download_10mb || 0
        };
    });

    charts.speedTest = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Upload 10KB',
                    data: processedData.map(item => ({ x: item.x, y: item.upload10k })),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                },
                {
                    label: 'Upload 100KB',
                    data: processedData.map(item => ({ x: item.x, y: item.upload100k })),
                    borderColor: 'rgba(255, 102, 255, 1)',
                    fill: false
                },
                {
                    label: 'Upload 1MB',
                    data: processedData.map(item => ({ x: item.x, y: item.upload1m })),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false
                },
                {
                    label: 'Upload 10MB',
                    data: processedData.map(item => ({ x: item.x, y: item.upload10m })),
                    borderColor: 'rgba(153, 102, 255, 1)',
                    fill: false
                },
                {
                    label: 'Download 10KB',
                    data: processedData.map(item => ({ x: item.x, y: item.download10k })),
                    borderColor: 'rgba(200, 150, 132, 1)',
                    fill: false
                },
                {
                    label: 'Download 100KB',
                    data: processedData.map(item => ({ x: item.x, y: item.download100k })),
                    borderColor: 'rgba(255, 159, 64, 1)',
                    fill: false
                },
                {
                    label: 'Download 1MB',
                    data: processedData.map(item => ({ x: item.x, y: item.download1m })),
                    borderColor: 'rgba(255, 205, 86, 1)',
                    fill: false
                },
                {
                    label: 'Download 10MB',
                    data: processedData.map(item => ({ x: item.x, y: item.download10m })),
                    borderColor: 'rgba(255, 99, 132, 1)',
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'minute'
                    },
                    title: {
                        display: true,
                        text: 'Test Time'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Speed (MB/s)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Speed Test History'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y.toFixed(2)} MB/s`;
                        }
                    }
                }
            }
        }
    });
}

    function displayHistoricalResults(testType, data, industryAvg) {
        var ctx = document.getElementById(testType + '-chart').getContext('2d');
    
        if (charts[testType]) {
            charts[testType].destroy();
        }
    
        // Process the data to extract dates and values
        var processedData = data.map(item => ({
            x: new Date(item.test_date),
            y: parseFloat(item[testType])
        }));
    
        // Create industry average dataset
        var industryAvgData = processedData.map(item => ({
            x: item.x,
            y: industryAvg[testType]
        }));
    
        charts[testType] = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: testType.charAt(0).toUpperCase() + testType.slice(1) + ' Performance',
                        data: processedData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    },
                    {
                        label: 'Industry Average',
                        data: industryAvgData,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4 
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        },
                        title: {
                            display: true,
                            text: 'Test Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)'
                        }
                    }
                }
            }
        });
    }


 function displayWordPressPerformance(data, industryAvg) {
    var ctx = document.getElementById('wordpress-performance-chart').getContext('2d');

    if (charts.wordpressPerformance) {
        charts.wordpressPerformance.destroy();
    }

    // Process the data to extract dates, time, and queries
    var processedData = data.map(item => ({
        x: new Date(item.test_date),
        y1: parseFloat(item.wordpress_performance.time),
        y2: parseFloat(item.wordpress_performance.queries)
    }));

    charts.wordpressPerformance = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Execution Time',
                    data: processedData.map(item => ({ x: item.x, y: item.y1 })),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false,
                    yAxisID: 'y-time'
                },
                {
                    label: 'Industry Avg Time',
                    data: processedData.map(item => ({ x: item.x, y: industryAvg.wordpress_performance.time })),
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderDash: [5, 5],
                    fill: false,
                    yAxisID: 'y-time'
                },
                {
                    label: 'Queries per Second',
                    data: processedData.map(item => ({ x: item.x, y: item.y2 })),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false,
                    yAxisID: 'y-queries'
                },
                {
                    label: 'Industry Avg Queries',
                    data: processedData.map(item => ({ x: item.x, y: industryAvg.wordpress_performance.queries })),
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderDash: [5, 5],
                    fill: false,
                    yAxisID: 'y-queries'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day'
                    },
                    title: {
                        display: true,
                        text: 'Test Date'
                    }
                },
                'y-time': {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Time (seconds)'
                    }
                },
                'y-queries': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Queries per Second'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}
function initializeTabs() {
    $('.wpspeedtestpro-tab-links a').on('click', function(e) {
        e.preventDefault();
        var currentAttrValue = $(this).attr('href');

        $('.wpspeedtestpro-tab-content ' + currentAttrValue).show().siblings().hide();
        $(this).parent('li').addClass('active').siblings().removeClass('active');
    });

    // Ensure the default/active tab is shown initially
    var defaultTab = $('.wpspeedtestpro-tab-links li.active a').attr('href') || $('.wpspeedtestpro-tab-links a').first().attr('href');
    if (defaultTab) {
        $('.wpspeedtestpro-tab-content ' + defaultTab).show().siblings().hide();
    }
}


initializeTabs();
updateButtonState(testStatus);
updateContinuousTestInfo();
    // Add a slight delay before loading results to ensure tabs are properly initialized
    setTimeout(function() {
        loadResults();
    }, 100);
});