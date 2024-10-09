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
        displayHistoricalResults('math', data.math);
        displayHistoricalResults('string', data.string);
        displayHistoricalResults('loops', data.loops);
        displayHistoricalResults('conditionals', data.conditionals);
        displayHistoricalResults('mysql', data.mysql);
        displayWordPressPerformance(data.wordpress_performance);
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

    function displayHistoricalResults(testType, data) {
        var ctx = document.getElementById(testType + '-chart').getContext('2d');

        if (charts[testType]) {
            charts[testType].destroy();
        }

        charts[testType] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.test_date),
                datasets: [{
                    label: testType.charAt(0).toUpperCase() + testType.slice(1) + ' Performance',
                    data: data.map(item => item.value),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Test Date'
                        }
                    }
                }
            }
        });
    }

    function displayWordPressPerformance(data) {
        var ctx = document.getElementById('wordpress-performance-chart').getContext('2d');

        if (charts.wordpressPerformance) {
            charts.wordpressPerformance.destroy();
        }

        charts.wordpressPerformance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.test_date),
                datasets: [
                    {
                        label: 'Execution Time',
                        data: data.map(item => item.time),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false,
                        yAxisID: 'y-time'
                    },
                    {
                        label: 'Queries per Second',
                        data: data.map(item => item.queries),
                        borderColor: 'rgba(255, 99, 132, 1)',
                        fill: false,
                        yAxisID: 'y-queries'
                    }
                ]
            },
            options: {
                scales: {
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
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Test Date'
                        }
                    }
                }
            }
        });
    }

    updateButtonState(testStatus);
    loadResults();
});