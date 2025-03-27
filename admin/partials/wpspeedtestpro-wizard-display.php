<?php
/**
 * Template for the setup wizard
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 */
?>
<div id="wpspeedtestpro-wizard-template" style="display: none;">
    <div class="wpspeedtestpro-modal">
        <div class="wpspeedtestpro-modal-content">
            <div class="wizard-header">
                <h2>Welcome to WP Speed Test Pro</h2>
                <button class="close-wizard">&times;</button>
            </div>
            
            <div class="wizard-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 25%;!important"></div>
                </div>
                <div class="step-indicator">Step <span class="current-step">1</span> of 4</div>
            </div>

            <div class="wizard-body">
                <!-- Step templates will be loaded dynamically -->
            </div>

            <div class="wizard-footer">
                <button class="button prev-step" style="display: none;">Previous</button>
                <button class="button button-primary next-step">Next</button>
                <button class="button button-primary finish-setup" style="display: none;">
                    Go to Dashboard
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Step 1 Template -->
<script type="text/template" id="wizard-step-1">
    <div class="mission-statement">
        <h3>Our Mission</h3>
        <p>WP Speedtest Pro helps WordPress users choose better hosting with clear, data-driven performance insights. We identify the best providers, call out the worst, and help users get more value from their hosting. Committed to the WordPress community, we offer this service free.</p>
    </div>
    <div class="initial-setup">
        <h3>Let's get started with the basic setup</h3>
        <div class="form-group">
            <label for="gcp-region">Select Closest GCP Region</label>
            <select id="gcp-region" name="gcp-region" required></select>
        </div>
        <div class="form-group">
            <label for="hosting-provider">Select Your Hosting Provider</label>
            <select id="hosting-provider" name="hosting-provider" required></select>
        </div>
        <div class="form-group">
            <label for="hosting-package">Select Your Hosting Package</label>
            <select id="hosting-package" name="hosting-package" required></select>
        </div>
        <div class="form-group privacy-opt">
            <label>
                <input type="checkbox" id="allow-data-collection" name="allow-data-collection">
                Help improve WP Speed Test Pro by allowing anonymous data collection
            </label>
            <p class="privacy-note">Your data helps us identify trends and improve hosting recommendations for the WordPress community. <a href="https://wpspeedtestpro.com/privacy-policy/" target="_blank">Read our privacy policy</a>.</p>
        </div>
    </div>
</script>

<!-- Additional step templates... -->