<!-- Placeholder for wpspeedtestpro-latency-display.php -->
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
    <button id="start-test" class="button button-primary">Start Latency Test</button>
    <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
    <button id="delete-results" class="button button-secondary delete-button">Delete All Results</button>
    <p id="test-status"></p>
    <div id="countdown"></div>
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
<div id="graphs-container">
    <h2>Graphs for Each Region</h2>
</div>

</div>


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