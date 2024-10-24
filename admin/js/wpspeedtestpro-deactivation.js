jQuery(document).ready(function($) {
    // Only run on plugins page
    if (pagenow !== 'plugins') {
        return;
    }

    // Create and append the modal HTML
    const modalHtml = `
        <div id="wpspeedtestpro-deactivate-modal" style="display:none;" class="wpspeedtestpro-modal">
            <div class="wpspeedtestpro-modal-content">
                <h2>Plugin Deactivation</h2>
                <p>Would you like to delete all plugin data? This includes:</p>
                <ul>
                    <li>All database tables</li>
                    <li>All stored test results</li>
                    <li>All plugin settings</li>
                </ul>
                <p><strong>Warning:</strong> This action cannot be undone!</p>
                <div class="wpspeedtestpro-modal-footer">
                    <label>
                        <input type="checkbox" id="wpspeedtestpro-delete-data" name="delete_data">
                        Yes, delete all plugin data
                    </label>
                    <div class="wpspeedtestpro-modal-buttons">
                        <button type="button" class="button button-secondary wpspeedtestpro-modal-cancel">Cancel</button>
                        <button type="button" class="button button-primary wpspeedtestpro-modal-deactivate">Deactivate</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);

    // Get the deactivation link
    const deactivateLink = $('tr[data-plugin="wpspeedtestpro/wpspeedtestpro.php"] .deactivate a');
    const originalLink = deactivateLink.attr('href');

    // Override the deactivation link click
    deactivateLink.on('click', function(e) {
        e.preventDefault();
        $('#wpspeedtestpro-deactivate-modal').show();
    });

    // Handle cancel button
    $('.wpspeedtestpro-modal-cancel').on('click', function() {
        $('#wpspeedtestpro-deactivate-modal').hide();
    });

    // Handle deactivate button
    $('.wpspeedtestpro-modal-deactivate').on('click', function() {
        const deleteData = $('#wpspeedtestpro-delete-data').is(':checked');
        
        if (deleteData) {
            // Send AJAX request to delete data
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_pre_deactivation',
                    nonce: wpspeedtestpro_deactivation.nonce,
                    delete_data: true
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = originalLink;
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        } else {
            // Just deactivate without deleting data
            window.location.href = originalLink;
        }
    });

    // Close modal if clicked outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#wpspeedtestpro-deactivate-modal')) {
            $('#wpspeedtestpro-deactivate-modal').hide();
        }
    });
});
