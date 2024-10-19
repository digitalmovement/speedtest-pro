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

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!$this->uptimerobot_check_api_key()): ?>
        <div class="notice notice-error">
            <p><?php _e('UptimeRobot API key is not set. Please configure it in the settings.', 'wpspeedtestpro'); ?></p>
        </div>
    <?php else: ?>
        <?php if (!$this->ping_monitor_id || !$this->cron_monitor_id): ?>
            <div class="notice notice-warning">
                <p><?php _e('Uptime monitors are not set up. Click the button below to set them up.', 'wpspeedtestpro'); ?></p>
            </div>
            <button id="setup-monitors" class="button button-primary"><?php _e('Setup Monitors', 'wpspeedtestpro'); ?></button>
        <?php else: ?>
            <div id="uptime-monitors-data" class="loading">
                <div class="spinner is-active"></div>
                <p><?php _e('Loading monitor data...', 'wpspeedtestpro'); ?></p>
            </div>

            <div class="uptime-monitors-graph">
                <h3><?php _e('Ping and Cron Response Times', 'wpspeedtestpro'); ?></h3>
                <canvas id="combined-monitor-graph"></canvas>
            </div>

            <div class="uptime-monitors-logs">
                <div class="uptime-monitor-log">
                    <h3><?php _e('Ping Monitor Logs', 'wpspeedtestpro'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date/Time', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Type', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Duration', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Reason', 'wpspeedtestpro'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ping-monitor-logs"></tbody>
                    </table>
                </div>
                <div class="uptime-monitor-log">
                    <h3><?php _e('Cron Monitor Logs', 'wpspeedtestpro'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date/Time', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Type', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Duration', 'wpspeedtestpro'); ?></th>
                                <th><?php _e('Reason', 'wpspeedtestpro'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="cron-monitor-logs"></tbody>
                    </table>
                </div>
            </div>

            <div class="uptime-monitors-actions">
                <button id="refresh-monitors" class="button"><?php _e('Refresh Data', 'wpspeedtestpro'); ?></button>
                <button id="delete-monitors" class="button"><?php _e('Delete Monitors', 'wpspeedtestpro'); ?></button>
                <button id="recreate-monitors" class="button"><?php _e('Recreate Monitors', 'wpspeedtestpro'); ?></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>