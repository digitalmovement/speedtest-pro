jQuery(document).ready(function($) {
    // Handle quick test button clicks
    $(document).on('click', '.quick-test-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $container = $button.closest('.pagespeed-scores');
        const $status = $container.find('.pagespeed-test-status');
        const url = $container.data('url');
        
        // Disable button and show status
        $button.prop('disabled', true);
        $status.html('<span class="spinner is-active"></span>Initiating test...').show();

        // Start the test
        $.post(wpspeedtestpro_list.ajax_url, {
            action: 'pagespeed_run_test',
            nonce: wpspeedtestpro_list.nonce,
            url: url,
            device: 'both',
            frequency: 'once'
        }, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkTestStatus(url, $container);
            } else {
                $status.html('Error: ' + (response.data || 'Failed to start test'));
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('Failed to communicate with server');
            $button.prop('disabled', false);
        });
    });

    function checkTestStatus(url, $container) {
        const $button = $container.find('.quick-test-button');
        const $status = $container.find('.pagespeed-test-status');

        $.post(wpspeedtestpro_list.ajax_url, {
            action: 'pagespeed_check_test_status',
            nonce: wpspeedtestpro_list.nonce,
            url: url
        }, function(response) {
            if (!response.success) {
                $status.html('Error: ' + response.data);
                $button.prop('disabled', false);
                return;
            }

            if (response.data.status === 'running') {
                $status.html('<span class="spinner is-active"></span>Test in progress...');
                setTimeout(() => checkTestStatus(url, $container), 5000);
            } else if (response.data.status === 'complete') {
                // Update the display with new results
                updateResults($container, response.data.results);
                $status.html('Test completed successfully').fadeOut(2000);
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('Failed to check test status');
            $button.prop('disabled', false);
        });
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

        $container.find('.pagespeed-device').remove();
        $container.prepend(html);
    }

    function getScoreClass(score) {
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }
});