jQuery(document).ready(function($) {
    // Handler for running PageSpeed test from metabox

    if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
        let previousStatus = wp.data.select('core/editor')?.getCurrentPost()?.status || '';
        
        wp.data.subscribe(() => {
            const currentPost = wp.data.select('core/editor')?.getCurrentPost();
            const currentStatus = currentPost?.status || '';
            
            // Check if status changed to 'publish'
            if (previousStatus !== 'publish' && currentStatus === 'publish') {
                enablePageSpeedTest(currentPost.link);
            }
            
            previousStatus = currentStatus;
        });
    }

    // For classic editor
    $(document).on('heartbeat-send', function(e, data) {
        if ($('form#post').length && data.wp_autosave) {
            data.check_post_status = true;
        }
    });

    $(document).on('heartbeat-tick', function(e, data) {
        if (data.check_post_status && data.post_status === 'publish') {
            const postUrl = $('#post_permalink').val() || $('#sample-permalink a').attr('href');
            if (postUrl) {
                enablePageSpeedTest(postUrl);
            }
        }
    });

    function enablePageSpeedTest(postUrl) {
        const $button = $('.pagespeed-meta-box .run-pagespeed-test');
        const $notice = $('.pagespeed-meta-box .notice-warning');
        
        // Update button
        $button.prop('disabled', false)
               .data('url', postUrl)
               .data('post-status', 'publish');
        
        // Remove warning notice
        $notice.slideUp(300, function() {
            $(this).remove();
        });
    }

    // Existing MetaBox code...
    $('.pagespeed-meta-box .run-pagespeed-test').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $metabox = $button.closest('.pagespeed-meta-box');
        const $status = $metabox.find('.test-status');
        const url = $button.data('url');
        const postStatus = $button.data('post-status');

        // Check if post is published
        if (postStatus !== 'publish') {
            alert('Please publish this post before running PageSpeed tests.');
            return;
        }

        // Disable button and show loading state
        $button.prop('disabled', true);
        $status.show().html('<p>Running PageSpeed test...</p><div class="test-progress"></div>');

        // Start the test
        $.post(ajaxurl, {
            action: 'pagespeed_run_test',
            nonce: $('#pagespeed_test_nonce').val(),
            url: url,
            device: 'both',
            frequency: 'once'
        }, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkMetaBoxTestStatus(url, $metabox);
            } else {
                $status.html('<p class="error">Error: ' + (response.data || 'Failed to start test') + '</p>');
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('<p class="error">Failed to communicate with server</p>');
            $button.prop('disabled', false);
        });
    });


    // origial code
    /*
    $('.pagespeed-meta-box .run-pagespeed-test').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $metabox = $button.closest('.pagespeed-meta-box');
        const $status = $metabox.find('.test-status');
        const url = $button.data('url');
        const postStatus = $button.data('post-status');

        // Check if post is published
        if (postStatus !== 'publish') {
            alert('Please publish this post before running PageSpeed tests.');
            return;
        }

        // Disable button and show loading state
        $button.prop('disabled', true);
        $status.show().html('<p>Running PageSpeed test...</p><div class="test-progress"></div>');

        // Start the test
        $.post(ajaxurl, {
            action: 'pagespeed_run_test',
            nonce: $('#pagespeed_test_nonce').val(),
            url: url,
            device: 'both',
            frequency: 'once'
        }, function(response) {
            if (response.success && response.data.status === 'initiated') {
                checkMetaBoxTestStatus(url, $metabox);
            } else {
                $status.html('<p class="error">Error: ' + (response.data || 'Failed to start test') + '</p>');
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('<p class="error">Failed to communicate with server</p>');
            $button.prop('disabled', false);
        });
    });
*/
    // Handler for viewing test details from metabox
    $(document).on('click', '.pagespeed-meta-box .view-details', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        showTestDetails(id); // Reuse the existing showTestDetails function
    });

    function checkMetaBoxTestStatus(url, $metabox) {
        const $status = $metabox.find('.test-status');
        const $button = $metabox.find('.run-pagespeed-test');
        
        $.post(ajaxurl, {
            action: 'pagespeed_check_test_status',
            nonce: $('#pagespeed_test_nonce').val(),
            url: url
        }, function(response) {
            if (!response.success) {
                $status.html('<p class="error">Error: ' + response.data + '</p>');
                $button.prop('disabled', false);
                return;
            }

            if (response.data.status === 'running') {
                $status.html('<p>Test in progress...</p><div class="test-progress"></div>');
                setTimeout(() => checkMetaBoxTestStatus(url, $metabox), 5000);
            } else if (response.data.status === 'complete') {
                updateMetaBoxResults($metabox, response.data.results);
                $status.hide();
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $status.html('<p class="error">Failed to check test status</p>');
            $button.prop('disabled', false);
        });
    }
    function updateMetaBoxResults($metabox, results) {
        const $results = $metabox.find('.results-grid');
        
        // Update desktop results
        if (results.desktop) {
            updateDeviceScores($results.find('.device-results').first(), results.desktop);
        }
        
        // Update mobile results
        if (results.mobile) {
            updateDeviceScores($results.find('.device-results').last(), results.mobile);
        }
        
        $results.show();
    }

    function updateDeviceScores($container, data) {
        // Update Performance Score
        updateScore($container, 'Performance', data.performance_score);
        
        // Update Accessibility Score
        updateScore($container, 'Accessibility', data.accessibility_score);
        
        // Update Best Practices Score
        updateScore($container, 'Best Practices', data.best_practices_score);
        
        // Update SEO Score
        updateScore($container, 'SEO', data.seo_score);
    }

    function updateScore($container, type, score) {
        const $scoreItem = $container.find(`.score-item:contains('${type}')`);
        if ($scoreItem.length) {
            const $scoreValue = $scoreItem.find('.score');
            $scoreValue
                .removeClass('good warning poor')
                .addClass(getScoreClass(score))
                .text(score ? score + '%' : '--');
        }
    }

    function getScoreClass(score) {
        if (!score || score === 'N/A') return '';
        score = parseInt(score);
        if (score >= 90) return 'good';
        if (score >= 50) return 'average';
        return 'poor';
    }


    function showError($container, message) {
        $container.find('.scores-grid').hide();
        $container.append(
            $('<div/>', {
                class: 'notice notice-error',
                html: `<p>${message}</p>`
            })
        );
    }

    function clearError($container) {
        $container.find('.notice-error').remove();
        $container.find('.scores-grid').show();
    }

    function formatTiming(value) {
        if (value >= 1000) {
            return (value / 1000).toFixed(1) + 's';
        }
        return Math.round(value) + 'ms';
    }
});