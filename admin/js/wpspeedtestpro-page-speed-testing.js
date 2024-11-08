
jQuery(document).ready(function($) {


    var probeInterval;
    var isProbing = false;

  


    function startProbing() {
        if (!isProbing) {
            isProbing = true;
            probeInterval = setInterval(probeForUpdates, 5000); // Probe every 5 seconds
        }
    }

    function stopProbing() {
        if (isProbing) {
            clearInterval(probeInterval);
            isProbing = false;
        }
    }

    function probeForUpdates() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'speedvitals_probe_for_updates',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data.updated_tests);
                }
            },
            error: function() {
                console.log('Error probing for updates');
            }
        });
    }

    function updateResultsTable(updatedTests) {

        function convertToSeconds(milliseconds) {
        return (milliseconds / 1000).toFixed(2) + 's';
    }

    function getColorClass(metric, value) {
        const thresholds = {
            performance_score: { green: 90, amber: 50 },
            first_contentful_paint: { green: 1.8, amber: 3 },
            speed_index: { green: 3.4, amber: 5.8 },
            largest_contentful_paint: { green: 2.5, amber: 4 },
            total_blocking_time: { green: 200, amber: 600 },
            cumulative_layout_shift: { green: 0.1, amber: 0.25 }
        };

        if (metric === 'performance_score') {
            if (value >= thresholds[metric].green) return 'green';
            if (value >= thresholds[metric].amber) return 'amber';
            return 'red';
        } else {
            if (value <= thresholds[metric].green) return 'green';
            if (value <= thresholds[metric].amber) return 'amber';
            return 'red';
        }
    }


    updatedTests.forEach(function(test) {
        var row = $('#test-row-' + test.id);
        if (row.length) {
            // Update existing row
            row.find('td:eq(0)').text(test.id);
            row.find('td:eq(1)').text(test.url);
            row.find('td:eq(2)').text(test.device);
            row.find('td:eq(3)').text(test.location);
            row.find('td:eq(4)').text(new Date(test.created_at).toLocaleString());
            if (test.metrics && typeof test.metrics.performance_score !== 'undefined') {
                row.find('td:eq(5)').text(test.metrics.performance_score)
                    .removeClass('red amber green')
                    .addClass(getColorClass('performance_score', test.metrics.performance_score));
                row.find('td:eq(6)').text(convertToSeconds(test.metrics.first_contentful_paint))
                    .removeClass('red amber green')
                    .addClass(getColorClass('first_contentful_paint', test.metrics.first_contentful_paint / 1000))
                    .show();
                row.find('td:eq(7)').text(convertToSeconds(test.metrics.speed_index))
                    .removeClass('red amber green')
                    .addClass(getColorClass('speed_index', test.metrics.speed_index / 1000))
                    .show();
                row.find('td:eq(8)').text(convertToSeconds(test.metrics.largest_contentful_paint))
                    .removeClass('red amber green')
                    .addClass(getColorClass('largest_contentful_paint', test.metrics.largest_contentful_paint / 1000))
                    .show();
                row.find('td:eq(9)').text(convertToSeconds(test.metrics.total_blocking_time))
                    .removeClass('red amber green')
                    .addClass(getColorClass('total_blocking_time', test.metrics.total_blocking_time))
                    .show();
                row.find('td:eq(10)').text(test.metrics.cumulative_layout_shift.toFixed(2))
                    .removeClass('red amber green')
                    .addClass(getColorClass('cumulative_layout_shift', test.metrics.cumulative_layout_shift))
                    .show();
                row.find('td:eq(11) a').attr('href', test.report_url);
                row.find('td:eq(11) a').attr('href', test.report_url).text('View Report');
                    } else { // Test in progress    
            row.find('td:eq(5)').text('Test in progress....');
            }

        } else {
            // Add new row to the table
            var newRow = '<tr id="test-row-' + test.id + '">' +
                '<td>' + test.id + '</td>' +
                '<td>' + test.url + '</td>' +
                '<td>' + test.device + '</td>' +
                '<td>' + test.location + '</td>' +
                '<td>' + new Date(test.created_at).toLocaleString() + '</td>';
                if (test.metrics && typeof test.metrics.performance_score !== 'undefined') {
                    newRow +=
                    '<td>' + (test.metrics ? (test.metrics.performance_score || 'N/A') : 'N/A') + '</td>' +
                    '<td>' + (test.metrics ? (test.metrics.first_contentful_paint ? convertToSeconds(test.metrics.first_contentful_paint) : 'N/A') : 'N/A') + '</td>' +
                    '<td>' + (test.metrics ? (test.metrics.speed_index ? convertToSeconds(test.metrics.speed_index) : 'N/A') : 'N/A') + '</td>' +
                  '<td>' + (test.metrics ? (test.metrics.largest_contentful_paint ? convertToSeconds(test.metrics.largest_contentful_paint) : 'N/A') : 'N/A') + '</td>' +
                 '<td>' + (test.metrics ? (test.metrics.total_blocking_time ? convertToSeconds(test.metrics.total_blocking_time) : 'N/A') : 'N/A') + '</td>' +
                 '<td>' + (test.metrics ? (test.metrics.cumulative_layout_shift ? test.metrics.cumulative_layout_shift.toFixed(2) : 'N/A') : 'N/A') + '</td>';
                    } else {
                    newRow += '<td>Test in progress.....</td>';
                    newRow += "<td></td><td></td><td></td><td></td><td></td>";
                }
                newRow += '</tr>';
            $('#speedvitals-results-body').prepend(newRow);
        }
    });
}


    $('#pagespeed-info-banner .notice-dismiss').on('click', function(e) {
        e.preventDefault();
        
        const $banner = $(this).closest('#pagespeed-info-banner');
        console.log('Dismissing banner');
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_dismiss_pagespeed_info',
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
                console.error('Failed to dismiss page speed info banner');
            }
        });
    });


    $('#speedvitals-test-form').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serializeArray();
    
    $('#speedvitals-test-status').show();
    $('#speedvitals-loading-gif').show();
    $('#speedvitals-status-message').text('Initiating test...');

    var data = {
        action: 'speedvitals_run_test',
        nonce: wpspeedtestpro_ajax.nonce
    };

    // Convert the serialized array to an object
    $.each(formData, function(i, field) {
        data[field.name] = field.value;
    });

    $.ajax({
        url: wpspeedtestpro_ajax.ajax_url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success && response.data && response.data.id) {
                $('#speedvitals-status-message').text('Test initiated successfully. Results will update automatically.');
                
                var testData = response.data;
                
                // Add a new row for the initiated test
                var newRow = '<tr id="test-row-' + testData.id + '">' +
                    '<td>' + testData.id + '</td>' +
                    '<td>' + testData.url + '</td>' +
                    '<td>' + testData.device + '</td>' +
                    '<td>' + testData.location + '</td>' +
                    '<td>' + new Date(testData.created_at).toLocaleString() + '</td>' +
                    '<td>Test in progress...</td>' +
                    '<td></td>' + '<td></td>' + '<td></td>' + '<td></td>' + '<td></td>' +
                    '<td><a href="#" target="_blank">Report Pending</a></td>' +
                    '</tr>';
                $('#speedvitals-results-body').prepend(newRow);
                
                startProbing(); // Start probing for updates
            } else {
                $('#speedvitals-status-message').text('Error: Unable to initiate test. Please try again.');
                $('#speedvitals-loading-gif').hide();
            }
        },
        error: function() {
            $('#speedvitals-status-message').text('An error occurred. Please try again.');
            $('#speedvitals-loading-gif').hide();
        }
    });
});

    // Start probing when the page loads
    startProbing();

    // Stop probing when the user leaves the page
    $(window).on('beforeunload', function() {
        stopProbing();
    });


    function checkTestStatus(testId) {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'speedvitals_get_test_status',
                nonce: wpspeedtestpro_ajax.nonce,
                test_id: testId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'completed') {
                        $('#speedvitals-status-message').text('Test completed successfully!');
                        $('#speedvitals-loading-gif').hide();
                        // Refresh the results table
                        location.reload();
                    } else {
                        $('#speedvitals-status-message').text('Test in progress: ' + response.data.status);
                        setTimeout(function() {
                            checkTestStatus(testId);
                        }, 10000);
                    }
                } else {
                    $('#speedvitals-status-message').text('Error: ' + response.data);
                    $('#speedvitals-loading-gif').hide();
                }
            },
            error: function() {
                $('#speedvitals-status-message').text('An error occurred while checking test status.');
                $('#speedvitals-loading-gif').hide();
            }
        });
    }

    $('.speedvitals-cancel-scheduled-test').on('click', function() {
        var scheduleId = $(this).data('id');
        if (confirm('Are you sure you want to cancel this scheduled test?')) {
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'speedvitals_cancel_scheduled_test',
                    nonce: wpspeedtestpro_ajax.nonce,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Scheduled test cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    $('#speedvitals-delete-old-results-form').on('submit', function(e) {
        e.preventDefault();
        var days = $('#speedvitals-delete-days').val();
        
        if (confirm('Are you sure you want to delete results older than ' + days + ' days?')) {
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'speedvitals_delete_old_results',
                    nonce: wpspeedtestpro_ajax.nonce,
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        alert('Old results deleted successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });
});
