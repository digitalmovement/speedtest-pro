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
<div id="wpspeedtestpro" class="wrap">
    <h1>Google Data Center Latency Testing</h1>

    <?php if (!get_option('wpspeedtestpro_latency_info_dismissed', false)):  ?>
    <div id="latency-info-banner" class="notice notice-info">
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
        <h2 style="margin-top: 0;">Understanding Google Cloud Latency Testing</h2>
        
        <p>Latency testing measures the response time between your server and Google Cloud Platform's global data centers, helping you optimize your website's performance for users worldwide.</p>
        
        <div style="margin: 20px 0;">
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Regional Testing Coverage</h4>
                <div style="margin-left: 20px;">
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #2271b1;">Europe</strong>
                        <p style="margin: 0; color: #555;">London, Frankfurt, Netherlands, Paris, Warsaw, Milan, and more. Ideal for European audience targeting.</p>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #2271b1;">United States & Canada</strong>
                        <p style="margin: 0; color: #555;">Multiple locations including Iowa, Oregon, Virginia, Montreal, ensuring comprehensive North American coverage.</p>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #2271b1;">Asia Pacific</strong>
                        <p style="margin: 0; color: #555;">Tokyo, Singapore, Mumbai, Sydney, covering major APAC business centers.</p>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Latency Measurements</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Current Latency:</strong> Latest response time measurement
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Fastest Latency:</strong> Best response time achieved
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Slowest Latency:</strong> Worst response time recorded
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Testing Options</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Run Once:</strong> Single comprehensive test of all regions
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Continuous Testing:</strong> Hourly tests for 24-hour monitoring
                        <br>
                        <span style="color: #d63638;">⚠️ Note: Continuous testing should be used carefully on production sites</span>
                    </p>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Why Latency Testing Matters</h4>
                <ul style="margin: 0; color: #555; list-style-type: disc; padding-left: 40px;">
                    <li>Identify optimal CDN regions for content delivery</li>
                    <li>Monitor connection quality to different global regions</li>
                    <li>Optimize server location choices for target audiences</li>
                    <li>Track performance trends over time</li>
                    <li>Help make informed infrastructure decisions</li>
                </ul>
            </div>
        </div>

        <p style="margin-top: 15px; color: #555;">Regular latency testing helps ensure optimal performance for your global audience and informs strategic infrastructure decisions.</p>
    </div>
    <?php endif; ?>

    <div id="latency-test-container">
        <button id="run-once-test" class="button button-primary">Run Once</button>
        <button id="continuous-test" class="button button-primary">Start Continuous Testing</button>
        <button id="stop-test" class="button button-secondary" style="display:none;">Stop Continuous Testing</button>
        <button id="delete-results" class="button button-secondary delete-button">Delete All Results</button>
        <p id="test-status"></p>
        <div id="next-test-countdown"></div>
    </div>
    <div id="results-container">
        <div>
            <label for="time-range">Select Time Range:</label>
            <select id="time-range" class="time-range-dropdown">
                <option value="24_hours">Last 24 Hours</option>
                <option value="7_days">Last 7 Days</option>
                <option value="90_days">Last 90 Days</option>
            </select>
        </div>
        <h2>Latest Results</h2>
        <table id="latency-results" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Region</th>
                    <th>Current Latency (ms)</th>
                    <th>Fastest Latency (ms)</th>
                    <th>Slowest Latency (ms)</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <!-- Results will be populated here -->
            </tbody>
        </table>
    </div>
    <div id="tabs">
        <ul>
            <li><a href="#tabs-1">Europe</a></li>
            <li><a href="#tabs-2">US</a></li>
            <li><a href="#tabs-3">Asia</a></li>
            <li><a href="#tabs-4">Other</a></li>
        </ul>
        <div id="tabs-1">
            <div id="graphs-europe"></div>
        </div>
        <div id="tabs-2">
            <div id="graphs-us"></div>
        </div>
        <div id="tabs-3">
            <div id="graphs-asia"></div>
        </div>
        <div id="tabs-4">
            <div id="graphs-other"></div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Discard all latency Data?</h2>
            <p>This cannot be undone.</p>
            <div class="modal-footer">
                <button id="cancelButton" class="button button-secondary">Cancel</button>
                <button id="confirmDelete" class="button discard-button">Discard</button>
            </div>
        </div>
    </div>

    <!-- Continuous Testing Warning Modal -->
    <div id="continuousModal" class="modal">
        <div class="modal-content">
            <h2>Warning</h2>
            <p>Running continuous latency tests can affect server performance. This feature should not be used on production websites. This will execute once every hour until terminated!</p>
            <div class="modal-footer">
                <button id="cancelContinuous" class="button button-secondary">Cancel</button>
                <button id="confirmContinuous" class="button button-primary">Continue</button>
            </div>
        </div>
    </div>
<style>
    .graph-container {
        height: 400px;
        margin-bottom: 40px;
    }
    #tabs {
        margin-top: 20px;
    }
</style>