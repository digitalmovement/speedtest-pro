jQuery(document).ready(function($) {
    var testInProgress = false;
    var statusCheckInterval;

    $('.ssl-info-banner .notice-dismiss').on('click', function(e) {
        e.preventDefault();
        
        const $banner = $(this).closest('.ssl-info-banner');
        
        $.ajax({
            url: wpspeedtestpro_ssl.ajax_url,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_dismiss_ssl_info',
                nonce: wpspeedtestpro_ssl.nonce
            },
            success: function(response) {
                if (response.success) {
                    $banner.slideUp(200, function() {
                        $banner.remove();
                    });
                }
            },
            error: function() {
                console.error('Failed to dismiss SSL info banner');
            }
        });
    });

    $('#start-ssl-test').on('click', function(e) {
        e.preventDefault();
        if (testInProgress) {
            return;
        }
        testInProgress = true;
        $('#ssl-status-message').html('Starting SSL test... Testing can take up to 3 minutes to complete<div class="test-progress"></div>');
        toggleNotice($('#ssl-status-message'), 'info');
        $('#start-ssl-test').prop('disabled', true);

        $.ajax({
            url: wpspeedtestpro_ssl.ajax_url,
            type: 'POST',
            data: {
                action: 'start_ssl_test',
                nonce: wpspeedtestpro_ssl.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'in_progress') {
                        $('#ssl-status-message').html("Testing is still in progress..." + '<div class="test-progress"></div>');
                        toggleNotice($('#ssl-status-message'), 'info');
                        startStatusCheck();
                    } else if (response.data.status === 'completed') {
                        displayResults(response.data.data);
                        testInProgress = false;
                        $('#start-ssl-test').prop('disabled', false);
                        $('#ssl-status-message').html('SSL testing completed');
                        toggleNotice($('#ssl-status-message'), 'success');
                    }
                } else {
                    $('#ssl-status-message').html('Error: ' + response.data );
                    toggleNotice($('#ssl-status-message'), 'error');
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                $('#ssl-status-message').html('An error occurred while starting the SSL test.');
                toggleNotice($('#ssl-status-message'), 'error');
                testInProgress = false;
                $('#start-ssl-test').prop('disabled', false);
            }
        });
    });

    function startStatusCheck() {
        statusCheckInterval = setInterval(checkStatus, 5000); // Check every 5 seconds
    }

    function checkStatus() {
        $.ajax({
            url: wpspeedtestpro_ssl.ajax_url,
            type: 'POST',
            data: {
                action: 'check_ssl_test_status',
                nonce: wpspeedtestpro_ssl.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'completed') {
                        clearInterval(statusCheckInterval);
                        displayResults(response.data.data);
                        testInProgress = false;
                        $('#start-ssl-test').prop('disabled', false);
                    } else if (response.data.status === 'in_progress') {
                        $('#ssl-status-message').html( "Testing is still in progress" + '<div class="test-progress"></div>' );
                        toggleNotice($('#ssl-status-message'), 'info');
                    }
                } else {
                    clearInterval(statusCheckInterval);
                    $('#ssl-status-message').html('Error: ' + response.data );
                    toggleNotice($('#ssl-status-message'), 'error');
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                clearInterval(statusCheckInterval);
                $('#ssl-status-message').html('An error occurred while checking the SSL test status');
                toggleNotice($('#ssl-status-message'), 'error');
                testInProgress = false;
                $('#start-ssl-test').prop('disabled', false);
            }
        });
    }


    function initializeTabs() {
        $('.ssl-tab-links a').on('click', function(e) {
            e.preventDefault();
            var currentAttrValue = $(this).attr('href');

            $('.ssl-tab-content ' + currentAttrValue).show().siblings().hide();
            $(this).parent('li').addClass('active').siblings().removeClass('active');
        });
    }


    function displayResults(results) {
        $('#ssl-test-results').html(results);
        initializeTabs();
    }

    function toggleNotice($element, type = 'info') {
        const baseClass = 'notice-';
        $element.removeClass(baseClass + 'info ' + baseClass + 'error' + baseClass + 'success')
                .addClass(baseClass + type);
        $element.show();
    }



          // Initialize tabs if there are cached results
    if ($('.ssl-tabs').length > 0) {
       initializeTabs();
   }

   checkStatus();
   startStatusCheck();
});