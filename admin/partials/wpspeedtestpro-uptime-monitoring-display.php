<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
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

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <?php 
    if (!get_option('wpspeedtestpro_uptime_info_dismissed', false)): 
        ?>
        <div id="uptime-info-banner" class="notice notice-info uptime-info-banner">
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            
            <h2 style="margin-top: 0;">Understanding Your Website's Uptime Monitoring</h2>
            
            <p>We've implemented two essential monitors to ensure your website remains healthy and functional:</p>
            
            <div style="padding-left: 20px;">
                <div style="margin-bottom: 15px;">
                    <h4 style="margin: 0 0 5px 0;">1. Server Response Monitor (Ping)</h4>
                    <p style="margin: 0; color: #555;">
                        This monitor regularly checks if your website is accessible from the internet. It confirms that your server 
                        is online and responding to requests, which is crucial for ensuring your visitors can access your site.
                    </p>
                </div>
                
                <div>
                    <h4 style="margin: 0 0 5px 0;">2. WordPress Cron Monitor</h4>
                    <p style="margin: 0; color: #555;">
                        This monitor verifies that WordPress's scheduled task system (WP-Cron) is functioning correctly. It's essential 
                        for features like scheduled posts, automatic updates, backups, and other time-based tasks that keep your 
                        website running smoothly.
                    </p>
                </div>
            </div>
    
            <p style="margin-top: 15px; color: #555;">
                Together, these monitors provide comprehensive oversight of your website's health, alerting you to potential 
                issues before they impact your visitors or content management workflow.
            </p>
        </div>
        <?php endif; ?>


    <?php if (!$this->uptimerobot_check_api_key()): ?>
        <div class="notice notice-error">
            <p><?php esc_attr_e('UptimeRobot API key is not set. Please configure it in the settings.', 'speedtest-pro'); ?></p>
        </div>
    <?php else: ?>
        <?php if (!$this->ping_monitor_id || !$this->cron_monitor_id): ?>
            <div class="notice notice-warning">
                <p><?php esc_attr_e('Uptime monitors are not set up. Click the button below to set them up.', 'speedtest-pro'); ?></p>
            </div>
            <button id="setup-monitors" class="button button-primary"><?php esc_attr_e('Setup Monitors', 'speedtest-pro'); ?></button>
        <?php else: ?>
            <div id="uptime-monitors-data">
                <div class="spinner is-active" style="float: none;"></div>
                <p><?php esc_attr_e('Loading monitor data...', 'speedtest-pro'); ?></p>
            </div>

            <div class="uptime-monitors-graph">
                <h3><?php esc_attr_e('Ping and Cron Response Times', 'speedtest-pro'); ?></h3>
                <canvas id="combined-monitor-graph"></canvas>
            </div>

            <div class="uptime-monitors-logs">
                <div class="uptime-monitor-log">
                    <h3><?php esc_attr_e('Ping Monitor Logs', 'speedtest-pro'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_attr_e('Date/Time', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Type', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Duration', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Reason', 'speedtest-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ping-monitor-logs"></tbody>
                    </table>
                </div>
                <div class="uptime-monitor-log">
                    <h3><?php esc_attr_e('Cron Monitor Logs', 'speedtest-pro'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_attr_e('Date/Time', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Type', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Duration', 'speedtest-pro'); ?></th>
                                <th><?php esc_attr_e('Reason', 'speedtest-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="cron-monitor-logs"></tbody>
                    </table>
                </div>
            </div>

            <div class="uptime-monitors-actions">
                <button id="refresh-monitors" class="button"><?php esc_attr_e('Refresh Data', 'speedtest-pro'); ?></button>
                <button id="delete-monitors" class="button"><?php esc_attr_e('Delete Monitors', 'speedtest-pro'); ?></button>
                <button id="recreate-monitors" class="button"><?php esc_attr_e('Recreate Monitors', 'speedtest-pro'); ?></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>