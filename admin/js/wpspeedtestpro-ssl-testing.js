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
        $('#ssl-test-results').html('Starting SSL test... Testing can take up to 3 minutes to complete<div class="test-progress"></div>');
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
                        $('#ssl-test-results').html("Testing is still in progress" + '<div class="test-progress"></div>');
                        startStatusCheck();
                    } else if (response.data.status === 'completed') {
                        displayResults(response.data.data);
                        testInProgress = false;
                        $('#start-ssl-test').prop('disabled', false);
                    }
                } else {
                    $('#ssl-test-results').html('Error: ' + response.data );
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                $('#ssl-test-results').html('An error occurred while starting the SSL test.');
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
                        $('#ssl-test-results').html( "Testing is still in progress" + '<div class="test-progress"></div>' );
                    }
                } else {
                    clearInterval(statusCheckInterval);
                    $('#ssl-test-results').html('Error: ' + response.data );
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                clearInterval(statusCheckInterval);
                $('#ssl-test-results').html('An error occurred while checking the SSL test status');
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

          // Initialize tabs if there are cached results
    if ($('.ssl-tabs').length > 0) {
       initializeTabs();
   }

   checkStatus();
   startStatusCheck();
});