jQuery(document).ready(function($) {
    // Initialize wizard if it hasn't been completed
    if (!localStorage.getItem('wpspeedtestpro_setup_complete')) {
        initSetupWizard();
    }

    function initSetupWizard() {
        // Create and append wizard HTML
        const wizardHtml = `
            <div id="wpspeedtestpro-setup-wizard" class="wpspeedtestpro-modal">
                <div class="wpspeedtestpro-modal-content">
                    <div class="wizard-header">
                        <h2>Welcome to WP Speed Test Pro</h2>
                        <button class="close-wizard">&times;</button>
                    </div>
                    
                    <div class="wizard-progress">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="step-indicator">Step <span class="current-step">1</span> of 4</div>
                    </div>

                    <div class="wizard-body">
                        <!-- Step 1: Welcome -->
                        <div class="wizard-step" data-step="1">
                            <div class="mission-statement">
                                <h3>Our Mission</h3>
                                <p>WP Speed Test Pro helps WordPress users choose better hosting with clear, data-driven performance insights. We identify the best providers, call out the worst, and help users get more value from their hosting. Committed to the WordPress community, we offer this service free.</p>
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
                                        <input type="checkbox" id="allow-data-collection" name="allow-data-collection" checked>
                                        Help improve WP Speed Test Pro by allowing anonymous data collection
                                    </label>
                                    <p class="privacy-note">Your data helps us identify trends and improve hosting recommendations for the WordPress community.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: UptimeRobot Setup -->
                        <div class="wizard-step" data-step="2" style="display: none;">
                            <h3>UptimeRobot Integration</h3>
                            <p>Monitor your website's uptime and performance with UptimeRobot integration.</p>
                            <div class="form-group">
                                <label for="uptimerobot-key">UptimeRobot API Key</label>
                                <input type="text" id="uptimerobot-key" name="uptimerobot-key">
                                <p class="help-text">
                                    Don't have an API key? 
                                    <a href="https://uptimerobot.com/signUp" target="_blank">Sign up for free</a>
                                </p>
                            </div>
                            <p class="skip-note">You can skip this step and set it up later.</p>
                        </div>

                        <!-- Step 3: Initial Tests -->
                        <div class="wizard-step" data-step="3" style="display: none;">
                            <h3>Let's Run Your First Tests</h3>
                            <div class="test-group">
                                <div class="test-item">
                                    <button class="test-button" data-test="latency">Run Latency Test</button>
                                    <div class="test-progress" style="display: none;">
                                        <div class="progress-bar"></div>
                                    </div>
                                    <span class="test-status"></span>
                                </div>
                                <div class="test-item">
                                    <button class="test-button" data-test="ssl">Run SSL Test</button>
                                    <div class="test-progress" style="display: none;">
                                        <div class="progress-bar"></div>
                                    </div>
                                    <span class="test-status"></span>
                                </div>
                                <div class="test-item">
                                    <button class="test-button" data-test="performance">Run Performance Test</button>
                                    <div class="test-progress" style="display: none;">
                                        <div class="progress-bar"></div>
                                    </div>
                                    <span class="test-status"></span>
                                </div>
                                <div class="test-item">
                                    <button class="test-button" data-test="pagespeed">Run PageSpeed Test</button>
                                    <div class="test-progress" style="display: none;">
                                        <div class="progress-bar"></div>
                                    </div>
                                    <span class="test-status"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Completion -->
                        <div class="wizard-step" data-step="4" style="display: none;">
                            <h3>Setup Complete!</h3>
                            <p>You're all set to start monitoring your WordPress site's performance.</p>
                            <div class="completion-summary">
                                <h4>Here's what we've set up:</h4>
                                <ul class="setup-summary"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-footer">
                        <button class="prev-step" style="display: none;">Previous</button>
                        <button class="next-step">Next</button>
                        <button class="finish-setup" style="display: none;">Go to Dashboard</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(wizardHtml);

        let currentStep = 1;
        const totalSteps = 4;

        // Load initial data
        loadGCPRegions();
        loadHostingProviders();

        // Navigation handlers
        $('.next-step').on('click', function() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateWizardStep();
                }
            }
        });

        $('.prev-step').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateWizardStep();
            }
        });

        $('.close-wizard').on('click', function() {
            if (confirm('Are you sure you want to exit the setup wizard? You can always access these settings later.')) {
                $('#wpspeedtestpro-setup-wizard').remove();
            }
        });

        // Test execution handlers
        $('.test-button').on('click', function() {
            const testType = $(this).data('test');
            runTest(testType, $(this));
        });

        $('.finish-setup').on('click', function() {
            localStorage.setItem('wpspeedtestpro_setup_complete', 'true');
            $('#wpspeedtestpro-setup-wizard').remove();
            window.location.href = 'admin.php?page=wpspeedtestpro';
        });

        // Provider change handler
        $('#hosting-provider').on('change', function() {
            loadHostingPackages($(this).val());
        });

        function updateWizardStep() {
            $('.wizard-step').hide();
            $(`.wizard-step[data-step="${currentStep}"]`).show();
            $('.current-step').text(currentStep);
            $('.progress-fill').css('width', `${(currentStep / totalSteps) * 100}%`);
            
            $('.prev-step').toggle(currentStep > 1);
            $('.next-step').toggle(currentStep < totalSteps);
            $('.finish-setup').toggle(currentStep === totalSteps);

            if (currentStep === totalSteps) {
                updateCompletionSummary();
            }
        }

        function validateCurrentStep() {
            switch(currentStep) {
                case 1:
                    return $('#gcp-region').val() && $('#hosting-provider').val() && $('#hosting-package').val();
                case 2:
                    return true; // UptimeRobot key is optional
                case 3:
                    return true; // Allow proceeding even if not all tests are run
                default:
                    return true;
            }
        }

        function runTest(testType, $button) {
            $button.prop('disabled', true);
            const $progress = $button.siblings('.test-progress');
            const $status = $button.siblings('.test-status');
            
            $progress.show();
            $status.text('Running test...');

            // Simulate progress bar
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 2;
                $progress.find('.progress-bar').css('width', `${Math.min(progress, 95)}%`);
            }, 100);

            // Make AJAX call to run the test
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: `wpspeedtestpro_${testType}_run_test`,
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    clearInterval(progressInterval);
                    $progress.find('.progress-bar').css('width', '100%');
                    $status.text('Test completed');
                    $button.prop('disabled', false);
                    
                    setTimeout(() => {
                        $progress.hide();
                        $progress.find('.progress-bar').css('width', '0%');
                    }, 1000);
                },
                error: function() {
                    clearInterval(progressInterval);
                    $status.text('Test failed');
                    $button.prop('disabled', false);
                    $progress.hide();
                }
            });
        }

        function getRegionFlag(regionName) {
            // Mapping of region names to two-letter country codes for FlagCDN
            const flagMapping = {
                'Johannesburg': 'za',
                'Taiwan': 'tw',
                'Hong Kong': 'hk',
                'Tokyo': 'jp',
                'Osaka': 'jp',
                'Seoul': 'kr',
                'Mumbai': 'in',
                'Delhi': 'in',
                'Singapore': 'sg',
                'Jakarta': 'id',
                'Sydney': 'au',
                'Melbourne': 'au',
                'Warsaw': 'pl',
                'Finland': 'fi',
                'Madrid': 'es',
                'Belgium': 'be',
                'Berlin': 'de',
                'Turin': 'it',
                'London': 'gb',
                'Frankfurt': 'de',
                'Netherlands': 'nl',
                'Zurich': 'ch',
                'Milan': 'it',
                'Paris': 'fr',
                'Doha': 'qa',
                'Dammam': 'sa',
                'Tel Aviv': 'il',
                'Montréal': 'ca',
                'Toronto': 'ca',
                'São Paulo': 'br',
                'Santiago': 'cl',
                'Iowa': 'us',
                'South Carolina': 'us',
                'North Virginia': 'us',
                'Columbus': 'us',
                'Dallas': 'us',
                'Oregon': 'us',
                'Los Angeles': 'us',
                'Salt Lake City': 'us',
                'Las Vegas': 'us'
            };
        
            const countryCode = flagMapping[regionName] || 'globe';
            if (countryCode === 'globe') {
                return '<i class="fas fa-globe"></i>'; // FontAwesome globe icon as fallback
            }
            
            return `<img src="https://flagcdn.com/24x18/${countryCode}.png" 
                         srcset="https://flagcdn.com/48x36/${countryCode}.png 2x,
                                 https://flagcdn.com/72x54/${countryCode}.png 3x"
                         width="24" 
                         height="18" 
                         alt="${regionName} flag"
                         class="region-flag">`;
        }
        
        function loadGCPRegions() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_get_gcp_endpoints',
                    nonce: wpspeedtestpro_wizard.gcp_endpoints_nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#gcp-region');
                        $select.empty();
                        
                        // Add CSS for flag styling
                        if (!$('#gcp-region-styles').length) {
                            $('head').append(`
                                <style id="gcp-region-styles">
                                    .gcp-region-option {
                                        display: flex;
                                        align-items: center;
                                        gap: 8px;
                                        padding: 4px;
                                    }
                                    .region-flag {
                                        border-radius: 2px;
                                        box-shadow: 0 0 1px rgba(0,0,0,0.2);
                                    }
                                    .fa-globe {
                                        width: 24px;
                                        height: 18px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: #666;
                                    }
                                    .select2-container {
                                        min-width: 200px;
                                    }
                                </style>
                            `);
                        }
        
                        // Style select and initialize Select2
                        if (!$select.hasClass('styled-select')) {
                            $select.addClass('styled-select');
                            $select.css({
                                'padding': '8px',
                                'line-height': '1.4',
                                'height': 'auto'
                            });
                        }
        
                        response.data.forEach(region => {
                            const flag = getRegionFlag(region.region_name);
                            $select.append(`
                                <option value="${region.region}">
                                    ${region.region_name}
                                </option>
                            `);
                        });
        
                        // Initialize Select2 with custom templates
                        if (!$select.next('.select2-container').length) {
                            $select.select2({
                                templateResult: function(data) {
                                    if (!data.id) return data.text;
                                    const flag = getRegionFlag(data.text);
                                    return $(`
                                        <span class="gcp-region-option">
                                            ${flag}
                                            <span>${data.text}</span>
                                        </span>
                                    `);
                                },
                                templateSelection: function(data) {
                                    if (!data.id) return data.text;
                                    const flag = getRegionFlag(data.text);
                                    return $(`
                                        <span class="gcp-region-option">
                                            ${flag}
                                            <span>${data.text}</span>
                                        </span>
                                    `);
                                }
                            });
                        }
                    } else {
                        console.error('Failed to load GCP regions');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading GCP regions:', error);
                    const $select = $('#gcp-region');
                    $select.empty();
                    $select.append('<option value="">Error loading regions</option>');
                }
            });
        }
        function loadHostingProviders() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_get_hosting_providers',
                    nonce: wpspeedtestpro_wizard.hosting_packages_nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#hosting-provider');
                        response.data.forEach(provider => {
                            $select.append(`<option value="${provider.name}">${provider.name}</option>`);
                        });
                    }
                }
            });
        }

        function loadHostingPackages(providerId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_get_provider_packages',
                    provider: providerId,
                    nonce: wpspeedtestpro_wizard.hosting_packages_nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#hosting-package');
                        $select.empty();
                        response.data.forEach(package => {
                            $select.append(`<option value="${package.type}">${package.type}</option>`);
                        });
                    }
                }
            });
        }

        function updateCompletionSummary() {
            const $summary = $('.setup-summary');
            $summary.empty();

            const summaryItems = [
                `Region: ${$('#gcp-region option:selected').text()}`,
                `Hosting Provider: ${$('#hosting-provider option:selected').text()}`,
                `Package: ${$('#hosting-package option:selected').text()}`,
                `Data Collection: ${$('#allow-data-collection').is(':checked') ? 'Enabled' : 'Disabled'}`,
                `UptimeRobot Integration: ${$('#uptimerobot-key').val() ? 'Configured' : 'Skipped'}`
            ];

            summaryItems.forEach(item => {
                $summary.append(`<li>${item}</li>`);
            });
        }
    }
});