<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Admin page template for PageSpeed testing
 */
?>
<div class="wrap">
    <h1>Google PageSpeed Testing</h1>

    <div id="test-status" class="notice notice-info" style="display: none;!important">
            <p>Test in progress... Please wait!</p>
    </div>

    <div class="pagespeed-form-container">
        <h2>Run a New Test</h2>
        <form id="pagespeed-test-form">
            <div class="pagespeed-form-row">
                <div class="pagespeed-form-column">
                    <label for="test-url">Select Page</label>
                    <select name="test-url" id="test-url">
                        <option value="">Select a page...</option>
                        <?php
                        // Get all published pages
                        $pages = get_pages(['post_status' => 'publish']);
                        if (!empty($pages)) {
                            echo '<optgroup label="Pages">';
                            foreach ($pages as $page) {
                                printf(
                                    '<option value="%s">%s</option>',
                                    esc_url(get_permalink($page->ID)),
                                    esc_html($page->post_title)
                                );
                            }
                            echo '</optgroup>';
                        }
                        
                        // Get all published posts
                        $posts = get_posts(['post_status' => 'publish', 'posts_per_page' => -1]);
                        if (!empty($posts)) {
                            echo '<optgroup label="Posts">';
                            foreach ($posts as $post) {
                                printf(
                                    '<option value="%s">%s</option>',
                                    esc_url(get_permalink($post->ID)),
                                    esc_html($post->post_title)
                                );
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>
                <!--
                <div class="pagespeed-form-column">
                    <label for="test-device">Device Type</label>
                    <select name="device" id="test-device">
                        <option value="both">Desktop & Mobile</option>
                        <option value="desktop">Desktop Only</option>
                        <option value="mobile">Mobile Only</option>
                    </select>
                </div>
                -->

                <input id="test-device" type="hidden" name="device" value="both" />

                <div class="pagespeed-form-column">
                    <label for="test-frequency">Test Frequency</label>
                    <select name="frequency" id="test-frequency">
                        <option value="once">One-time Test</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>

                <div class="pagespeed-form-column submit-column">
                    <?php wp_nonce_field('wpspeedtestpro_ajax_nonce', 'wpspeedtestpro_ajax_nonce'); ?>
                    <button type="submit" class="button button-primary">Run Test</button>
                </div>
            </div>
        </form>
    </div>



    <div id="latest-results" style="display: none;" class="results-panel">
        <h2>Latest Test Results</h2>
        <div class="results-grid">
            <div class="device-results desktop">
                <h3>Desktop Results</h3>
                <div class="score-circle performance">
                    <span class="score-label">Performance</span>
                    <span class="score-value">0</span>
                </div>
                <div class="scores-grid">
                    <div class="score-item">
                        <span class="label">Accessibility</span>
                        <span class="value">0</span>
                    </div>
                    <div class="score-item">
                        <span class="label">Best Practices</span>
                        <span class="value">0</span>
                    </div>
                    <div class="score-item">
                        <span class="label">SEO</span>
                        <span class="value">0</span>
                    </div>
                </div>
                <div class="metrics-panel">
                    <h4>Core Web Vitals</h4>
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <span class="label">FCP</span>
                            <span class="value">0s</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">LCP</span>
                            <span class="value">0s</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">CLS</span>
                            <span class="value">0</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">TBT</span>
                            <span class="value">0ms</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="device-results mobile">
                <h3>Mobile Results</h3>
                <div class="score-circle performance">
                    <span class="score-label">Performance</span>
                    <span class="score-value">0</span>
                </div>
                <div class="scores-grid">
                    <div class="score-item">
                        <span class="label">Accessibility</span>
                        <span class="value">0</span>
                    </div>
                    <div class="score-item">
                        <span class="label">Best Practices</span>
                        <span class="value">0</span>
                    </div>
                    <div class="score-item">
                        <span class="label">SEO</span>
                        <span class="value">0</span>
                    </div>
                </div>
                <div class="metrics-panel">
                    <h4>Core Web Vitals</h4>
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <span class="label">FCP</span>
                            <span class="value">0s</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">LCP</span>
                            <span class="value">0s</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">CLS</span>
                            <span class="value">0</span>
                        </div>
                        <div class="metric-item">
                            <span class="label">TBT</span>
                            <span class="value">0ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2>Test History</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>URL</th>
                <th>Device</th>
                <th>Performance</th>
                <th>Accessibility</th>
                <th>Best Practices</th>
                <th>SEO</th>
                <th>Test Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="test-results">
            <!-- Results will be populated via JavaScript -->
        </tbody>
    </table>

    <div class="tablenav bottom">
        <div class="alignleft actions">
            <button type="button" id="delete-old-results" class="button">Delete Results Older Than</button>
            <select id="days-to-keep">
                <option value="1">1 day</option>
                <option value="30">30 days</option>
                <option value="60">60 days</option>
                <option value="90">90 days</option>
            </select>
        </div>
    </div>

    <h2>Scheduled Tests</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>URL</th>
                <th>Frequency</th>
                <th>Last Run</th>
                <th>Next Run</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="scheduled-tests">
            <!-- Scheduled tests will be populated via JavaScript -->
        </tbody>
    </table>
</div>