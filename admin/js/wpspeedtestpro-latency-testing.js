jQuery(document).ready(function($) {
    var countdownInterval;
    var nextTestCountdownInterval;
    var isRunning = false;
    var chartInstances = {};
    const MIN_DATA_POINTS = 5;
    const $status = $('#test-status');

    const regionGroups = {
        'Europe': ['Warsaw', 'Finland', 'Madrid', 'Belgium', 'Berlin', 'Turin', 'London', 'Frankfurt', 'Netherlands', 'Zurich', 'Milan', 'Paris', 'Stockholm'],
        'US': ['Montréal', 'Toronto', 'Iowa', 'South Carolina', 'North Virginia', 'Columbus', 'Dallas', 'Oregon', 'Los Angeles', 'Salt Lake City', 'Las Vegas'],
        'Asia': ['Taiwan', 'Hong Kong', 'Tokyo', 'Osaka', 'Seoul', 'Mumbai', 'Delhi', 'Singapore', 'Jakarta'],
        'Other': ['Johannesburg', 'São Paulo', 'Santiago', 'Sydney', 'Melbourne', 'Doha', 'Dammam', 'Tel Aviv', 'México']
    };

    
    const countryMap = {
        'Taiwan': 'tw',
        'Johannesburg': 'za',
        'Hong Kong': 'hk',
        'Tokyo': 'jp',
        'Osaka': 'jp',
        'Seoul': 'kr',
        'Mumbai': 'in',
        'Delhi': 'in',
        'Singapore': 'sg',
        'Jakarta': 'id',
        'Melbourne': 'au',
        'Berlin': 'de',
        'Belgium': 'be',
        'Madrid': 'es',
        'Finland': 'fi',
        'Warsaw': 'pl',
        'Sydney': 'au',
        'Netherlands': 'nl',
        'Dammam': 'sa',
        'Doha': 'qa',
        'Paris': 'fr',
        'Zurich': 'ch',
        'Frankfurt': 'de',
        'London': 'gb',
        'Turin': 'it',
        'México': 'mx',
        'Milan': 'it',
        'Tel Aviv': 'il',
        'Montréal': 'ca',
        'Toronto': 'ca',
        'São Paulo': 'br',
        'Santiago': 'cl',
        'Stockholm': 'se',
        'Columbus': 'us',
        'North Virginia': 'us',
        'South Carolina': 'us',
        'Iowa': 'us',
        'Dallas': 'us',
        'Oregon': 'us',
        'Los Angeles': 'us',
        'Salt Lake City': 'us',
        'Las Vegas': 'us',
        'Global External HTTPS Load Balancer': 'un'
    };

    $('#latency-info-banner .notice-dismiss').on('click', function(e) {
        e.preventDefault();
        
        const $banner = $(this).closest('#latency-info-banner');
        
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_dismiss_latency_info',
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
                console.error('Failed to dismiss latency info banner');
            }
        });
    });


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
        $status.html('One-time test is initializing, please wait! <div class="test-progress"></div>');
        toggleNotice($status, 'info');
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_run_once_test',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('Running one-time test...<div class="test-progress"></div>');
                    toggleNotice($status, 'info');
                    setTimeout(function() {
                        isRunning = false;
                        updateButtonState(false, false);
                        $status.html('Test completed.');
                        toggleNotice($status, 'success');
  
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
            $status.html('Continuous test is initializing, please wait! <div class="test-progress"></div>');
            toggleNotice($status, 'info');
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_start_continuous_test',
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('Continuous testing enabled.');
                        toggleNotice($status, 'success');
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
                    $status.text('Continuous testing stopped.');
                    toggleNotice($status, 'success');
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
                    toggleNotice ($status, 'success');
                    $status.text('Continuous testing enabled.');
                    toggleNotice($status, 'success');
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
                $status.text('Test completed.');
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
                $status.text('Test running...');
                toggleNotice($status, 'info');
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
            marginBottom: '40px'
        });
    
        var canvas = $('<canvas>').attr('id', 'graph-' + regionName.replace(/\s+/g, '-').toLowerCase());
        container.append(canvas);
        
        // Make sure jQuery UI tabs are initialized before adding content
        if (!$('#tabs').hasClass('ui-tabs')) {
            $("#tabs").tabs();
        }
        
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
            // Force tabs initialization/refresh before creating chart containers
        if ($("#tabs").tabs("instance") === undefined) {
            $("#tabs").tabs();
        } else {
            $("#tabs").tabs("refresh");
        }
        
        // Clear existing graph containers that might be in the wrong tabs
        $('.graph-container').remove();

        

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
                if (!dates || dates.length === 0) {
                    return [];
                }
                
                var result = [];
                
                // If we have fewer dates than requested samples, return all dates
                if (dates.length <= numSamples) {
                    return dates;
                }
                
                var step = Math.floor(dates.length / (numSamples - 1));
                // Ensure step is at least 1
                step = Math.max(1, step);
                
                for (var i = 0; i < dates.length; i += step) {
                    result.push(dates[Math.min(i, dates.length - 1)]);
                }
                
                // Make sure the last date is included
                if (result[result.length - 1] !== dates[dates.length - 1]) {
                    result.push(dates[dates.length - 1]);
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
                    $status.text('Test started. Running for 1 hour.');
                    toggleNotice($status, 'info');
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
                    $status.text('Test stopped.');
                    toggleNotice($status, 'info');
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




    // Add this function to your existing updateResultsTable function
    function updateResultsTable(results) {
        var tableBody = $('#latency-results tbody');
        tableBody.empty();
        var regionData = {};
        var selectedRegion = wpspeedtestpro_ajax.selected_region;
    
        function isValidLatency(value) {
            var parsedValue = parseFloat(value);
            return !isNaN(parsedValue) && isFinite(parsedValue);
        }
    
        // Process results and create regionData object
        results.forEach(function(result) {
            if (!result || typeof result !== 'object') return;
            
            var region = result.region;
            var region_name = result.region_name;

            if (!region) return;
    
            if (!('latency' in result) || !('fastest_latency' in result) || !('slowest_latency' in result)) return;
            if (!isValidLatency(result.latency) || !isValidLatency(result.fastest_latency) || !isValidLatency(result.slowest_latency)) return;
    
            var latency = parseFloat(result.latency);
            var fastestLatency = parseFloat(result.fastest_latency);
            var slowestLatency = parseFloat(result.slowest_latency);
            var testTime = result.test_time;
    
            if (!regionData[region]) {
                regionData[region] = {
                    region_name: region_name,
                    currentLatency: latency,
                    fastestLatency: fastestLatency,
                    slowestLatency: slowestLatency,
                    lastUpdated: testTime,
                    previousLatency: null
                };
            } else {
                // Store the previous latency before updating
                regionData[region].previousLatency = regionData[region].currentLatency;
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
    
        // Create table rows with flags and bubbles
        Object.keys(regionData).forEach(function(region) {
            var row = $('<tr>');
            var thisRegionName = regionData[region].region_name
            if (region === selectedRegion) {
                row.addClass('highlight-row');
            }
    
            // Create region cell with flag
            var regionCell = $('<td>');
            if (countryMap[thisRegionName]) {
                var flagSpan = $('<span>')
                    .addClass('flag-icon')
                    .css({
                        'background-image': `url('https://flagcdn.com/28x21/${countryMap[thisRegionName]}.png')`,
                        'display': 'inline-block',
                        'width': '20px',
                        'height': '20px',
                        'background-size': 'cover',
                        'border-radius': '50%',
                        'vertical-align': 'middle',
                        'margin-right': '8px'
                    });
                regionCell.append(flagSpan);
            }
            regionCell.append(thisRegionName);
            
            // Create latency cell with bubble
            var latencyCell = $('<td>');
            var bubbleSpan = $('<span>').addClass('latency-bubble');
            
            // Determine bubble color based on comparison with previous latency
            if (regionData[region].previousLatency !== null) {
                if (regionData[region].currentLatency < regionData[region].previousLatency) {
                    bubbleSpan.addClass('faster');
                } else if (regionData[region].currentLatency > regionData[region].previousLatency) {
                    bubbleSpan.addClass('slower');
                } else {
                    bubbleSpan.addClass('same');
                }
            } else {
                bubbleSpan.addClass('same');
            }
            
            var latencyValue = $('<span>')
                .addClass('latency-value')
                .text(regionData[region].currentLatency.toFixed(1) + ' ms');
            
            latencyCell.append(bubbleSpan).append(latencyValue);
            
            row.append(regionCell);
            row.append(latencyCell);
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
        
        // Make sure tabs are initialized first
        if (!$('#tabs').hasClass('ui-tabs')) {
            $("#tabs").tabs();
        }
        
        updateResults(storedTimeRange);
    }

    function toggleNotice($element, type = 'info') {
        const baseClass = 'notice-';
        $element.removeClass(baseClass + 'info ' + baseClass + 'error' + baseClass + 'success')
                .addClass(baseClass + type);
                $element.show();
    }


    $(function() {
        // Initialize tabs first
        $("#tabs").tabs();
        
        // Then proceed with the rest
        checkContinuousTestingStatus();
        checkTestStatus();
        
        // Modify the initializeTimeRange function to ensure proper order
        var storedTimeRange = getStoredTimeRange();
        $('#time-range').val(storedTimeRange);
        
        // Small delay to ensure tabs are fully initialized
        setTimeout(function() {
            updateResults(storedTimeRange);
        }, 100);
        
        setInterval(function() {
            updateResults(getStoredTimeRange());
        }, 60000);
    });
});