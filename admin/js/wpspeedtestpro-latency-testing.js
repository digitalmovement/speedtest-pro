jQuery(document).ready(function($) {
    var countdownInterval;
    var nextTestCountdownInterval;
    var isRunning = false;
    var chartInstances = {};
    const MIN_DATA_POINTS = 5;

    const regionGroups = {
        'Europe': ['Warsaw', 'Finland', 'Madrid', 'Belgium', 'Berlin', 'Turin', 'London', 'Frankfurt', 'Netherlands', 'Zurich', 'Milan', 'Paris'],
        'US': ['Montréal', 'Toronto', 'Iowa', 'South Carolina', 'North Virginia', 'Columbus', 'Dallas', 'Oregon', 'Los Angeles', 'Salt Lake City', 'Las Vegas'],
        'Asia': ['Taiwan', 'Hong Kong', 'Tokyo', 'Osaka', 'Seoul', 'Mumbai', 'Delhi', 'Singapore', 'Jakarta'],
        'Other': ['Johannesburg', 'São Paulo', 'Santiago', 'Sydney', 'Melbourne', 'Doha', 'Dammam', 'Tel Aviv']
    };

    function hideAllTabs() {
        $('#tabs').hide();
        if (!$('#graphs-message').length) {
            $('#results-container').append(
                '<div id="graphs-message" class="notice notice-info">' +
                '<p>Waiting for more test results before displaying graphs. At least 5 data points are needed for each region.</p>' +
                '</div>'
            );
        }
    }

    function updateGraphVisibility(results) {
        // Group results by region
        let regionData = {};
        results.forEach(result => {
            if (!regionData[result.region_name]) {
                regionData[result.region_name] = [];
            }
            regionData[result.region_name].push(result);
        });

        // Check each region group for sufficient data
        let hasEnoughData = false;
        Object.keys(regionGroups).forEach(group => {
            let groupHasData = false;
            regionGroups[group].forEach(region => {
                if (regionData[region] && regionData[region].length >= MIN_DATA_POINTS) {
                    groupHasData = true;
                }
            });
            
            const tabIndex = Object.keys(regionGroups).indexOf(group) + 1;
            if (groupHasData) {
                hasEnoughData = true;
                $(`#tabs-${tabIndex}`).show();
                $(`#tabs ul li:nth-child(${tabIndex})`).show();
            } else {
                $(`#tabs-${tabIndex}`).hide();
                $(`#tabs ul li:nth-child(${tabIndex})`).hide();
            }
        });

        if (!hasEnoughData) {
            hideAllTabs();
        } else {
            $('#tabs').show();
            $('#graphs-message').remove();
        }
    }


    function updateButtonState(isRunning, isContinuous) {
        $('#run-once-test').prop('disabled', isRunning);
        $('#continuous-test').prop('disabled', isRunning);
        $('#run-once-test').toggle(!isRunning);
        $('#continuous-test').toggle(!isRunning);
        $('#stop-test').toggle(isRunning && isContinuous);
    }

    function showContinuousWarning() {
        return new Promise((resolve, reject) => {
            $('#continuousModal').css('display', 'block');

            $('#cancelContinuous').on('click', function() {
                $('#continuousModal').css('display', 'none');
                reject();
            });

            $('#confirmContinuous').on('click', function() {
                $('#continuousModal').css('display', 'none');
                resolve();
            });
        });
    }

   function startNextTestCountdown() {
        clearInterval(nextTestCountdownInterval);
        
        // Get next scheduled time from WordPress
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_next_test_time',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.next_test_time) {
                    const nextTestTime = new Date(response.data.next_test_time * 1000);
                    
                    nextTestCountdownInterval = setInterval(function() {
                        const now = new Date();
                        let diff = nextTestTime - now;

                        if (diff <= 0) {
                            $('#next-test-countdown').text('Running test...');
                            clearInterval(nextTestCountdownInterval);
                            // Refresh status after a short delay
                            setTimeout(checkContinuousTestingStatus, 5000);
                            return;
                        }

                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                        const formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
                        const formattedSeconds = seconds < 10 ? "0" + seconds : seconds;

                        $('#next-test-countdown').text(`Next test in: ${formattedMinutes}:${formattedSeconds}`);
                    }, 1000);
                } else {
                    $('#next-test-countdown').text('');
                }
            }
        });
    }


    $('#run-once-test').on('click', function() {
        isRunning = true;
        updateButtonState(true, false);

        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_run_once_test',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Running one-time test...');
                    setTimeout(function() {
                        isRunning = false;
                        updateButtonState(false, false);
                        $('#test-status').text('Test completed.');
                        updateResults(getStoredTimeRange());
                    }, 30000); // Wait 30 seconds for test to complete
                }
            }
        });
    });

    $('#continuous-test').on('click', function() {
        showContinuousWarning().then(() => {
            isRunning = true;
            updateButtonState(true, true);

            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_start_continuous_test',
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#test-status').text('Continuous testing enabled.');
                        startNextTestCountdown();
                    }
                }
            });
        }).catch(() => {
            // User cancelled - no action needed
        });
    });

    $('#stop-test').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_stop_continuous_test',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Continuous testing stopped.');
                    clearInterval(nextTestCountdownInterval);
                    $('#next-test-countdown').text('');
                    isRunning = false;
                    updateButtonState(false, false);
                }
            }
        });
    });
    // Check continuous testing status on page load
    function checkContinuousTestingStatus() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_continuous_status',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_continuous) {
                    isRunning = true;
                    updateButtonState(true, true);
                    $('#test-status').text('Continuous testing enabled.');
                    startNextTestCountdown();
                }
            }
        });
    }



    function startCountdown(duration, startTime) {
        var timer = duration - (Math.floor(Date.now() / 1000) - startTime), minutes, seconds;
        countdownInterval = setInterval(function () {
            if (timer <= 0) {
                clearInterval(countdownInterval);
                $('#test-status').text('Test completed.');
                isRunning = false;
                updateButtonState(false);
                return;
            }

            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#countdown').text(minutes + ":" + seconds);
            timer--;
        }, 1000);
    }

    function checkTestStatus() {
        var startTime = parseInt(wpspeedtestpro.start_time, 10);
        if (startTime) {
            var currentTime = Math.floor(Date.now() / 1000);
            var elapsedTime = currentTime - startTime;
            if (elapsedTime < 3600) {
                $('#test-status').text('Test running...');
                isRunning = true;
                updateButtonState(true);
                startCountdown(3600, startTime);
            }
        }
    }

    function createGraphContainer(regionName, groupName) {
        var containerId = 'graph-container-' + regionName.replace(/\s+/g, '-').toLowerCase();
        var container = $('<div>').attr('id', containerId).addClass('graph-container').css({
            width: '100%',
            marginBottom: '40px'  // Add margin to the bottom of each graph container
        });

        var canvas = $('<canvas>').attr('id', 'graph-' + regionName.replace(/\s+/g, '-').toLowerCase());
        container.append(canvas);
        $('#graphs-' + groupName.toLowerCase()).append(container);
    }

    function renderGraphs(results) {
        var regionData = {};

        results.forEach(function(result) {
            if (!regionData[result.region_name]) {
                regionData[result.region_name] = {
                    labels: [],
                    latencies: [],
                    lastUpdated: result.test_time
                };
            }

            regionData[result.region_name].labels.push(new Date(result.test_time));
            regionData[result.region_name].latencies.push(parseFloat(result.latency));
            regionData[result.region_name].lastUpdated = result.test_time;
        });

        Object.keys(regionData).forEach(function(region) {
            var groupName = Object.keys(regionGroups).find(group => regionGroups[group].includes(region)) || 'Other';
            if (!document.getElementById('graph-' + region.replace(/\s+/g, '-').toLowerCase())) {
                createGraphContainer(region, groupName);
            }

            var ctx = document.getElementById('graph-' + region.replace(/\s+/g, '-').toLowerCase()).getContext('2d');

            if (chartInstances[region]) {
                chartInstances[region].destroy();
            }

            var lastUpdatedTimePlugin = {
                id: 'lastUpdatedTimePlugin',
                afterDraw: function(chart) {
                    var ctx = chart.ctx;
                    var chartArea = chart.chartArea;
                    var lastUpdated = regionData[region].lastUpdated;
                    ctx.save();
                    ctx.font = '12px Arial';
                    ctx.fillStyle = 'gray';
                    ctx.textAlign = 'right';

                    var formattedDate = new Date(lastUpdated).toLocaleString();
                    ctx.fillText("Last updated: " + formattedDate, 
                        chartArea.right,
                        chartArea.top - 10
                    );
                    ctx.restore();
                }
            };

            // Function to get evenly spaced sample dates
            function getSampleDates(dates, numSamples) {
                var result = [];
                var step = Math.floor(dates.length / (numSamples - 1));
                for (var i = 0; i < dates.length; i += step) {
                    result.push(dates[Math.min(i, dates.length - 1)]);
                }
                if (result[result.length - 1] !== dates[dates.length - 1]) {
                    result[result.length - 1] = dates[dates.length - 1];
                }
                return result;
            }

            var sampleDates = getSampleDates(regionData[region].labels, 5);

            chartInstances[region] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: regionData[region].labels,
                    datasets: [{
                        label: 'Latency (ms) for ' + region,
                        data: regionData[region].latencies,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                displayFormats: {
                                    day: 'MMM d'
                                }
                            },
                            ticks: {
                                source: 'auto',
                                autoSkip: true,
                                maxTicksLimit: 5,
                                callback: function(value, index, values) {
                                    return sampleDates.find(date => date.getTime() === value) ? this.getLabelForValue(value) : '';
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Latency (ms)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: region,
                            font: {
                                size: 16
                            },
                            padding: {
                                top: 10,
                                bottom: 30
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return new Date(tooltipItems[0].parsed.x).toLocaleString();
                                }
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 30  // Add padding to the top for the "Last updated" text
                        }
                    }
                },
                plugins: [lastUpdatedTimePlugin]
            });
        });
    }


    $('#time-range').on('change', function() {
        var timeRange = $(this).val();

        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_results_for_time_range',
                nonce: wpspeedtestpro_ajax.nonce,
                time_range: timeRange
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                    renderGraphs(response.data);
                } else {
                    alert('No results found for the selected time range.');
                }
            }
        });
    });

    $('#start-test').on('click', function() {
        isRunning = true;
        updateButtonState(true);

        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_start_latency_test',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test started. Running for 1 hour.');
                    startCountdown(3600, Math.floor(Date.now() / 1000));
                } else {
                    alert(response.data);
                    isRunning = false;
                    updateButtonState(false);
                }
            }
        });
    });

    $('#stop-test').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_stop_latency_test',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test stopped.');
                    clearInterval(countdownInterval);
                    isRunning = false;
                    updateButtonState(false);
                    $('#countdown').text('');
                }
            }
        });
    });

    $('#delete-results').on('click', function() {
        $('#deleteModal').css('display', 'block');
    });

    $('#cancelButton').on('click', function() {
        $('#deleteModal').css('display', 'none');
    });

    $('#confirmDelete').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_delete_all_results',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').css('display', 'none');
                    alert('All latency data discarded');
                    //updateResults();
                    location.reload();
                } else {
                    alert('Error discarding results');
                }
            }
        });
    });

    function saveTimeRange(timeRange) {
        localStorage.setItem('wpspeedtestpro_time_range', timeRange);
    }

    function getStoredTimeRange() {
        return localStorage.getItem('wpspeedtestpro_time_range') || '24_hours';
    }

   function updateResults(timeRange) {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_results_for_time_range',
                nonce: wpspeedtestpro_ajax.nonce,
                time_range: timeRange
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                    updateGraphVisibility(response.data);
                    if ($('#tabs').is(':visible')) {
                        renderGraphs(response.data);
                    }
                } else {
                    hideAllTabs();
                    console.error('Error in server response:', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
            }
        });
    }

    $('#time-range').on('change', function() {
        var timeRange = $(this).val();
        saveTimeRange(timeRange);
        updateResults(timeRange);
    });



    function updateResultsTable(results) {
        var tableBody = $('#latency-results tbody');
        tableBody.empty();
        var regionData = {};
        var selectedRegion = wpspeedtestpro_ajax.selected_region;

        function isValidLatency(value) {
            var parsedValue = parseFloat(value);
            return !isNaN(parsedValue) && isFinite(parsedValue);
        }

        results.forEach(function(result) {
            if (!result || typeof result !== 'object') {
                console.error('Invalid result:', result);
                return; // Skip this result
            }

            var region = result.region_name;
            if (!region) {
                console.error('Invalid result: missing region_name', result);
                return; // Skip this result
            }

            if (!('latency' in result) || !('fastest_latency' in result) || !('slowest_latency' in result)) {
                console.error('Missing latency data for region:', region, result);
                return; // Skip this result
            }

            if (!isValidLatency(result.latency) || !isValidLatency(result.fastest_latency) || !isValidLatency(result.slowest_latency)) {
                console.error('Invalid latency data for region:', region, result);
                return; // Skip this result
            }

            var latency = parseFloat(result.latency);
            var fastestLatency = parseFloat(result.fastest_latency);
            var slowestLatency = parseFloat(result.slowest_latency);
            var testTime = result.test_time;

            if (!regionData[region]) {
                regionData[region] = {
                    currentLatency: latency,
                    fastestLatency: fastestLatency,
                    slowestLatency: slowestLatency,
                    lastUpdated: testTime
                };
            } else {
                if (fastestLatency < regionData[region].fastestLatency) {
                    regionData[region].fastestLatency = fastestLatency;
                }
                if (slowestLatency > regionData[region].slowestLatency) {
                    regionData[region].slowestLatency = slowestLatency;
                }
                if (new Date(testTime) > new Date(regionData[region].lastUpdated)) {
                    regionData[region].currentLatency = latency;
                    regionData[region].lastUpdated = testTime;
                }
            }
        });

        Object.keys(regionData).forEach(function(region) {
            var row = $('<tr>');

            if (region === selectedRegion) {
                row.addClass('highlight-row');
            }

            row.append($('<td>').text(region));
            row.append($('<td>').text(regionData[region].currentLatency.toFixed(1) + ' ms'));
            row.append($('<td>').text(regionData[region].fastestLatency.toFixed(1) + ' ms'));
            row.append($('<td>').text(regionData[region].slowestLatency.toFixed(1) + ' ms'));
            row.append($('<td>').text(formatDate(regionData[region].lastUpdated)));
            tableBody.append(row);
        });
    }

    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleString();
    }


    function initializeTimeRange() {
        var storedTimeRange = getStoredTimeRange();
        $('#time-range').val(storedTimeRange);
        updateResults(storedTimeRange);
    }

    $(function() {
        $("#tabs").tabs();
    });

    // Initialize
    checkContinuousTestingStatus();
    checkTestStatus();
    initializeTimeRange();
    setInterval(function() {
        updateResults(getStoredTimeRange());
    }, 60000);
});