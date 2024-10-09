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
        </ul>
        
        <div id="tab-latest-results">
            <h2>Latest Benchmark Results</h2>
            <div id="latest-results-chart-container" class="chart-container">
                <canvas id="latest-results-chart"></canvas>
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
    </div>
    <div class="performance-note">
        <p><strong>Note:</strong> Historical charts will be displayed once at least 5 test results have been collected. Run multiple tests to see the performance trends over time.</p>
    </div>
</div>

