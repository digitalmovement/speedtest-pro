jQuery(document).ready(function($) {
    // Test form submission
    $('#pagespeed-test-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const $status = $('#test-status');
        const $error_message = $('#error-message');

        
        // Disable submit button and show status
        $submit.prop('disabled', true);
        $status.show().html('<p>Initiating test...</p>');
    
        // Collect form data
        const data = {
            action: 'pagespeed_run_test',
            nonce: $('#pagespeed_test_nonce').val(),
            url: $('#test-url').val(),
            device: $('#test-device').val(),
            frequency: $('#test-frequency').val()
        };
    
        // Start the test
        $.post(ajaxurl, data, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkTestStatus(data.url);
            } else {
                $error_message.html('<p class="error">Error: ' + (response.data || 'Failed to start test') + '</p>');
                $error_message.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('<p class="error">Failed to communicate with server</p>');
            $submit.prop('disabled', false);
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


    function checkTestStatus(url) {
        const $status = $('#test-status');
        const $submit = $('#pagespeed-test-form button[type="submit"]');
        
        $.post(ajaxurl, {
            action: 'pagespeed_check_test_status',
            nonce: $('#pagespeed_test_nonce').val(),
            url: url
        }, function(response) {
            if (!response.success) {
                $status.html('<p class="error">Error: ' + response.data + '</p>');
                $submit.prop('disabled', false);
                return;
            }
    
            if (response.data.status === 'running') {
                // Update progress message
                $status.html('<p>Test in progress.... <span class="spinner is-active"></span></p>');
                // Check again in 5 seconds
                setTimeout(() => checkTestStatus(url), 5000);
            } else if (response.data.status === 'complete') {
                // Display results
                displayResults(response.data.results);
                loadTestHistory();
                
                // Update UI
                $status.hide();
                $submit.prop('disabled', false);
                
                // Check if we need to reload scheduled tests
                if ($('#test-frequency').val() !== 'once') {
                    loadScheduledTests();
                }
            }
        }).fail(function() {
            $status.html('<p class="error">Failed to check test status</p>');
            $submit.prop('disabled', false);
        });
    }


    // Function to cancel a scheduled test
    function cancelScheduledTest(id) {
        $.post(ajaxurl, {
            action: 'pagespeed_cancel_scheduled_test',
            nonce: $('#pagespeed_test_nonce').val(),
            test_id: id
        }, function(response) {
            if (response.success) {
                loadScheduledTests();
            } else {
                alert('Error canceling test: ' + response.data);
            }
        });
    }

    // Function to run a scheduled test immediately
    function runScheduledTest(id) {
        $.post(ajaxurl, {
            action: 'pagespeed_run_scheduled_test',
            nonce: $('#pagespeed_test_nonce').val(),
            test_id: id
        }, function(response) {
            if (response.success) {
                loadScheduledTests();
                loadTestHistory();
            } else {
                alert('Error running test: ' + response.data);
            }
        });
    }

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
                updateTestHistory(response.data.results);
                updatePagination(response.data.pagination);
            }
        });
    }

    function updateTestHistory(results) {
        const $tbody = $('#test-results');
        $tbody.empty();

        results.forEach(function(result) {
            $tbody.append(`
                <tr>
                    <td>${escapeHtml(result.url)}</td>
                    <td>${result.device}</td>
                    <td class="${result.performance.class}">${result.performance.score}%</td>
                    <td class="${result.accessibility.class}">${result.accessibility.score}%</td>
                    <td class="${result.best_practices.class}">${result.best_practices.score}%</td>
                    <td class="${result.seo.class}">${result.seo.score}%</td>
                    <td>${result.test_date}</td>
                    <td>
                        <button type="button" class="button button-small view-details" 
                                data-id="${result.id}">View Details</button>
                    </td>
                </tr>
            `);
        });
    }

    function updatePagination(pagination) {
        const $pagination = $('.tablenav-pages');
        if (pagination.total_pages <= 1) {
            $pagination.hide();
            return;
        }

        let html = '<span class="displaying-num">' + pagination.total_items + ' items</span>';
        
        if (pagination.current_page > 1) {
            html += `<a class="prev-page" href="#" data-page="${pagination.current_page - 1}">‹</a>`;
        }
        
        html += '<span class="paging-input">' + pagination.current_page + ' of ' + pagination.total_pages + '</span>';
        
        if (pagination.current_page < pagination.total_pages) {
            html += `<a class="next-page" href="#" data-page="${pagination.current_page + 1}">›</a>`;
        }

        $pagination.html(html).show();
    }

    // Load scheduled tests
    function loadScheduledTests() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pagespeed_get_scheduled_tests',
                nonce: $('#pagespeed_test_nonce').val()
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
                        <button type="button" class="button button-small cancel-schedule" 
                                data-id="${test.id}">Cancel</button>
                        ${test.status === 'overdue' ? `
                            <button type="button" class="button button-small run-now" 
                                    data-id="${test.id}">Run Now</button>
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
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Event handlers for scheduled test actions
    $(document).on('click', '.cancel-schedule', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (confirm('Are you sure you want to cancel this scheduled test?')) {
            cancelScheduledTest(id);
        }
    });

    $(document).on('click', '.run-now', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        runScheduledTest(id);
    });

    // Pagination event handlers
    $(document).on('click', '.prev-page, .next-page', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadTestHistory(page);
    });

    // View details handler
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        // Implement view details functionality
        // This could open a modal or expand the row with more details
    });

    // Initialize page
    loadScheduledTests();
    loadTestHistory();
    
    // Refresh every 5 minutes
    setInterval(loadScheduledTests, 5 * 60 * 1000);
});