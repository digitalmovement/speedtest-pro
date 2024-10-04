<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin/partials
 */
?>
<?php
if (!current_user_can('manage_options')) {
    return;
}

// Show error/update messages
settings_errors('wpspeedtestpro_messages');

?>
<div class="wrap">
    <h1><?php echo esc_html('WP SpeedTesting Pro Settings'); ?></h1>
    <form method="post" action="options.php" id="wpspeedtestpro_settings-form">
        <?php
        // Output security fields for the registered setting group "wpspeedtestpro_settings"
        settings_fields('wpspeedtestpro_settings');

        // Output setting sections and fields for the page slug 'wpspeedtestpro-settings'
        do_settings_sections('wpspeedtestpro-settings');
        
echo "after";
        settings_fields('wpspeedtestpro-settings');

        // Output setting sections and fields for the page slug 'wpspeedtestpro-settings'
        do_settings_sections('wpspeedtestpro_settings');
        
    

        // Submit button
        submit_button('Save Settings');
        ?>
    </form>
</div>
<!-- Confirmation Modal -->
<div id="data-collection-modal" style="display: none;">
    <div class="modal-content">
        <h2>Are you sure?</h2>
        <p>Are you sure you wish to not provide us with anonymous statistics? It really helps the development of this free plugin. We take privacy seriously!</p>
        <p><a href="https://wpspeedtestpro.com/privacy-policy" target="_blank">Learn more about our privacy policy</a></p>
        <div class="modal-buttons">
            <button id="modal-cancel" class="button">Cancel</button>
            <button id="modal-confirm" class="button button-primary">No stats for you</button>
        </div>
    </div>
</div>
