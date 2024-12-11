jQuery(document).ready(function($) {
    if (!localStorage.getItem('wpspeedtestpro_setup_complete')) {
        initSetupWizard();
    }

    function initSetupWizard() {
        const wizardHtml = `
            <div id="wpspeedtestpro-setup-wizard" class="wpspeedtestpro-modal">
                <div class="wpspeedtestpro-modal-content">
                    <div class="wizard-header">
                        <h2>Welcome to WP Speed Test Pro</h2>
                        <button class="close-wizard">&times;</button>
                    </div>
                    
                    <div class="wizard-progress">
                        <div class="progress-steps">
                            <div class="step-item active">
                                <div class="step-circle">1</div>
                                <div class="step-line"></div>
                                <div class="step-label">Welcome</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">2</div>
                                <div class="step-line"></div>
                                <div class="step-label">Setup</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">3</div>
                                <div class="step-line"></div>
                                <div class="step-label">UptimeRobot</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">4</div>
                                <div class="step-line"></div>
                                <div class="step-label">Testing</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">5</div>
                                <div class="step-label">Complete</div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-body">
                        <!-- Step 1: Welcome -->
                        <div class="wizard-step" data-step="1">
                            <div class="welcome-content">
                                <h1>Welcome to WP Speed Test Pro! 👋</h1>
                                <p class="welcome-intro">Ready to discover your WordPress site's true performance?</p>
                                
                                <div class="feature-grid">
                                    <div class="feature-item">
                                        <span class="feature-icon">🎯</span>
                                        <h3>Performance Testing</h3>
                                        <p>Get detailed insights about your site's speed and performance</p>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">📊</span>
                                        <h3>Hosting Analysis</h3>
                                        <p>Compare your hosting performance against industry standards</p>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">⚡</span>
                                        <h3>Speed Optimization</h3>
                                        <p>Receive actionable recommendations to improve your site</p>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">🔍</span>
                                        <h3>24/7 Monitoring</h3>
                                        <p>Keep track of your site's performance around the clock</p>
                                    </div>
                                </div>

                                <div class="mission-statement">
                                    <h3>Our Mission</h3>
                                    <p>WP Speed Test Pro helps WordPress users choose better hosting with clear, data-driven performance insights. We identify the best providers, call out the worst, and help users get more value from their hosting. Committed to the WordPress community, we offer this service free.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Setup -->
                        <div class="wizard-step" data-step="2" style="display: none;">
                            <div class="initial-setup">
                                <h3>Basic Configuration</h3>
                                <p>Let's configure your testing environment to get the most accurate results.</p>
                                
                                <div class="form-group">
                                    <label for="gcp-region">Select Closest GCP Region</label>
                                    <select id="gcp-region" name="gcp-region" required></select>
                                    <p class="help-text">Choose the region closest to your target audience for more accurate results</p>
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
                                    <p class="privacy-note">Your data helps us identify trends and improve hosting recommendations for the WordPress community. You can stop sharing at any time in Settings</p>
                                    <p class="privacy-note">For more information you can view our full <a target="_new" href="https://wpspeedtestpro.com/privacy">privacy policy</a></p>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-step" data-step="3" style="display: none;">
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

                        <!-- Step 3: Testing -->
                        <div class="wizard-step" data-step="4" style="display: none;">
                            <div class="testing-container">
                                <h3>Initial Performance Analysis</h3>
                                <p>We'll run a comprehensive series of tests to analyze your site's performance. This might take a few minutes.</p>
                                
                                <div class="test-status-container">
                                    <div class="test-item" data-test="latency">
                                        <span class="test-name">Latency Test</span>
                                        <span class="test-status pending">Pending</span>
                                    </div>
                                    <div class="test-item" data-test="ssl">
                                        <span class="test-name">SSL Security Test</span>
                                        <span class="test-status pending">Pending</span>
                                    </div>
                                    <div class="test-item" data-test="performance">
                                        <span class="test-name">Performance Test</span>
                                        <span class="test-status pending">Pending</span>
                                    </div>
                                    <div class="test-item" data-test="pagespeed">
                                        <span class="test-name">PageSpeed Analysis</span>
                                        <span class="test-status pending">Pending</span>
                                    </div>
                                </div>

                                <div class="overall-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill"></div>
                                    </div>
                                    <div class="progress-label">Preparing tests...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Completion -->
                        <div class="wizard-step" data-step="5" style="display: none;">
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
                        <button class="next-step">Get Started</button>
                        <button class="start-tests" style="display: none;">Run Performance Tests</button>
                        <button class="finish-setup" style="display: none;">Go to Dashboard</button>
                    </div>
                </div>
            </div>
        `;

        // Add styles for the new UI
        const wizardStyles = `
            <style>
                .progress-steps {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 20px 0;
                    padding: 0 20px;
                }

                .step-item {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    flex: 1;
                    position: relative;
                }

                .step-circle {
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    background: #e0e0e0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #666;
                    margin-bottom: 8px;
                    font-weight: bold;
                }

                .step-line {
                    position: absolute;
                    top: 15px;
                    right: -50%;
                    width: 100%;
                    height: 2px;
                    background: #e0e0e0;
                    z-index: -1;
                }

                .step-item:last-child .step-line {
                    display: none;
                }

                .step-item.active .step-circle {
                    background: #2271b1;
                    color: white;
                }

                .step-item.completed .step-circle {
                    background: #00a32a;
                    color: white;
                }

                .step-item.active ~ .step-item .step-line,
                .step-item.completed ~ .step-item .step-line {
                    background: #e0e0e0;
                }

                .step-item.completed .step-line {
                    background: #00a32a;
                }

                .welcome-content {
                    text-align: center;
                    padding: 20px;
                }

                .feature-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    margin: 30px 0;
                }

                .feature-item {
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    text-align: center;
                }

                .feature-icon {
                    font-size: 2em;
                    margin-bottom: 10px;
                    display: block;
                }

                .test-status-container {
                    margin: 20px 0;
                }

                .test-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px;
                    border-bottom: 1px solid #eee;
                }

                .test-status {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 0.9em;
                }

                .test-status.pending {
                    background: #f0f0f1;
                    color: #666;
                }

                .test-status.running {
                    background: #fff4e5;
                    color: #996300;
                }

                .test-status.completed {
                    background: #edf7ed;
                    color: #005200;
                }

                .test-status.failed {
                    background: #fee;
                    color: #c00;
                }

                .overall-progress {
                    margin-top: 20px;
                }

                .start-tests {
                    background: #2271b1;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 4px;
                    border: none;
                    cursor: pointer;
                    font-size: 1.1em;
                }

                .start-tests:hover {
                    background: #135e96;
                }

                .start-tests:disabled {
                    background: #e0e0e0;
                    cursor: not-allowed;
                }
            </style>
        `;

        $('head').append(wizardStyles);
        $('body').append(wizardHtml);

        let currentStep = 1;
        const totalSteps = 5;

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

        $('.start-tests').on('click', function() {
            $(this).prop('disabled', true);
            runAllTests();
        });

        async function runAllTests() {
            const hasUptimeRobotKey = $('#uptimerobot-key').val().trim() !== '';
            const tests = ['latency', 'ssl', 'performance', 'pagespeed'];
            let completedTests = 0;
            let failedTests = [];

            // If UptimeRobot key is provided, add UptimeRobot setup to tests
            if (hasUptimeRobotKey) {
                const $uptimeRobotItem = $(`
                    <div class="test-item" data-test="uptimerobot">
                        <span class="test-name">UptimeRobot Setup</span>
                        <span class="test-status pending">Pending</span>
                    </div>
                `);
                $('.test-status-container').prepend($uptimeRobotItem);
                tests.unshift('uptimerobot');
            }

            $('.progress-label').text('Starting tests...');

            for (const testType of tests) {
                const $testItem = $(`.test-item[data-test="${testType}"]`);
                $testItem.find('.test-status')
                    .removeClass('pending')
                    .addClass('running')
                    .text('Running...');

                try {
                    if (testType === 'uptimerobot') {
                        await setupUptimeRobot($('#uptimerobot-key').val());
                    } else {
                        await runTest(testType);
                    }
                    completedTests++;
                    $testItem.find('.test-status')
                        .removeClass('running')
                        .addClass('completed')
                        .text('Completed');
                } catch (error) {
                    failedTests.push(testType);
                    $testItem.find('.test-status')
                        .removeClass('running')
                        .addClass('failed')
                        .text('Failed');
                }

                // Update overall progress
                const progress = (completedTests / tests.length) * 100;
                $('.overall-progress .progress-fill').css('width', `${progress}%`);
            }

            if (failedTests.length > 0) {
                const failedTestsNames = failedTests.map(t => {
                    const name = t.charAt(0).toUpperCase() + t.slice(1);
                    return t === 'uptimerobot' ? 'UptimeRobot Setup' : name;
                }).join(', ');
                
                $('.progress-label').html(`
                    <div class="test-failed-message" style="color: #c00;">
                        Some tests failed (${failedTestsNames}). You can still proceed, but some features might be limited.
                        <br>You can retry these tests later from the dashboard.
                    </div>
                `);
            } else {
                $('.progress-label').text('All tests completed successfully!');
            }

            // Show next step button after tests are complete
            $('.next-step').prop('disabled', false).show();
            $('.start-tests').hide();
        }
        
        // Add function to setup UptimeRobot
        function setupUptimeRobot(apiKey) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpspeedtestpro_setup_uptimerobot',
                        api_key: apiKey,
                        nonce: wpspeedtestpro_wizard.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data || 'UptimeRobot setup failed'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error(error));
                    }
                });
            });
        }

        
        function runTest(testType) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: `wpspeedtestpro_${testType}_run_test`,
                        nonce: wpspeedtestpro_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data || 'Test failed'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error(error));
                    }
                });
            });
        }

        function updateWizardStep() {
            // Hide all steps
            $('.wizard-step').hide();
            // Show current step
            $(`.wizard-step[data-step="${currentStep}"]`).show();

            // Update progress steps
            $('.step-item').removeClass('active completed');
            for (let i = 1; i <= totalSteps; i++) {
                const $step = $(`.step-item:nth-child(${i})`);
                if (i < currentStep) {
                    $step.addClass('completed');
                } else if (i === currentStep) {
                    $step.addClass('active');
                }
            }

            // Update button visibility based on current step
            $('.prev-step').toggle(currentStep > 1);
            $('.next-step').toggle(currentStep < totalSteps);
            $('.start-tests').toggle(currentStep === 4);
            $('.finish-setup').toggle(currentStep === totalSteps);

            // Hide next button during testing phase
            if (currentStep === 4) {
                $('.next-step').hide();
            }

            // Update button text based on step
            if (currentStep === 1) {
                $('.next-step').text('Get Started');
            } else {
                $('.next-step').text('Next');
            }

            if (currentStep === totalSteps) {
                updateCompletionSummary();
            }
        }

        function validateCurrentStep() {
            switch(currentStep) {
                case 1:
                    return true; // Welcome page, no validation needed
                case 2:
                    const isValid = $('#gcp-region').val() && 
                                  $('#hosting-provider').val() && 
                                  $('#hosting-package').val();
                    
                    if (!isValid) {
                        alert('Please complete all required fields before proceeding.');
                        return false;
                    }
                    return true;
                case 3:
                    return true; // UptimeRobot key is optional
                case 4:
                    // Only allow proceeding if tests are complete or have been attempted
                    return $('.test-status.completed, .test-status.failed').length > 0;
                default:
                    return true;
            }
        }
        
        $('.close-wizard').on('click', function() {
            if (confirm('Are you sure you want to exit the setup wizard? You can always access these settings later.')) {
                $('#wpspeedtestpro-setup-wizard').remove();
            }
        });

        $('.finish-setup').on('click', function() {
            localStorage.setItem('wpspeedtestpro_setup_complete', 'true');
            $('#wpspeedtestpro-setup-wizard').remove();
            window.location.href = 'admin.php?page=wpspeedtestpro';
        });

        $('#hosting-provider').on('change', function() {
            loadHostingPackages($(this).val());
        });

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
                        response.data.forEach(region => {
                            $select.append(`<option value="${region.region}">${region.region_name}</option>`);
                        });
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
                        $select.empty();
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
        
            // Add test results to summary
            $('.test-item').each(function() {
                const testName = $(this).find('.test-name').text();
                const status = $(this).find('.test-status').text();
                summaryItems.push(`${testName}: ${status}`);
            });
        
            summaryItems.forEach(item => {
                $summary.append(`<li>${item}</li>`);
            });
        }
    }
});