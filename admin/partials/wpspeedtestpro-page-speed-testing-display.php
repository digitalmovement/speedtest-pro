<div class="wrap">
    <h1>Google PageSpeed Insights</h1>
    
    <div class="pagespeed-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#test-tab" class="nav-tab nav-tab-active">Run Test</a>
            <a href="#results-tab" class="nav-tab">Test Results</a>
            <a href="#scheduled-tab" class="nav-tab">Scheduled Tests</a>
        </nav>

        <div id="test-tab" class="tab-content active">
            <div class="card">
                <h2>Run PageSpeed Test</h2>
                <form id="pagespeed-test-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="test-url">Select Page</label></th>
                            <td>
                                <select name="test-url" id="test-url">
                                    <option value="">Select a page...</option>
                                    <?php
                                    $pages = get_pages();
                                    foreach ($pages as $page) {
                                        echo sprintf(
                                            '<option value="%s">%s</option>',
                                            esc_url(get_permalink($page->ID)),
                                            esc_attr($page->post_title)
                                        );
                                    }
                                    
                                    $posts = get_posts(['posts_per_page' => -1]);
                                    if (!empty($posts)) {
                                        echo '<optgroup label="Posts">';
                                        foreach ($posts as $post) {
                                            echo sprintf(
                                                '<option value="%s">%s</option>',
                                                esc_url(get_permalink($post->ID)),
                                                esc_attr($post->post_title)
                                            );
                                        }
                                        echo '</optgroup>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test-frequency">Test Frequency</label></th>
                            <td>
                                <select name="test-frequency" id="test-frequency">
                                    <option value="once">One-time Test</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php wp_nonce_field('pagespeed_test_nonce', 'pagespeed_test_nonce'); ?>
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="run-test">Run Test</button>
                        <span class="spinner" style="float: none; margin-top: 0;"></span>
                    </p>
                </form>
            </div>

            <div class="card" id="latest-results" style="display: none;">
                <h2>Latest Test Results</h2>
                <div class="results-grid">
                    <div class="results-column desktop">
                        <h3>Desktop Results</h3>
                        <div class="score-circle performance">
                            <span class="score">0</span>
                            <span class="label">Performance</span>
                        </div>
                        <div class="metrics-grid">
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">Accessibility</span>
                            </div>
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">Best Practices</span>
                            </div>
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">SEO</span>
                            </div>
                        </div>
                    </div>

                    <div class="results-column mobile">
                        <h3>Mobile Results</h3>
                        <div class="score-circle performance">
                            <span class="score">0</span>
                            <span class="label">Performance</span>
                        </div>
                        <div class="metrics-grid">
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">Accessibility</span>
                            </div>
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">Best Practices</span>
                            </div>
                            <div class="metric">
                                <span class="score">0</span>
                                <span class="label">SEO</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="core-web-vitals">
                    <h3>Core Web Vitals</h3>
                    <div class="metrics-grid">
                        <div class="metric">
                            <span class="score">0</span>
                            <span class="label">FCP</span>
                        </div>
                        <div class="metric">
                            <span class="score">0</span>
                            <span class="label">LCP</span>
                        </div>
                        <div class="metric">
                            <span class="score">0</span>
                            <span class="label">CLS</span>
                        </div>
                        <div class="metric">
                            <span class="score">0</span>
                            <span class="label">FID</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="results-tab" class="tab-content">
            <div class="card">
                <h2>Historical Test Results</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Test Date</th>
                            <th>Desktop Performance</th>
                            <th>Mobile Performance</th>
                            <th>Accessibility</th>
                            <th>Best Practices</th>
                            <th>SEO</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="results-table-body">
                        <!-- Results will be populated via JavaScript -->
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <button type="button" class="button" id="delete-old-results">
                            Delete Results Older Than
                        </button>
                        <select id="days-to-keep">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div id="scheduled-tab" class="tab-content">
            <div class="card">
                <h2>Scheduled Tests</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Frequency</th>
                            <th>Last Run</th>
                            <th>Next Run</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="scheduled-table-body">
                        <!-- Scheduled tests will be populated via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>