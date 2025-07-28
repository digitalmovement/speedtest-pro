jQuery(document).ready(function($) {
    'use strict';

    // Handle opt-in button click
    $('#wpspeedtestpro-opt-in-data').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: wpspeedtestpro_ajax.opt_in_action,
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wpspeedtestpro-data-collection-notice').fadeOut();
                }
            },
            error: function() {
                // Handle error silently or show a message
                console.log('AJAX request failed');
            }
        });
    });

    // Handle dismiss button click
    $('#wpspeedtestpro-dismiss-notice').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: wpspeedtestpro_ajax.dismiss_action,
                nonce: wpspeedtestpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wpspeedtestpro-data-collection-notice').fadeOut();
                }
            },
            error: function() {
                // Handle error silently or show a message
                console.log('AJAX request failed');
            }
        });
    });

    // Handle default dismiss button (X)
    $('#wpspeedtestpro-data-collection-notice .notice-dismiss').on('click', function() {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: wpspeedtestpro_ajax.dismiss_action,
                nonce: wpspeedtestpro_ajax.nonce
            }
        });
    });
});