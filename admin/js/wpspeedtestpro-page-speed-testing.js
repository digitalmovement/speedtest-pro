jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('href');
        
        // Update active tab
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content
        $('.tab-content').removeClass('active');
        $(targetTab).addClass('active');
        
        // Refresh data if needed
        if (targetTab === '#results-tab') {
            loadTestResults();
        } else if (targetTab === '#scheduled-tab') {
            loadScheduledTests();
        }
    });

    // Run test form submission
    $('#pagespeed-test-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('#run-test');
        var $spinner = $form.find('.spinner');
        
        $submit.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'run_pagespeed_test',
                nonce: $('#pagespeed_test_nonce').val(),
                url: $('#test-url').val(),
                frequency: $('#test-frequency').val()
            },
            success: function(response) {
                if (response.success) {
                    updateLatestResults(response.data);
                    $('#latest-results').show();
                } else {
                    alert('Error running test: ' + response.data);
                }
            },
            error: function() {
                alert('Error communicating with server');
            },
            complete: function() {
                $submit.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Delete old results
    $('#delete-old-results').on('click', function() {
        if (!confirm('Are you sure you want to delete old results?')) {
            return;
        }
        
        var days = $('#days-to-keep').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_old_pagespeed_results',
                nonce: $('#pagespeed_test_nonce').val(),
                days: days
            },
            success: function(response) {
                if (response.success) {
                    loadTestResults();
                } else {
                    alert('Error deleting results: ' + response.data);
                }
            }
        });
    });

    function loadTestResults() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pagespeed_results',
                nonce: $('#pagespeed_test_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                }
            }
        });
    }

    function loadScheduledTests() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_scheduled_pagespeed_tests',
                nonce: $('#pagespeed_test_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    updateScheduledTable(response.data);
                }
            }
        });
    }

    function updateLatestResults(results) {
        // Update desktop scores
        $('.desktop .performance .score').text(results.desktop.performance);
        $('.desktop .metrics-grid .metric').each(function(index) {
            var metrics = ['accessibility', 'best_practices', 'seo'];
            $(this).find('.score').text(results.desktop[metrics[index]]);
        });

        // Update mobile scores
        $('.mobile .performance .score').text(results.mobile.performance);
        $('.mobile .metrics-grid .metric').each(function(index) {
            var metrics = ['accessibility', 'best_practices', 'seo'];
            $(this).find('.score').text(results.mobile[metrics[index]]);
        });

        // Update Core Web Vitals
        $('.core-web-vitals .metric').each(function(index) {
            var metrics = ['fcp', 'lcp', 'cls', 'fid'];
            $(this).find('.score').text(results.desktop[metrics[index]]);
        });

        // Update score colors
        $('.score').each(function() {
            var score = parseInt($(this).text());
            $(this).closest('.metric, .score-circle')
                .removeClass('poor average good')
                .addClass(getScoreClass(score));
        });
    }

    function updateResultsTable(results) {
        var $tbody = $('#results-table-body');
        $tbody.empty();
        
        results.forEach(function(result) {
            var row = `
                <tr>
                    <td>${result.url}</td>
                    <td>${formatDate(result.test_date)}</td>
                    <td class="${getScoreClass(result.desktop_performance)}">
                        ${result.desktop_performance}%
                    </td>
                    <td class="${getScoreClass(result.mobile_performance)}">
                        ${result.mobile_performance}%
                    </td>
                    <td class="${getScoreClass(result.desktop_accessibility)}">
                        ${result.desktop_accessibility}%
                    </td>
                    <td class="${getScoreClass(result.desktop_best_practices)}">
                        ${result.desktop_best_practices}%
                    </td>
                    <td class="${getScoreClass(result.desktop_seo)}">
                        ${result.desktop_seo}%
                    </td>
                    <td>
                        <button type="button" class="button view-details" data-id="${result.id}">
                            View Details
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
    }

    function updateScheduledTable(tests) {
        var $tbody = $('#scheduled-table-body');
        $tbody.empty();
        
        tests.forEach(function(test) {
            var row = `
                <tr>
                    <td>${test.url}</td>
                    <td>${test.frequency}</td>
                    <td>${formatDate(test.last_run)}</td>
                    <td>${formatDate(test.next_run)}</td>
                    <td>${test.active ? 'Active' : 'Inactive'}</td>
                    <td>
                        <button type="button" class="button toggle-schedule" data-id="${test.id}">
                            ${test.active ? 'Pause' : 'Resume'}
                        </button>
                        <button type="button" class="button delete-schedule" data-id="${test.id}">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
    }

    function getScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }

    function formatDate(dateString) {
        if (!dateString) return 'Never';
        return new Date(dateString).toLocaleString();
    }

    // Initial load
    loadTestResults();
    loadScheduledTests();
});