jQuery(document).ready(function($) {
    // Test form submission
    $('#pagespeed-test-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const $status = $('#test-status');
        
        // Disable submit button and show status
        $submit.prop('disabled', true);
        $status.show();

        // Collect form data
        const data = {
            action: 'pagespeed_run_test',
            nonce: $('#pagespeed_test_nonce').val(),
            url: $('#test-url').val(),
            device: $('#test-device').val(),
            frequency: $('#test-frequency').val()
        };

        // Run the test
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                if (response.data.status === 'complete') {
                    displayResults(response.data.results);
                    loadTestHistory();
                    if (data.frequency !== 'once') {
                        loadScheduledTests();
                    }
                }
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            alert('Failed to run test. Please try again.');
        }).always(function() {
            $submit.prop('disabled', false);
            $status.hide();
        });
    });

    // Handle old results deletion
    $('#delete-old-results').on('click', function() {
        if (!confirm('Are you sure you want to delete old results?')) {
            return;
        }

        const days = $('#days-to-keep').val();
        
        $.post(ajaxurl, {
            action: 'pagespeed_delete_old_results',
            nonce: $('#pagespeed_test_nonce').val(),
            days: days
        }, function(response) {
            if (response.success) {
                loadTestHistory();
            } else {
                alert('Error deleting results: ' + response.data);
            }
        });
    });

    // Display test results
    function displayResults(results) {
        const $results = $('#latest-results');
        
        // Update desktop results
        if (results.desktop) {
            updateDeviceResults('desktop', results.desktop);
        }
        
        // Update mobile results
        if (results.mobile) {
            updateDeviceResults('mobile', results.mobile);
        }
        
        $results.show();
    }

    function updateDeviceResults(device, data) {
        const $container = $(`.device-results.${device}`);
        
        // Update main performance score
        const $score = $container.find('.score-circle.performance');
        $score.find('.score-value').text(data.performance_score);
        updateScoreClass($score, data.performance_score);

        // Update other scores
        $container.find('.scores-grid .score-item').each(function() {
            const $item = $(this);
            const metric = $item.find('.label').text().toLowerCase().replace(' ', '_') + '_score';
            const value = data[metric];
            $item.find('.value').text(value);
            updateScoreClass($item, value);
        });

        // Update Core Web Vitals
        updateWebVital($container, 'FCP', data.fcp, 's', 2.5);
        updateWebVital($container, 'LCP', data.lcp, 's', 2.5);
        updateWebVital($container, 'CLS', data.cls, '', 0.1);
        updateWebVital($container, 'TBT', data.tbt, 'ms', 300);
    }

    function updateWebVital($container, metric, value, unit, threshold) {
        const $metric = $container.find(`.metric-item:contains("${metric}")`);
        let displayValue = value;
        
        if (unit === 's') {
            displayValue = (value / 1000).toFixed(1);
        } else if (unit === 'ms') {
            displayValue = Math.round(value);
        }
        
        $metric.find('.value').text(displayValue + unit);
        updateScoreClass($metric, value <= threshold ? 100 : 0);
    }

    function updateScoreClass($element, score) {
        $element.removeClass('poor average good')
                .addClass(getScoreClass(score));
    }

    function getScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }

    // Load test history
    function loadTestHistory() {
        $.post(ajaxurl, {
            action: 'pagespeed_get_test_results',
            nonce: $('#pagespeed_test_nonce').val()
        }, function(response) {
            if (response.success) {
                updateTestHistory(response.data);
            }
        });
    }

    // Load scheduled tests
    function loadScheduledTests() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pagespeed_get_scheduled_tests',
                nonce: wpspeedtestpro_pagespeed.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayScheduledTests(response.data);
                } else {
                    console.error('Failed to load scheduled tests:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }
    // Initialize page
 
    function displayScheduledTests(tests) {
        const $tbody = $('#pagespeed-scheduled-body');
        $tbody.empty();
    
        if (tests.length === 0) {
            $tbody.append(`
                <tr>
                    <td colspan="5" class="no-items">No scheduled tests found.</td>
                </tr>
            `);
            return;
        }
    
        tests.forEach(function(test) {
            const statusClass = getStatusClass(test.status);
            $tbody.append(`
                <tr>
                    <td>${escapeHtml(test.url)}</td>
                    <td>${escapeHtml(test.frequency)}</td>
                    <td>${escapeHtml(test.last_run)}</td>
                    <td>
                        <span class="status-indicator ${statusClass}">
                            ${escapeHtml(test.next_run)}
                        </span>
                    </td>
                    <td>
                        <button type="button" 
                                class="button button-small cancel-schedule" 
                                data-id="${test.id}">
                            Cancel
                        </button>
                        ${test.status === 'overdue' ? `
                            <button type="button" 
                                    class="button button-small run-now" 
                                    data-id="${test.id}">
                                Run Now
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `);
        });
    }
    
    function getStatusClass(status) {
        switch(status) {
            case 'active':
                return 'status-active';
            case 'overdue':
                return 'status-overdue';
            case 'inactive':
                return 'status-inactive';
            default:
                return '';
        }
    }
    
    // Helper function to safely escape HTML
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    
});




// Event handlers for scheduled test actions
jQuery(document).on('click', '.cancel-schedule', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (confirm('Are you sure you want to cancel this scheduled test?')) {
        cancelScheduledTest(id);
    }
});

jQuery(document).on('click', '.run-now', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    runScheduledTest(id);
});

// Load scheduled tests when page loads
jQuery(document).ready(function() {
    loadScheduledTests();
    loadTestHistory();
    // Refresh every 5 minutes
    setInterval(loadScheduledTests, 5 * 60 * 1000);
    
});

