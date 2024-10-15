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

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1>WP Speed Test Pro - Page Speed Testing</h1>
    <?php print_r($data); ?>

    <?php if (!isset($data)) { echo "There was an error fetching data."; return; } ?>

    <div id="speedvitals-credits-info">
        <h3>Account Credits</h3>
        <p>Lighthouse Credits: <?php echo esc_html($data['credits']['lighthouse']['available_credits']); ?></p>
        <p>TTFB Credits: <?php echo esc_html($data['credits']['ttfb']['available_credits']); ?></p>
        <p>Next Refill: <?php echo date('Y-m-d H:i:s', $data['credits']['credits_refill_date']); ?></p>
    </div>

    <h2>Run a New Test</h2>
    <form id="speedvitals-test-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="speedvitals-url">URL to Test</label></th>
                <td>
                    <select id="speedvitals-url" name="url">
                        <?php foreach ($data['pages_and_posts'] as $id => $title) : ?>
                            <option value="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html($title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="speedvitals-location">Test Location</label></th>
                <td>
                    <select id="speedvitals-location" name="location">
                        <?php foreach ($data['locations'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="speedvitals-device">Device</label></th>
                <td>
                    <select id="speedvitals-device" name="device">
                        <?php foreach ($data['devices'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="speedvitals-frequency">Test Frequency</label></th>
                <td>
                    <select id="speedvitals-frequency" name="frequency">
                        <?php foreach ($data['frequencies'] as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Run Test">
        </p>
    </form>

    <div id="speedvitals-test-status" style="display: none;">
        <h3>Test Status</h3>
        <p id="speedvitals-status-message"></p>
        <div id="speedvitals-loading-gif" style="display: none;">
            <img src="<?php echo esc_url(admin_url('images/loading.gif')); ?>" alt="Loading">
        </div>
    </div>

    <h2>Test Results</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>URL</th>
                <th>Device</th>
                <th>Location</th>
                <th>Date</th>
                <th>Performance Score</th>
                <th>FCP</th>
                <th>SI</th>
                <th>LCP</th>
                <th>TTI</th>
                <th>TBT</th>
                <th>CLS</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="speedvitals-results-body">
            <?php foreach ($data['test_results'] as $result) : ?>
                <tr>
                    <td><?php echo esc_html($result['id']); ?></td>
                    <td><?php echo esc_url($result['url']); ?></td>
                    <td><?php echo esc_html($result['device']); ?></td>
                    <td><?php echo esc_html($result['location']); ?></td>
                    <td><?php echo esc_html($result['test_date']); ?></td>
                    <td><?php echo esc_html($result['performance_score']); ?></td>
                    <td><?php echo esc_html($result['first_contentful_paint']); ?></td>
                    <td><?php echo esc_html($result['speed_index']); ?></td>
                    <td><?php echo esc_html($result['largest_contentful_paint']); ?></td>
                    <td><?php echo esc_html($result['time_to_interactive']); ?></td>
                    <td><?php echo esc_html($result['total_blocking_time']); ?></td>
                    <td><?php echo esc_html($result['cumulative_layout_shift']); ?></td>
                    <td>
                        <a href="<?php echo esc_url($result['report_url']); ?>" target="_blank">View Report</a>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
        $('#speedvitals-test-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        
        $('#speedvitals-test-status').show();
        $('#speedvitals-loading-gif').show();
        $('#speedvitals-status-message').text('Initiating test...');

        var data = {
            action: 'speedvitals_run_test',
            nonce: wpspeedtestpro_ajax.nonce
        };

        // Convert the serialized array to an object
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });

        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    checkTestStatus(response.data.test_id);
                } else {
                    $('#speedvitals-status-message').text('Error: ' + response.data);
                    $('#speedvitals-loading-gif').hide();
                }
            },
            error: function() {
                $('#speedvitals-status-message').text('An error occurred. Please try again.');
                $('#speedvitals-loading-gif').hide();
            }
        });
    });

    function checkTestStatus(testId) {
        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'speedvitals_get_test_status',
                nonce: wpspeedtestpro_ajax.nonce,
                test_id: testId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'completed') {
                        $('#speedvitals-status-message').text('Test completed successfully!');
                        $('#speedvitals-loading-gif').hide();
                        // Refresh the results table
                        location.reload();
                    } else {
                        $('#speedvitals-status-message').text('Test in progress: ' + response.data.status);
                        setTimeout(function() {
                            checkTestStatus(testId);
                        }, 5000);
                    }
                } else {
                    $('#speedvitals-status-message').text('Error: ' + response.data);
                    $('#speedvitals-loading-gif').hide();
                }
            },
            error: function() {
                $('#speedvitals-status-message').text('An error occurred while checking test status.');
                $('#speedvitals-loading-gif').hide();
            }
        });
    }

    $('.speedvitals-cancel-scheduled-test').on('click', function() {
        var scheduleId = $(this).data('id');
        if (confirm('Are you sure you want to cancel this scheduled test?')) {
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'speedvitals_cancel_scheduled_test',
                    nonce: wpspeedtestpro_ajax.nonce,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Scheduled test cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    $('#speedvitals-delete-old-results-form').on('submit', function(e) {
        e.preventDefault();
        var days = $('#speedvitals-delete-days').val();
        
        if (confirm('Are you sure you want to delete results older than ' + days + ' days?')) {
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'speedvitals_delete_old_results',
                    nonce: wpspeedtestpro_ajax.nonce,
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        alert('Old results deleted successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });
});
</script>