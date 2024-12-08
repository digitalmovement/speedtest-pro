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
            <p>SSL Testing requires an email address to send the results to. Please enter your email address in the settings.</p>
        <?php else: ?>
            <?php
            if (!get_option('wpspeedtestpro_ssl_info_dismissed',  false)):  
            ?>
            
            <div id="ssl-info-banner" class="notice notice-info ssl-info-banner">
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                
                <h2 style="margin-top: 0;">Understanding SSL Security Grades</h2>
                
                <p>SSL/TLS certificates are crucial for your website's security and user trust. They encrypt data transmission between your server and visitors, protecting sensitive information like passwords and personal data.</p>
                
                <div style="padding-left: 20px; margin-top: 15px;">
                    <h4 style="margin: 0 0 10px 0;">SSL Labs Grades Explained:</h4>
                    <div class="ssl-grades">
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #2271b1;">A+ (Superior)</strong>
                            <p style="margin: 0; color: #555;">Perfect SSL implementation with exceptional security features including HSTS, perfect forward secrecy, and strong cipher preferences.</p>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #2271b1;">A (Excellent)</strong>
                            <p style="margin: 0; color: #555;">Strong security with good cipher strength and key exchange. Meets modern security standards but might lack some optional enhancements.</p>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #FFA500;">B (Good)</strong>
                            <p style="margin: 0; color: #555;">Secure but with minor weaknesses. May use older protocols or cipher suites that, while secure, aren't considered best practice.</p>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #FF4500;">C (Fair)</strong>
                            <p style="margin: 0; color: #555;">Notable configuration issues or weaker security options enabled. Should be improved for better security.</p>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #FF0000;">F (Poor)</strong>
                            <p style="margin: 0; color: #555;">Major vulnerabilities or configuration problems. Immediate attention required.</p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 15px;">
                    <h4 style="margin: 0 0 5px 0;">Key Factors Affecting Your Grade:</h4>
                    <ul style="margin: 0; color: #555; list-style-type: disc; padding-left: 40px;">
                        <li>Protocol Support (TLS versions)</li>
                        <li>Key Exchange Strength</li>
                        <li>Cipher Strength</li>
                        <li>Forward Secrecy Support</li>
                        <li>HSTS (HTTP Strict Transport Security)</li>
                        <li>Certificate Chain Trust</li>
                    </ul>
                </div>

                <p style="margin-top: 15px; color: #555;">Regular SSL testing helps ensure your website maintains strong security standards and protects your visitors' data effectively.</p>
            </div>
            <?php endif; ?>
            <div id="ssl-status-message" class="notice notice-info" style="display: none;"></div>
            <button id="start-ssl-test" class="button button-primary">Start SSL Test</button>
        <?php endif; ?>
        
        <div id="ssl-test-results">
            <?php
            if ($cached_result) {
                echo $this->format_ssl_test_results($cached_result);
            } elseif (!empty($user_email)) {
                echo '<div class="notice notice-info" style="display: none;"><p>No SSL test results available. Click "Start SSL Test" to begin.</p></div';
            }
            ?>
        </div>
    </div>
</div>
