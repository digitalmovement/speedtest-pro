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

 function convert_to_seconds($milliseconds) {
    return number_format($milliseconds / 1000, 2) . 's';
}

function get_color_class($metric, $value) {
    $thresholds = [
        'performance_score' => ['green' => 90, 'amber' => 50],
        'first_contentful_paint' => ['green' => 1.8, 'amber' => 3],
        'speed_index' => ['green' => 3.4, 'amber' => 5.8],
        'largest_contentful_paint' => ['green' => 2.5, 'amber' => 4],
        'total_blocking_time' => ['green' => 200, 'amber' => 600],
        'cumulative_layout_shift' => ['green' => 0.1, 'amber' => 0.25]
    ];

    if ($metric === 'performance_score') {
        if ($value >= $thresholds[$metric]['green']) return 'green';
        if ($value >= $thresholds[$metric]['amber']) return 'amber';
        return 'red';
    } else {
        if ($value <= $thresholds[$metric]['green']) return 'green';
        if ($value <= $thresholds[$metric]['amber']) return 'amber';
        return 'red';
    }
}

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1>WP Speed Test Pro - Page Speed Testing</h1>

    <?php    
    if (!get_option('wpspeedtestpro_pagespeed_info_dismissed', false)) : 
    ?>
    <div id="pagespeed-info-banner" class="notice notice-info">
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
        <h2 style="margin-top: 0;">Understanding Core Web Vitals and Page Speed</h2>
        
        <p>Page speed and Core Web Vitals are crucial for user experience and SEO rankings. Our testing tool measures the following key metrics:</p>
        
        <div style="margin: 20px 0;">
            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Performance Score (0-100)</h4>
                <p style="margin: 0 0 5px 20px; color: #555;">
                    An overall score calculated by Lighthouse that indicates your page's performance.
                </p>
                <div style="margin-left: 20px; font-size: 13px;">
                    <span style="color: #0a3622;">• 90-100 (Good)</span> |
                    <span style="color: #a66f00;">• 50-89 (Needs Improvement)</span> |
                    <span style="color: #a60000;">• 0-49 (Poor)</span>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Core Web Vitals</h4>
                <div style="margin-left: 20px;">
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Largest Contentful Paint (LCP)</strong>: Measures loading performance. Should occur within 2.5 seconds.
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>First Contentful Paint (FCP)</strong>: Time until the first text/image appears. Should be under 1.8 seconds.
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Cumulative Layout Shift (CLS)</strong>: Measures visual stability. Should be less than 0.1.
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Total Blocking Time (TBT)</strong>: Measures interactivity. Should be under 200 milliseconds.
                    </p>
                    <p style="margin: 5px 0; color: #555;">
                        <strong>Speed Index (SI)</strong>: How quickly content is visually populated. Should be under 3.4 seconds.
                    </p>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #1d2327;">Why These Metrics Matter</h4>
                <ul style="margin: 0; color: #555; list-style-type: disc; padding-left: 40px;">
                    <li>Directly impact your Google search rankings</li>
                    <li>Affect user experience and engagement</li>
                    <li>Influence conversion rates and bounce rates</li>
                    <li>Critical for mobile user experience</li>
                    <li>Impact your website's credibility</li>
                </ul>
            </div>
        </div>

        <p style="margin-top: 15px; color: #555;">Regular testing helps identify and fix performance issues before they impact your users and search rankings.</p>
    </div>
    <?php endif; ?>

    <?php if (!isset($data)) { echo "There was an error fetching data."; return; } ?>

    <div id="speedvitals-credits-info">
        <h3>Account Credits</h3>
        <p>Lighthouse Credits: <?php echo esc_html($data['credits']['lighthouse']['available_credits']); ?></p>
        <!-- Not used at the moment  <p>TTFB Credits: <?php echo esc_html($data['credits']['ttfb']['available_credits']); ?></p> -->
         <p>Next Refill: <?php echo wp_date('Y-m-d H:i:s', $data['credits']['credits_refill_date']); ?></p>
        <?php if ($data['credits']['lighthouse']['available_credits'] <= 0) : ?>
            <p style="color: red;">You have no Lighthouse credits remaining. Please <a href="https://speedvitals/pricing" target="_blank">purchase more credits or wait until the next refill</a>.</p>
        <?php endif; ?>
        </div>
    <h2>Run a New Test</h2>
    <form id="speedvitals-test-form">
        <div class="speedvitals-form-container">
            <div class="speedvitals-form-row">
                <div class="speedvitals-form-column">
                    <label for="speedvitals-url">URL to Test</label>
                    <select id="speedvitals-url" name="url">
                        <?php foreach ($data['pages_and_posts'] as $id => $title) : ?>
                            <option value="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html($title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="speedvitals-form-column">
                    <label for="speedvitals-location">Test Location</label>
                    <select id="speedvitals-location" name="location">
                        <?php foreach ($data['locations'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="speedvitals-form-column">
                    <label for="speedvitals-device">Device</label>
                    <select id="speedvitals-device" name="device">
                        <?php foreach ($data['devices'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="speedvitals-form-column">
                    <label for="speedvitals-frequency">Test Frequency</label>
                    <select id="speedvitals-frequency" name="frequency">
                        <?php foreach ($data['frequencies'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="speedvitals-form-column submit-column">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Run Test">
                </div>
            </div>
        </div>
    </form>

    <div id="speedvitals-test-status" style="display: none;">
        <h3>Test Status</h3>
        <p id="speedvitals-status-message"></p>
        <div id="speedvitals-loading-gif" style="display: none;">
            <img src="<?php echo esc_url(admin_url('images/loading.gif')); ?>" alt="Loading">
        </div>
    </div>

    <h2>Test Results</h2>
    <table class="speedvitals-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
            <th title="Unique identifier for each test">Test ID</th>
            <th title="The URL that was tested">URL</th>
            <th title="The device type used for the test">Device</th>
            <th title="The location from which the test was run">Location</th>
            <th title="The date and time when the test was conducted">Date</th>
            <th title="Overall performance score (0-100). Higher is better.">Performance Score</th>
            <th title="First Contentful Paint (FCP): Time when the first text or image is painted. Lower is better.">FCP (s)</th>
            <th title="Speed Index (SI): How quickly the contents of a page are visibly populated. Lower is better.">SI (s)</th>
            <th title="Largest Contentful Paint (LCP): Time when the largest text or image is painted. Lower is better.">LCP (s)</th>
            <th title="Total Blocking Time (TBT): Sum of all time periods between FCP and Time to Interactive, when task length exceeded 50ms. Lower is better.">TBT (s)</th>
            <th title="Cumulative Layout Shift (CLS): Measures visual stability. Lower is better.">CLS</th>
            <th title="Actions you can take on this test result">Actions</th>
            </tr>
        </thead>
        <tbody id="speedvitals-results-body"><?php
            // In your table rendering loop:
            foreach ($data['test_results'] as $result) : ?>
                <tr id="test-row-<?php echo esc_attr($result['test_id']); ?>">
                    <td><?php echo esc_html($result['test_id']); ?></td>
                    <td><?php echo esc_url($result['url']); ?></td>
                    <td><?php echo esc_html($result['device']); ?></td>
                    <td><?php echo esc_html($result['location']); ?></td>
                    <td><?php echo esc_html($result['test_date']); ?></td>
                    <?php if (empty($result['performance_score'])) : ?>
                        <td>Pending Results...</td>
                        <td></td><td></td><td></td><td></td><td></td>
                    <?php else : ?>
                        <td class="<?php echo get_color_class('performance_score', $result['performance_score']); ?>"><?php echo esc_html($result['performance_score']); ?></td>
                        <td class="<?php echo get_color_class('first_contentful_paint', $result['first_contentful_paint'] / 1000); ?>"><?php echo esc_html(convert_to_seconds($result['first_contentful_paint'])); ?></td>
                        <td class="<?php echo get_color_class('speed_index', $result['speed_index'] / 1000); ?>"><?php echo esc_html(convert_to_seconds($result['speed_index'])); ?></td>
                        <td class="<?php echo get_color_class('largest_contentful_paint', $result['largest_contentful_paint'] / 1000); ?>"><?php echo esc_html(convert_to_seconds($result['largest_contentful_paint'])); ?></td>
                        <td class="<?php echo get_color_class('total_blocking_time', $result['total_blocking_time']); ?>"><?php echo esc_html(convert_to_seconds($result['total_blocking_time'])); ?></td>
                        <td class="<?php echo get_color_class('cumulative_layout_shift', $result['cumulative_layout_shift']); ?>"><?php echo esc_html(number_format($result['cumulative_layout_shift'], 2)); ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if (empty($result['performance_score'])) : ?>
                            Report Pending
                        <?php else : ?>
                            <a href="<?php echo esc_url($result['report_url']); ?>" target="_blank">View Report</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Scheduled Tests</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>URL</th>
                <th>Device</th>
                <th>Location</th>
                <th>Frequency</th>
                <th>Next Run</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="speedvitals-scheduled-tests-body">
            <?php foreach ($data['scheduled_tests'] as $test) : ?>
                <tr>
                    <td><?php echo esc_html($test['id']); ?></td>
                    <td><?php echo esc_url($test['url']); ?></td>
                    <td><?php echo esc_html($test['device']); ?></td>
                    <td><?php echo esc_html($test['location']); ?></td>
                    <td><?php echo esc_html($test['frequency']); ?></td>
                    <td><?php echo esc_html($test['next_run']); ?></td>
                    <td>
                        <button class="button button-secondary speedvitals-cancel-scheduled-test" data-id="<?php echo esc_attr($test['id']); ?>">Cancel</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Maintenance</h2>
    <form id="speedvitals-delete-old-results-form">
        <p>
            <label for="speedvitals-delete-days">Delete results older than:</label>
            <input type="number" id="speedvitals-delete-days" name="days" min="1" value="30">
            days
        </p>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-secondary" value="Delete Old Results">
        </p>
    </form>
</div>

