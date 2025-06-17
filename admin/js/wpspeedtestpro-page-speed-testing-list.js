jQuery(document).ready(function($) {
    let activeTest = null;

    // Handle quick test button clicks
    $(document).on('click', '.quick-test-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $container = $button.closest('.pagespeed-scores');
        const postStatus = $container.data('status');

        // Check if post is published
        if (postStatus !== 'publish') {
            alert('This content must be published before running a PageSpeed test.');
            return;
        }
        
        // Check for active test
        if (activeTest) {
            alert('A test is already running. Please wait for it to complete.');
            return;
        }

        const $status = $container.find('.pagespeed-test-status');
        const url = $container.data('url');
        
        // Set this as the active test
        activeTest = url;

        // Disable all test buttons while a test is running
        $('.quick-test-button').prop('disabled', true);
        
        // Remove "No test" text if it exists
        $container.find('.no-test-text').remove();
        $container.find('.no-test-indicator').remove();

        $status.html('<span class="spinner is-active"></span>Starting page testing...').show();

        // Start the test
        $.post(wpspeedtestpro_list.ajax_url, {
            action: 'wpspeedtestpro_pagespeed_run_test',
            nonce: wpspeedtestpro_list.nonce,
            url: url,
            device: 'both',
            frequency: 'once'
        }, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkTestStatus(url, $container);
            } else {
                $status.html('Error: ' + (response.data || 'Failed to start test'));
                resetTestState();
            }
        }).fail(function() {
            $status.html('Failed to communicate with server');
            resetTestState();
        });
    });


    function checkTestStatus(url, $container) {
        const $button = $container.find('.quick-test-button');
        const $status = $container.find('.pagespeed-test-status');

        $.post(wpspeedtestpro_list.ajax_url, {
            action: 'wpspeedtestpro_pagespeed_check_test_status',
            nonce: wpspeedtestpro_list.nonce,
            url: url
        }, function(response) {
            if (!response.success) {
                $status.html('Error: ' + response.data);
                resetTestState();
                return;
            }

            if (response.data.status === 'running') {
                $status.html('<span class="spinner is-active"></span>Test in progress...');
                setTimeout(() => checkTestStatus(url, $container), 5000);
            } else if (response.data.status === 'complete') {
                // Update the display with new results
                updateResults($container, response.data.results);
                $status.fadeOut(1000, function() {
                    $(this).empty().show().css('display', 'inline-block');
                });
                resetTestState();
            }
        }).fail(function() {
            $status.html('Failed to check test status');
            resetTestState();
        });
    }

    function resetTestState() {
        activeTest = null;
        $('.quick-test-button').prop('disabled', false);
    }

    function updateResults($container, results) {
        let html = '';

        if (results.desktop) {
            const desktopClass = getScoreClass(results.desktop.performance_score);
            html += `
                <div class="pagespeed-device">
                    <i class="fas fa-desktop"></i>
                    <span class="pagespeed-indicator ${desktopClass}"></span>
                    <span class="pagespeed-score">${results.desktop.performance_score}%</span>
                </div>`;
        }

        if (results.mobile) {
            const mobileClass = getScoreClass(results.mobile.performance_score);
            html += `
                <div class="pagespeed-device">
                    <i class="fas fa-mobile-screen"></i>
                    <span class="pagespeed-indicator ${mobileClass}"></span>
                    <span class="pagespeed-score">${results.mobile.performance_score}%</span>
                </div>`;
        }

        // Remove any existing scores or "No test" message
        $container.find('.pagespeed-device, .no-test-text').remove();
        $container.prepend(html);
    }

    function getScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }
});