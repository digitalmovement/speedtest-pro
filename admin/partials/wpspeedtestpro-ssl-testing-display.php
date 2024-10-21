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
        <?php if (empty($user_email)): ?>
            <div id="user-auth-form">
                <select id="auth-action">
                    <option value="login">Login</option>
                    <option value="register">Register</option>
                </select>
                <input type="text" id="first-name" placeholder="First Name" style="display:none;">
                <input type="text" id="last-name" placeholder="Last Name" style="display:none;">
                <input type="email" id="email" placeholder="Email">
                <input type="text" id="organization" placeholder="Organization" style="display:none;">
                <button id="auth-submit" class="button button-secondary">Submit</button>
            </div>
            <p id="auth-message" style="color: red;">Please login or register to use the SSL testing feature.</p>
        <?php else: ?>
            <p>SSL Testing will use, <?php echo esc_html($user_email); ?>!</p>
            <button id="start-ssl-test" class="button button-primary">Start SSL Test</button>
        <?php endif; ?>
        
        <div id="ssl-test-results">
            <?php
            if ($cached_result) {
                echo $this->format_ssl_test_results($cached_result);
            } elseif (!empty($user_details)) {
                echo '<p>No SSL test results available. Click "Start SSL Test" to begin.</p>';
            }
            ?>
        </div>
    </div>
</div>
