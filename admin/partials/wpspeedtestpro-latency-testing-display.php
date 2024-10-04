<!-- Placeholder for wpspeedtestpro-latency-display.php -->
<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin/partials
 */
?>
<div id="wpspeedtestpro" class="wrap">
<h1>Google Data Center Latency Testing</h1>
<div id="latency-test-container">
    <button id="start-test" class="button button-primary">Start Latency Test</button>
    <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
    <button id="delete-results" class="button button-secondary delete-button">Delete All Results</button>
    <p id="test-status"></p>
    <div id="countdown"></div>
</div>
<div id="results-container">
<div>
    <label for="time-range">Select Time Range:</label>
    <select id="time-range" class="time-range-dropdown">
        <option value="24_hours">Last 24 Hours</option>
        <option value="7_days">Last 7 Days</option>
        <option value="90_days">Last 90 Days</option>
    </select>
</div>
    <h2>Latest Results</h2>
    <table id="latency-results" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Region</th>
                <th>Current Latency (ms)</th>
                <th>Fastest Latency (ms)</th>
                <th>Slowest Latency (ms)</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>
<div id="graphs-container">
    <h2>Graphs for Each Region</h2>
</div>

</div>


<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h2>Discard all latency Data?</h2>
        <p>This cannot be undone.</p>
        <div class="modal-footer">
            <button id="cancelButton" class="button button-secondary">Cancel</button>
            <button id="confirmDelete" class="button discard-button">Discard</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var countdownInterval;
    var isRunning = false;
    var lastResults = {};
    var chartInstances = {};

    function updateButtonState(isRunning) {
        $('#start-test').prop('disabled', isRunning);
        $('#start-test').toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
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

    function createGraphContainer(regionName) {
        var container = $('<div>').attr('id', 'graph-container-' + regionName).css({
            height: '300px',
            width: '100%',
            margin: '20px 0'
        });

        var title = $('<h3>').text('Graph for ' + regionName);
        var canvas = $('<canvas>').attr('id', 'graph-' + regionName);

        container.append(title);
        container.append(canvas);
        $('#graphs-container').append(container);
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
            if (!document.getElementById('graph-' + region)) {
                createGraphContainer(region);
            }

            if (regionData[region].latencies.length < 10) {
                var ctx = document.getElementById('graph-' + region).getContext('2d');
                var message = "Awaiting more data for " + region;

                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.font = "16px Arial";
                ctx.fillStyle = "black";
                ctx.textAlign = "center";
                ctx.fillText(message, ctx.canvas.width / 2, ctx.canvas.height / 2);
            } else {
                var ctx = document.getElementById('graph-' + region).getContext('2d');

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
                        ctx.textAlign = 'center';

                        var formattedDate = new Date(lastUpdated).toLocaleString();
                        ctx.fillText("Last updated: " + formattedDate, 
                            (chartArea.left + chartArea.right) / 2,
                            chartArea.bottom + 30
                        );
                        ctx.restore();
                    }
                };

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
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'hour',
                                    tooltipFormat: 'MMM D, h:mm A',
                                    displayFormats: {
                                        hour: 'h:mm A'
                                    }
                                },
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 6,
                                    source: 'auto',
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    },
                    plugins: [lastUpdatedTimePlugin] 
                });
            }
        });
    }

    $('#time-range').on('change', function() {
        var timeRange = $(this).val();

        $.ajax({
            url: wpspeedtestpro-latency-testing.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_results_for_time_range',
                nonce: wpspeedtestpro-latency-testing.nonce,
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
            url: wpspeedtestpro-latency-testing.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_start_latency_test',
                nonce: wpspeedtestpro-latency-testing.nonce
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
            url: wpspeedtestpro.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_stop_latency_test',
                nonce: wpspeedtestpro.nonce
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
            url: wpspeedtestpro-latency-testing.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_delete_all_results',
                nonce: wpspeedtestpro-latency-testing.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').css('display', 'none');
                    alert('All latency data discarded');
                    updateResults();
                } else {
                    alert('Error discarding results');
                }
            }
        });
    });

    function updateResults() {
        $.ajax({
            url: wpspeedtestpro-latency-testing.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_get_latest_results',
                nonce: wpspeedtestpro-latency-testing.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                    renderGraphs(response.data);
                } else {
                    console.error('Error in server response:', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
            }
        });
    }

    function updateResultsTable(results) {
    var tableBody = $('#latency-results tbody');
    tableBody.empty();
    var regionData = {};
    var selectedRegion = wpspeedtestpro.selected_region;

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

    function isFloat(value) {
    // Check if the value is a number and not NaN
    if (typeof value === 'number' && !isNaN(value)) {
        // Check if it's not an integer
        return value % 1 !== 0;
    }
    return false;
}

    checkTestStatus();
    updateResults();
    setInterval(updateResults, 60000);
});
</script>