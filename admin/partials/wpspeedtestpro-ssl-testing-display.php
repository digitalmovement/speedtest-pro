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

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p>Test your website's SSL configuration and security.</p>
    
    <div class="wpspeedtestpro-ssl-testing">
        <button id="start-ssl-test" class="button button-primary">Start SSL Test</button>
        
        <div id="ssl-test-results">
            <?php
            if ($cached_result) {
                echo $this->format_ssl_test_results($cached_result);
            } else {
                echo '<p>No SSL test results available. Click "Start SSL Test" to begin.</p>';
            }
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {

    });
</script>