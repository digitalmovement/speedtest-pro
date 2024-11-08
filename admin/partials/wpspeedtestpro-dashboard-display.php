<?php
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
        </div>

        <!-- SSL Card -->
        <div class="dashboard-card" id="ssl-card">
            <h2><i class="fas fa-shield-alt"></i> SSL Security</h2>
            <div class="card-content">
                <div class="ssl-grade">
                    <span class="grade" id="ssl-grade">-</span>
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
                <div class="metric">
                    <span class="label">Performance Score:</span>
                    <span class="value" id="performance-score">Loading...</span>
                </div>
                <div class="pagespeed-metrics">
                    <div class="metric">
                        <span class="label">First Contentful Paint:</span>
                        <span class="value" id="fcp-value">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Largest Contentful Paint:</span>
                        <span class="value" id="lcp-value">Loading...</span>
                    </div>
                    <div class="metric">
                        <span class="label">Last Tested:</span>
                        <span class="value" id="pagespeed-last-tested">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="quick-actions-section">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <button id="test-latency" class="button button-primary">
                <i class="fas fa-tachometer-alt"></i> Test Latency
            </button>
            <button id="test-ssl" class="button button-primary">
                <i class="fas fa-shield-alt"></i> Test SSL
            </button>
            <button id="test-performance" class="button button-primary">
                <i class="fas fa-server"></i> Test Performance
            </button>
            <button id="test-pagespeed" class="button button-primary">
                <i class="fas fa-rocket"></i> Test Page Speed
            </button>
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
        <div class="event-card support">
            <i class="fas fa-life-ring"></i>
            <h3>Help & Support</h3>
            <p>Open a support ticket and get assistance from our support engineers</p>
            <a href="https://wpspeedtestpro.com/support" target="_blank" class="button button-secondary">
                Get Support
            </a>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.dashboard-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.dashboard-card h2 {
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2em;
    color: #1d2327;
}

.dashboard-card h2 i {
    color: #2271b1;
}

.card-content {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.metric {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}

.label {
    color: #646970;
    font-weight: 500;
}

.value {
    font-weight: 600;
    color: #1d2327;
}

.mini-chart {
    height: 100px;
    margin-top: 10px;
}

.ssl-grade {
    text-align: center;
    font-size: 3em;
    font-weight: bold;
    margin: 10px 0;
}

.quick-actions-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border: 1px solid #e2e4e7;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.dashboard-events {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.event-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e2e4e7;
}

.event-card i {
    font-size: 2em;
    color: #2271b1;
    margin-bottom: 10px;
}

.event-card h3 {
    margin: 10px 0;
    color: #1d2327;
}

.event-card p {
    color: #646970;
    margin-bottom: 15px;
}

/* Color indicators */
.good { color: #00a32a; }
.warning { color: #dba617; }
.poor { color: #d63638; }

/* Loading animation */
.loading {
    opacity: 0.6;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 0.3; }
    100% { opacity: 0.6; }
}
</style>