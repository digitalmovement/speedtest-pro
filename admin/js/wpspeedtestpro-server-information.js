jQuery(document).ready(function($) {

    $('#serverinfo-info-banner .notice-dismiss').on('click', function(e) {
        e.preventDefault();
        
        const $banner = $(this).closest('#serverinfo-info-banner');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpspeedtestpro_dismiss_serverinfo_info',
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $banner.slideUp(200, function() {
                        $banner.remove();
                    });
                }
            },
            error: function() {
                console.error('Failed to dismiss server info banner');
            }
        });
    });
    $('#wpspeedtestpro-server-tabs').tabs();

    // Make tables responsive
    $('.wp-list-table').wrap('<div class="table-responsive"></div>');

    // Style PHPInfo output
    $('.phpinfo-wrapper table').addClass('wp-list-table widefat fixed striped');
    $('.phpinfo-wrapper td.e').addClass('row-title');
});