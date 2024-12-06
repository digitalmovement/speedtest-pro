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
        $.post(ajaxurl, {
            action: 'pagespeed_get_scheduled_tests',
            nonce: $('#pagespeed_test_nonce').val()
        }, function(response) {
            if (response.success) {
                updateScheduledTests(response.data);
            }
        });
    }

    // Initialize page
    loadTestHistory();
    loadScheduledTests();
});