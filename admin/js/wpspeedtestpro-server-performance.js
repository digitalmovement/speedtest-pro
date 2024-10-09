jQuery(document).ready(function($) {
    'use strict';

    var testStatus = wpspeedtestpro_performance.testStatus;
    var charts = {};

    // Initialize tabs
    $('#server-performance-tabs').tabs();

    function updateButtonState(status) {
        var $button = $('#start-stop-test');
        $button.data('status', status);
        $button.text(status === 'running' ? 'Stop Test' : 'Start Test');
        $('#test-progress').toggle(status === 'running');
    }

    function displayErrorMessage(message) {
        $('#error-message').text(message).show();
    }

    function updateTestProgress(message) {
        $('#test-progress').text(message);
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
        displayLatestResults(data.latest_results);
        displayHistoricalResults('math', data.math, data.industry_avg);
        displayHistoricalResults('string', data.string, data.industry_avg);
        displayHistoricalResults('loops', data.loops, data.industry_avg);
        displayHistoricalResults('conditionals', data.conditionals, data.industry_avg);
        displayHistoricalResults('mysql', data.mysql, data.industry_avg);
        displayWordPressPerformance(data.wordpress_performance, data.industry_avg);
    }

    function displayLatestResults(data) {
        var ctx = document.getElementById('latest-results-chart').getContext('2d');

        if (charts.latestResults) {
            charts.latestResults.destroy();
        }

        charts.latestResults = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Math', 'String', 'Loops', 'Conditionals', 'MySQL', 'WordPress'],
                datasets: [
                    {
                        label: 'Latest Results',
                        data: [
                            data.math,
                            data.string,
                            data.loops,
                            data.conditionals,
                            data.mysql,
                            data.wordpress_performance.time
                        ],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    }
                ]
            },
            options: {
                scales: {
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

    updateButtonState(testStatus);
    loadResults();
});