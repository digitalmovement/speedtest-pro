<?php
/**
 * Server Information display template
 */
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php 
        if (!get_option('wpspeedtestpro_serverinfo_info_dismissed', false)) :
    ?>
    <div id="serverinfo-info-banner" class="notice notice-info">
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        <h2 style="margin-top: 0;">Understanding Your Server Configuration</h2>
        
        <p>Server information provides crucial details about your hosting environment, helping you ensure optimal performance and troubleshoot potential issues.</p>
        
        <div style="margin: 20px 0;">
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Hosting Environment</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Operating System:</strong> Your server's platform and architecture
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Web Server:</strong> Server software handling HTTP requests (e.g., Apache, Nginx)
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>PHP Version:</strong> Installed PHP version and key configuration settings
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Memory Limits:</strong> Available memory for PHP processes
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Database Information</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>MySQL Version:</strong> Database server and client versions
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Database Settings:</strong> Charset, collation, and connection details
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Table Prefix:</strong> WordPress database table prefix for security
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">PHP Configuration</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>PHP Modules:</strong> Installed PHP extensions and their versions
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>PHP Settings:</strong> Important PHP configuration parameters
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Error Reporting:</strong> Current error logging configuration
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Site Environment</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Active Theme:</strong> Current theme and version
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Active Plugins:</strong> List of activated plugins
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Debug Status:</strong> WordPress debug configuration
                    </p>
                </div>
            </div>
        </div>

        <div style="margin-top: 15px;">
            <h4 style="margin: 0 0 5px 0; color: #1d2327;">Why This Information Matters</h4>
            <ul style="margin: 0; color: #555; list-style-type: disc; padding-left: 40px;">
                <li>Helps in troubleshooting website issues</li>
                <li>Ensures compatibility with plugins and themes</li>
                <li>Identifies potential security concerns</li>
                <li>Assists in optimization efforts</li>
                <li>Provides essential details for support requests</li>
            </ul>
        </div>

        <p style="margin-top: 15px; color: #555;">Keep this information handy when working with developers or support teams.</p>
    </div>
    <?php endif; ?>

    <?php $server_info = $this->get_server_info(); ?>
    
    <div id="wpspeedtestpro-tabs">
        <ul class="wpspeedtestpro-tab-links">
            <li class="active"><a href="#hosting-tab">Hosting Information</a></li>
            <li><a href="#database-tab">Database Information</a></li>
            <li><a href="#php-tab">PHP Information</a></li>
            <li><a href="#wordpress-tab">WordPress Information</a></li>
        </ul>

        <div class="wpspeedtestpro-tab-content">
        <!-- Hosting Information Tab -->
        <div id="hosting-tab" class="wpspeedtestpro-tab active">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($server_info['hosting'] as $key => $value): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Database Information Tab -->
        <div id="database-tab"  class="wpspeedtestpro-tab">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($server_info['database'] as $key => $value): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PHP Information Tab -->
        <div id="php-tab"  class="wpspeedtestpro-tab">
            <div class="phpinfo-wrapper">
                <?php
        
                $php_info = new Wpspeedtestpro_Php_Info();
                echo wp_kses_post($php_info->get_php_info());
                ?>
            </div>
        </div>

        <!-- WordPress Information Tab -->
        <div id="wordpress-tab"  class="wpspeedtestpro-tab">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Active Theme</th>
                        <td><?php echo esc_html($server_info['wordpress']['active_theme']); ?></td>
                    </tr>
                    <tr>
                        <th>Active Plugins</th>
                        <td>
                            <?php if (!empty($server_info['wordpress']['active_plugins'])): ?>
                                <ul>
                                <?php foreach ($server_info['wordpress']['active_plugins'] as $name => $author): ?>
                                    <li><?php echo esc_html($name); ?> <?php echo $author ? '(By ' . esc_html($author) . ')' : ''; ?></li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Inactive Plugins</th>
                        <td>
                            <?php if (!empty($server_info['wordpress']['inactive_plugins'])): ?>
                                <ul>
                                <?php foreach ($server_info['wordpress']['inactive_plugins'] as $name => $author): ?>
                                    <li><?php echo esc_html($name); ?> <?php echo $author ? '(By ' . esc_html($author) . ')' : ''; ?></li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Memory Limit</th>
                        <td><?php echo esc_html($server_info['wordpress']['memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <th>Max Memory Limit</th>
                        <td><?php echo esc_html($server_info['wordpress']['max_memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <th>Debug Mode</th>
                        <td><?php echo esc_html($server_info['wordpress']['debug_mode']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>