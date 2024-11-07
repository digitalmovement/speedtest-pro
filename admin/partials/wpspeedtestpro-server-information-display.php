<?php
/**
 * Server Information display template
 */
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php $server_info = $this->get_server_info(); ?>
    
    <div id="wpspeedtestpro-server-tabs">
        <ul>
            <li><a href="#hosting-tab">Hosting Information</a></li>
            <li><a href="#database-tab">Database Information</a></li>
            <li><a href="#php-tab">PHP Information</a></li>
            <li><a href="#wordpress-tab">WordPress Information</a></li>
        </ul>

        <!-- Hosting Information Tab -->
        <div id="hosting-tab">
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
        <div id="database-tab">
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
        <div id="php-tab">
            <div class="phpinfo-wrapper">
                <?php
        
                $php_info = new Wpspeedtestpro_Php_Info();
                echo $php_info->get_php_info();
                
                // Convert phpinfo HTML to be WordPress-friendly
                //$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
                //$phpinfo = str_replace('<table>', '<table class="wp-list-table widefat fixed striped">', $phpinfo);
                //echo $phpinfo;
                ?>
            </div>
        </div>

        <!-- WordPress Information Tab -->
        <div id="wordpress-tab">
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