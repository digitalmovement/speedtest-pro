jQuery(document).ready(function($) {
    // Test form submission
    $('#pagespeed-test-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const $status = $('#test-status');


        
        // Disable submit button and show status
        $submit.prop('disabled', true);
        toggleNotice($status, 'info');
        $status.show().html('<p>Initiating test...</p><div class="test-progress"></div>');
    
        // Collect form data
        const data = {
            action: 'pagespeed_run_test',
            nonce: $('#wpspeedtestpro_ajax_nonce').val(),
            url: $('#test-url').val(),
            device: $('#test-device').val(),
            frequency: $('#test-frequency').val()
        };
    
        // Start the test
        $.post(ajaxurl, data, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkTestStatus(data.url);
            } else {
                toggleNotice($status, 'error');
                $status.html('<p class="error">Error: ' + (response.data || 'Failed to start test') + '</p>');
                $submit.prop('disabled', false);
            }
        }).fail(function() {
            toggleNotice($status, 'error');
            $status.html('<p class="error">Failed to communicate with server</p>');
            $submit.prop('disabled', false);
        });
    });

    // Handle old results deletion
    $('#delete-old-results').on('click', function() {

        const $status = $('#test-status');
        
        if (!confirm('Are you sure you want to delete old results?')) {
            return;
        }

        const days = $('#days-to-keep').val();
        
        $.post(ajaxurl, {
            action: 'pagespeed_delete_old_results',
            nonce: $('#wpspeedtestpro_ajax_nonce').val(),
            days: days
        }, function(response) {
            if (response.success) {
                loadTestHistory();
                toggleNotice($status, 'info');
                $status.show().html('<p>' + response.data.message + '</p>');

            } else {
                alert('Error deleting results: ' + response.data);
            }
        });
    });

    $('body').append(`
        <div id="test-details-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>PageSpeed Test Details</h2>
                <div id="test-details-content">
                    <div class="loading">Loading...</div>
                </div>
            </div>
        </div>
    `);

    // Update the view details handler
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        showTestDetails(id);
    });

    // Close modal when clicking the close button or outside the modal
    $('.close-modal, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#test-details-modal').hide();
        }
    });

    function showTestDetails(testId) {
        const $modal = $('#test-details-modal');
        const $content = $('#test-details-content');
        
        $modal.show();
        $content.html('<div class="loading">Loading...</div>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pagespeed_get_test_details',
                nonce: $('#wpspeedtestpro_ajax_nonce').val(),
                test_id: testId
            },
            success: function(response) {
                if (!response.success) {
                    $content.html('<div class="error">Error loading test details</div>');
                    return;
                }

                const data = response.data;
                let html = `
                    <div class="test-details-grid">
                        <div class="basic-info">
                            <div class="score-circle ${getScoreClass(data.scores.performance.score)}">
                                <span class="score-label">Performance</span>
                                <span class="score-value">${data.scores.performance.score}</span>
                            </div>
                            <div>
                                <div class="scores-grid">
                                    <div class="score-item">
                                        <span class="score-label">Accessibility</span>
                                        <span class="score ${data.scores.accessibility.class}">${data.scores.accessibility.score}%</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Best Practices</span>
                                        <span class="score ${data.scores.best_practices.class}">${data.scores.best_practices.score}%</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">SEO</span>
                                        <span class="score ${data.scores.seo.class}">${data.scores.seo.score}%</span>
                                    </div>
                                </div>
                                <div class="meta-info">
                                    <p><strong>URL:</strong> ${escapeHtml(data.basic_info.url)}</p>
                                    <p><strong>Device:</strong> ${data.basic_info.device}</p>
                                    <p><strong>Test Date:</strong> ${data.basic_info.test_date}</p>
                                </div>
                            </div>
                        </div>

                        <div class="metrics-section">
                            <h3>Core Web Vitals & Metrics</h3>
                            <div class="metrics-grid">
                                ${generateMetricItems(data.metrics)}
                            </div>
                        </div>

                        <div class="audits-section">
                            <h3>Detailed Audits</h3>
                            <div class="audits-list">
                                ${Object.entries(data.audits)
                                    .filter(([_, audit]) => audit.score !== null)
                                    .map(([key, audit]) => `
                                        <div class="audit-item ${getScoreClass(audit.score * 100)}">
                                            <div class="audit-content">
                                                <div class="audit-title">${audit.title}</div>
                                                <div class="audit-description">${audit.description}</div>
                                            </div>
                                            <div class="audit-score">${Math.round(audit.score * 100)}%</div>
                                        </div>
                                    `).join('')}
                            </div>
                        </div>
                    </div>
                `;

                $content.html(html);
            },
            error: function() {
                $content.html('<div class="error">Failed to load test details</div>');
            }
        });
    }

    function generateMetricItems(metrics) {
        const metricThresholds = {
            'First Contentful Paint': {
                good: 1800,
                poor: 3000,
                format: formatTiming
            },
            'Largest Contentful Paint': {
                good: 2500,
                poor: 4000,
                format: formatTiming
            },
            'Cumulative Layout Shift': {
                good: 0.1,
                poor: 0.25,
                format: formatCLS
            },
            'Total Blocking Time': {
                good: 200,
                poor: 600,
                format: formatTiming
            }
        };

        return Object.entries(metrics).map(([key, value]) => {
            const threshold = metricThresholds[key];
            if (!threshold) return '';

            const numericValue = parseFloat(value);
            const scoreClass = getMetricScoreClass(numericValue, threshold);
            const formattedValue = threshold.format(value);

            return `
                <div class="metric-item ${scoreClass}">
                    <div class="metric-label">${key}</div>
                    <div class="metric-value">${formattedValue}</div>
                </div>
            `;
        }).join('');
    }

    function getMetricScoreClass(value, threshold) {
        if (value <= threshold.good) return 'good';
        if (value <= threshold.poor) return 'average';
        return 'poor';
    }

    function formatTiming(value) {
        const numValue = parseFloat(value);
        if (isNaN(numValue)) return 'N/A';
        return numValue >= 1000 ? 
            (numValue / 1000).toFixed(2) + 's' : 
            Math.round(numValue) + 'ms';
    }

    function formatCLS(value) {
        return parseFloat(value).toFixed(3);
    }

    function getScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Update display results function to use new scoring
    function displayResults(results) {
        const $results = $('#latest-results');
        
        if (results.desktop) {
            updateDeviceResults('desktop', results.desktop);
        }
        
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

        // Update Core Web Vitals with new thresholds
        updateWebVital($container, 'FCP', data.fcp, 1800, 3000);
        updateWebVital($container, 'LCP', data.lcp, 2500, 4000);
        updateWebVital($container, 'CLS', data.cls, 0.1, 0.25);
        updateWebVital($container, 'TBT', data.tbt, 200, 600);
    }

    function updateWebVital($container, metric, value, goodThreshold, poorThreshold) {
        const $metric = $container.find(`.metric-item:contains("${metric}")`);
        const numValue = parseFloat(value);

        let displayValue;
        if (metric === 'CLS') {
            displayValue = numValue.toFixed(3);
        } else {
            displayValue = numValue >= 1000 ? 
                (numValue / 1000).toFixed(2) + 's' : 
                Math.round(numValue) + 'ms';
        }

        $metric.find('.value').text(displayValue);
        
        // Apply color class based on thresholds
        $metric.removeClass('good average poor');
        if (numValue <= goodThreshold) {
            $metric.addClass('good');
        } else if (numValue <= poorThreshold) {
            $metric.addClass('average');
        } else {
            $metric.addClass('poor');
        }
    }



    function checkTestStatus(url) {
        const $status = $('#test-status');
        const $submit = $('#pagespeed-test-form button[type="submit"]');
        
        $.post(ajaxurl, {
            action: 'pagespeed_check_test_status',
            nonce: $('#wpspeedtestpro_ajax_nonce').val(),
            url: url
        }, function(response) {
            if (!response.success) {
                toggleNotice($status, 'error');
                $status.html('<p class="error">Error: ' + response.data + '</p>');
                $submit.prop('disabled', false);
                return;
            }
    
            if (response.data.status === 'running') {
                // Update progress message
                toggleNotice($status, 'info');
                $status.html('<p>Test in progress....</p><div class="test-progress"></div>');
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
            toggleNotice($status, 'error');
            $status.html('<p class="error">Failed to check test status</p>');
            $submit.prop('disabled', false);
        });
    }


    // Function to cancel a scheduled test
    function cancelScheduledTest(id) {
        $.post(ajaxurl, {
            action: 'pagespeed_cancel_scheduled_test',
            nonce: $('#wpspeedtestpro_ajax_nonce').val(),
            schedule_id: id
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
        const $button = $(`.run-now[data-id="${id}"]`);
        const originalText = $button.text();
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('Running...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pagespeed_run_scheduled_test',
                nonce: $('#wpspeedtestpro_ajax_nonce').val(),
                schedule_id: id
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const $row = $button.closest('tr');
                    const $status = $('<div class="notice notice-success inline"><p>Test initiated successfully</p></div>')
                        .insertAfter($row);
                    
                    // Start monitoring the test status
                    checkScheduledTestStatus(id);
                    
                    // Add a loading indicator
                    $row.find('td:last').append('<span class="spinner is-active"></span>');
                } else {
                    alert('Error running test: ' + (response.data || 'Unknown error'));
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Failed to run test. Please try again.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function checkScheduledTestStatus(scheduleId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pagespeed_check_scheduled_test_status',
                nonce: $('#wpspeedtestpro_ajax_nonce').val(),
                schedule_id: scheduleId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'complete') {
                        // Remove any existing status messages and spinners
                        $('.notice.inline').remove();
                        $('.spinner').remove();
                        
                        // Show completion message
                        const $row = $(`.run-now[data-id="${scheduleId}"]`).closest('tr');
                        $('<div class="notice notice-success inline"><p>Test completed successfully</p></div>')
                            .insertAfter($row)
                            .delay(3000)
                            .fadeOut(400, function() { $(this).remove(); });
                        
                        // Re-enable the Run Now button
                        $(`.run-now[data-id="${scheduleId}"]`).prop('disabled', false).text('Run Now');
                        
                        // Reload the data
                        loadScheduledTests();
                        loadTestHistory();
                    } else if (response.data.status === 'running') {
                        // Check again in 5 seconds
                        setTimeout(() => checkScheduledTestStatus(scheduleId), 5000);
                    } else {
                        // Handle error or unknown status
                        $('.spinner').remove();
                        $(`.run-now[data-id="${scheduleId}"]`).prop('disabled', false).text('Run Now');
                        alert('Test status unknown. Please check the results page.');
                    }
                }
            },
            error: function() {
                // Handle error
                $('.spinner').remove();
                $(`.run-now[data-id="${scheduleId}"]`).prop('disabled', false).text('Run Now');
                alert('Failed to check test status. Please check the results page.');
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
            nonce: $('#wpspeedtestpro_ajax_nonce').val()
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
                    <td class="${result.performance.class}"><span>${result.performance.score}%</span></td>
                    <td class="${result.accessibility.class}"><span>${result.accessibility.score}%</span></td>
                    <td class="${result.best_practices.class}"><span>${result.best_practices.score}%</span></td>
                    <td class="${result.seo.class}"><span>${result.seo.score}%</span></td>
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
                nonce: $('#wpspeedtestpro_ajax_nonce').val()
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
        const $tbody = $('#scheduled-tests');
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
                        <button type="button" class="button button-small run-now" 
                                data-id="${test.id}">Run Now</button>
                        <button type="button" class="button button-small cancel-schedule" 
                                data-id="${test.id}">Cancel</button>
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

    function toggleNotice($element, type = 'info') {
        const baseClass = 'notice-';
        $element.removeClass(baseClass + 'info ' + baseClass + 'error' + baseClass + 'success')
                .addClass(baseClass + type);
    }


    // Initialize page
    loadScheduledTests();
    loadTestHistory();
    
    // Refresh every 5 minutes
    setInterval(loadScheduledTests, 5 * 60 * 1000);
});