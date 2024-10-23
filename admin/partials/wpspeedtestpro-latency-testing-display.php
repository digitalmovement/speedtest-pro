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
            <p>Running continuous latency tests can affect server performance. This feature should not be used on production websites.</p>
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