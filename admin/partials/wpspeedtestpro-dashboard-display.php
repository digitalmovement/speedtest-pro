<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Provide a admin area view for the plugin's dashboard
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

    <!-- Overview Section -->
    <div class="dashboard-grid">
        <!-- Server Performance Card -->
        <div class="dashboard-card" id="server-performance-card">
            <h2><i class="fas fa-server"></i> Server Performance</h2>
            <div class="card-content">
                <div class="metric">
                    <span class="label">Last Executed:</span>
                    <span class="value" id="server-performance-last-executed">Loading...</span>
                </div>
                <div class="performance-metrics">
                    <div class="metric">
                        <span class="label">Math Operations:</span>
                        <span class="value" id="math-performance">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">String Operations:</span>
                        <span class="value" id="string-performance">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">MySQL Performance:</span>
                        <span class="value" id="mysql-performance">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">WordPress Performance:</span>
                        <span class="value" id="wp-performance">Loading...</span>
                    </div>
                </div>
                <div class="mini-chart">
                    <canvas id="performance-trend-chart"></canvas>
                </div>
                <div class="action-buttons">
                <button id="test-performance" class="button button-primary">
                    <i class="fas fa-server"></i> Test Performance
                </button>
            </div>
            </div>
        </div>

        <!-- Latency Card -->
        <div class="dashboard-card" id="latency-card">
            <h2><i class="fas fa-tachometer-alt"></i> Latency Testing</h2>
            <div class="card-content">
                <div class="metric">
                    <span class="label">Selected Region:</span>
                    <span class="value" id="selected-region">Loading...</span>
                </div>
                <div class="latency-metrics">
                    <div class="metric">
                        <span class="label">Current:</span>
                        <span class="value" id="current-latency">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Fastest:</span>
                        <span class="value" id="fastest-latency">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Slowest:</span>
                        <span class="value" id="slowest-latency">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Last Updated:</span>
                        <span class="value" id="latency-last-updated">Loading...</span>
                    </div>
                </div>
                <div class="mini-chart">
                    <canvas id="latency-trend-chart"></canvas>
                </div>
            </div>
            <div class="action-buttons">
                <button id="test-latency" class="button button-primary">
                <i class="fas fa-tachometer-alt"></i> Test Latency
                </button>
            </div>
        </div>

        <!-- SSL Card -->
        <div class="dashboard-card" id="ssl-card">
            <h2><i class="fas fa-shield-alt"></i> SSL Security</h2>
            <div class="card-content">
                <div class="metric">
                    <span class="label">SSL Grade:</span>
                    <span class="value" id="ssl-grade">Loading...</span>
                </div>
                <div class="metric">
                    <span class="label">Last Checked:</span>
                    <span class="value" id="ssl-last-checked">Loading...</span>
                </div>
                <div class="ssl-metrics">
                    <div class="metric">
                        <span class="label">Certificate Expiry:</span>
                        <span class="value" id="ssl-expiry">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Protocol Version:</span>
                        <span class="value" id="ssl-protocol">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="action-buttons">
                    <button id="test-ssl" class="button button-primary">
                       <i class="fas fa-shield-alt"></i> Test SSL
                    </button>
            </div>
        </div>

        <!-- Uptime Card -->
        <div class="dashboard-card" id="uptime-card">
            <h2><i class="fas fa-clock"></i> Uptime Monitoring</h2>
            <div class="card-content">
                <div class="uptime-metrics">
                    <div class="metric">
                        <span class="label">Server Uptime:</span>
                        <span class="value" id="server-uptime">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Cron Uptime:</span>
                        <span class="value" id="cron-uptime">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Ping Time:</span>
                        <span class="value" id="avg-ping-time">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Cron Time:</span>
                        <span class="value" id="avg-cron-time">Loading...</span>
                    </div>
                </div>
                <div class="mini-chart">
                    <canvas id="uptime-trend-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Page Speed Card -->
        <div class="dashboard-card" id="pagespeed-card">
    <h2><i class="fas fa-rocket"></i> Page Speed</h2>
    <div class="card-content">
        <div class="pagespeed-grid">
            <!-- Desktop Results -->
            <div class="device-section">
                <h3>Desktop</h3>
                <div class="scores-grid">
                    <div class="metric">
                        <span class="label">Performance:</span>
                        <span class="value" id="desktop-performance">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Accessibility:</span>
                        <span class="value" id="desktop-accessibility">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Best Practices:</span>
                        <span class="value" id="desktop-best-practices">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">SEO:</span>
                        <span class="value" id="desktop-seo">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Mobile Results -->
            <div class="device-section">
                <h3>Mobile</h3>
                <div class="scores-grid">
                    <div class="metric">
                        <span class="label">Performance:</span>
                        <span class="value" id="mobile-performance">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Accessibility:</span>
                        <span class="value" id="mobile-accessibility">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Best Practices:</span>
                        <span class="value" id="mobile-best-practices">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">SEO:</span>
                        <span class="value" id="mobile-seo">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Common Information -->
        <div class="common-info">
            <div class="metric">
                <span class="label">URL Tested:</span>
                <span class="value" id="tested-url">Loading...</span>
            </div>
            <div class="metric">
                <span class="label">Last Tested:</span>
                <span class="value" id="pagespeed-last-tested">Loading...</span>
            </div>
        </div>
        <div class="action-buttons">
            <button id="test-pagespeed" class="button button-primary">
                    <i class="fas fa-rocket"></i> Test Page Speed
            </button>
        </div>
    </div>
</div>
</div>

    <!-- Advertising Section -->
    <div class="advertising-section">
        <div class="advert-cards">
            <div class="advert-card" id="advert-1">
                <div class="advert-image">
                    <a target="_blank" href="#" class="advert-link">
                    <div class="advertisement-container" aria-label="Advertisement"></div>
                                    </a>
                </div>
                <div class="advert-content">
                    <h3 class="advert-title"></h3>
                    <p class="advert-description"></p>
                    <a target="_blank" href="#" class="advert-button button button-primary"></a>
                </div>
            </div>
            <div class="advert-card" id="advert-2">
                <div class="advert-image">
                    <a target="_blank" href="#" class="advert-link">
                        <div class="advertisement-container" aria-label="Advertisement"></div>
                    </a>
                </div>
                <div class="advert-content">
                    <h3 class="advert-title"></h3>
                    <p class="advert-description"></p>
                    <a target="_blank" href="#" class="advert-button button button-primary"></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Dashboard Events Section -->
    <div class="dashboard-events">
        <div class="event-card documentation">
            <i class="fas fa-book"></i>
            <h3>Documentation</h3>
            <p>Learn how to configure and debug issues with our detailed documentation</p>
            <a href="https://wpspeedtestpro.com/documentation" target="_blank" class="button button-secondary">
                View Documentation
            </a>
        </div>
        <div class="event-card bugreport">
            <i class="fas fa-bug"></i>
            <h3>Bug Report</h3>
            <p>Found an issue? Report it directly to us so we can make things better</p>
            <a onclick="wpSpeedTestProBugReport.openBugModal();" target="_blank" class="button button-secondary">
                Bug Report
            </a>
        </div>
        <div class="event-card support">
            <i class="fas fa-life-ring"></i>
            <h3>Help & Support</h3>
            <p>Open a support ticket and get assistance from our support engineers</p>
            <a href="https://wpspeedtestpro.com/support" target="_blank" class="button button-secondary">
                Get Support
            </a>
            <a href="" target="_blank" class="button button-secondary send-diagnostics-link">
                Send Diagnostics <span class="spinner"></span>
            </a>
            <br /> <br /><span id="site-key" style="display:none"></span>
        </div>
    </div>
</div>
