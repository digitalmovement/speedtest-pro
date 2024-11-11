<?php
// Ensure this file is being included by a parent file
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <div class="server-performance-content">
        <h2>Server Performance Tests</h2>
        <p>This page contains various server performance tests and their results. The tests measure different aspects of your server's performance.</p>
    </div>
    
    <?php    if (!get_option('wpspeedtestpro_performance_info_dismissed', false)):
     
    ?>
    <div id="performance-info-banner" class="notice notice-info">
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
        <h2 style="margin-top: 0;">Understanding Server Performance Testing</h2>
        
        <p style="margin-bottom: 15px;">Server performance testing helps identify bottlenecks and ensures optimal performance of your WordPress site. Our comprehensive testing suite measures several critical aspects:</p>
        
        <div style="margin: 20px 0;">
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">System Performance Metrics</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Math Operations</strong>: Tests CPU performance with mathematical calculations
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>String Operations</strong>: Measures text processing and manipulation speed
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Loop Execution</strong>: Tests programming loop performance
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Conditional Logic</strong>: Evaluates decision-making speed
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Database Performance</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>MySQL Operations</strong>: Tests database query execution speed
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>WordPress Queries</strong>: Measures WordPress-specific database operations
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Network Performance</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Upload Speeds</strong>: Tests file upload performance (10KB to 10MB)
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Download Speeds</strong>: Measures file download capabilities (10KB to 10MB)
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Ping Latency</strong>: Checks server response time
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Test Types Available</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Single Test</strong>: One-time comprehensive performance check
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Continuous Testing</strong>: 24-hour monitoring with tests every 15 minutes
                        <br>
                        <span style="color: #d63638;">⚠️ Note: Use continuous testing carefully as it can impact server performance</span>
                    </p>
                </div>
            </div>
        </div>

        <p style="margin-top: 15px; color: #555;">Results are compared against industry averages to help you understand your server's performance in context.</p>
    </div>

    <?php endif; ?>
    <div id="error-message" class="notice notice-error" style="display: none;"></div>

    
    <div class="test-controls">
        <button id="start-stop-test" class="button button-primary" data-status="<?php echo esc_attr(get_option('wpspeedtestpro_performance_test_status', 'stopped')); ?>">
            <?php echo get_option('wpspeedtestpro_performance_test_status', 'stopped') === 'running' ? 'Stop Test' : 'Start Test'; ?>
        </button>
        <button id="continuous-test" class="button button-secondary">Continuous Testing</button>
        <div id="test-progress" class="notice notice-info" style="display: none;">
            <p>Test in progress... You can leave this page and come back later to see the results.</p>
        </div>
        <div id="continuous-test-info" class="notice notice-info" style="display: none;">
            <p>Continuous test in progress. Next test scheduled for: <span id="next-test-time"></span></p>
        </div>
        <div id="continuous-test-info" class="notice notice-info" style="display: none;">
            <p>Continuous test in progress. Time remaining: <span id="time-remaining"></span></p>
        </div>
        
    </div>

    <!-- Add modal for continuous testing warning -->
    <div id="continuous-test-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Warning</h2>
            <p>This is a continuous server test that can affect the performance of the server for end users. This should not be used on production sites. This test will run for 24 hours every 15 minutes.</p>
            <div class="modal-buttons">
                <button id="continue-test" class="button button-primary">Continue with test</button>
                <button id="cancel-test" class="button button-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <div id="server-performance-tabs">
        <ul>
            <li><a href="#tab-latest-results">Latest Results</a></li>
            <li><a href="#tab-math">Math</a></li>
            <li><a href="#tab-string">String</a></li>
            <li><a href="#tab-loops">Loops</a></li>
            <li><a href="#tab-conditionals">Conditionals</a></li>
            <li><a href="#tab-mysql">MySQL</a></li>
            <li><a href="#tab-wordpress">WordPress Performance</a></li>
            <li><a href="#tab-speed-test">Network Performance</a></li>
        </ul>
        
    <div id="tab-latest-results">
            <h2>Latest Benchmark Results</h2>
            
            <!-- System Performance Results -->
            <div class="chart-container">
                <canvas id="latest-performance-chart"></canvas>
            </div>
            
            <!-- Network Performance Results -->
            <div class="chart-container">
                <canvas id="latest-network-chart"></canvas>
            </div>
            
            <!-- Network Information -->
            <div class="speed-test-info">
                <p><strong>Location:</strong> <span id="speed-test-location"></span></p>
                <p><strong>IP Address:</strong> <span id="speed-test-ip"></span></p>
                <p><strong>Ping Latency:</strong> <span id="speed-test-ping"></span> ms</p>
            </div>
    </div>
        
        <div id="tab-math">
            <h2>Math Performance</h2>
            <div class="chart-container">
                <canvas id="math-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-string">
            <h2>String Performance</h2>
            <div class="chart-container">
                <canvas id="string-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-loops">
            <h2>Loops Performance</h2>
            <div class="chart-container">
                <canvas id="loops-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-conditionals">
            <h2>Conditionals Performance</h2>
            <div class="chart-container">
                <canvas id="conditionals-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-mysql">
            <h2>MySQL Performance</h2>
            <div class="chart-container">
                <canvas id="mysql-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-wordpress">
            <h2>WordPress Performance</h2>
            <div class="chart-container">
                <canvas id="wordpress-performance-chart"></canvas>
            </div>
        </div>

        <div id="tab-speed-test">
            <h2>Speed Test Performance</h2>
            <div class="chart-container">
                <canvas id="speed-test-chart"></canvas>
            </div>
        </div>

    </div>
    <div class="performance-note">
        <p><strong>Note:</strong> Historical charts will be displayed once at least 5 test results have been collected. Run multiple tests to see the performance trends over time.</p>
    </div>
</div>

