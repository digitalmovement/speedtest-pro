jQuery(document).ready(function($) {
    var testInProgress = false;
    var statusCheckInterval;

    
    $('#auth-action').on('change', function() {
        var action = $(this).val();
        if (action === 'register') {
            $('#first-name, #last-name, #organization').show();
        } else {
            $('#first-name, #last-name, #organization').hide();
        }
    });

    $('#auth-submit').on('click', function(e) {
        e.preventDefault();
        var action = $('#auth-action').val();
        var data = {
            action: action === 'login' ? 'login_user' : 'register_user',
            nonce: wpspeedtestpro_ssl.nonce,
            email: $('#email').val()
        };

        if (action === 'register') {
            data.first_name = $('#first-name').val();
            data.last_name = $('#last-name').val();
            data.organization = $('#organization').val();
        }

        $.ajax({
            url: wpspeedtestpro_ssl.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $('#auth-message').text(response.data).show();
                }
            },
            error: function() {
                $('#auth-message').text('An error occurred. Please try again.').show();
            }
        });
    });

    
    $('#start-ssl-test').on('click', function(e) {
        e.preventDefault();
        if (testInProgress) {
            return;
        }
        testInProgress = true;
        $('#ssl-test-results').html('&lt;p&gt;Starting SSL test...&lt;/p&gt;');
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
                        $('#ssl-test-results').html('&lt;p&gt;' + response.data.message + '&lt;/p&gt;');
                        startStatusCheck();
                    } else if (response.data.status === 'completed') {
                        displayResults(response.data.data);
                        testInProgress = false;
                        $('#start-ssl-test').prop('disabled', false);
                    }
                } else {
                    $('#ssl-test-results').html('&lt;p&gt;Error: ' + response.data + '&lt;/p&gt;');
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                $('#ssl-test-results').html('&lt;p&gt;An error occurred while starting the SSL test.&lt;/p&gt;');
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
                        $('#ssl-test-results').html('&lt;p&gt;' + response.data.message + '&lt;/p&gt;');
                    }
                } else {
                    clearInterval(statusCheckInterval);
                    $('#ssl-test-results').html('&lt;p&gt;Error: ' + response.data + '&lt;/p&gt;');
                    testInProgress = false;
                    $('#start-ssl-test').prop('disabled', false);
                }
            },
            error: function() {
                clearInterval(statusCheckInterval);
                $('#ssl-test-results').html('&lt;p&gt;An error occurred while checking the SSL test status.&lt;/p&gt;');
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
});