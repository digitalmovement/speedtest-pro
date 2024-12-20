jQuery(document).ready(function($) {
    if (!localStorage.getItem('wpspeedtestpro_setup_complete')) {
        initSetupWizard();
    }

    function initSetupWizard() {
        const wizardHtml = `
            <div id="wpspeedtestpro-setup-wizard" class="wpspeedtestpro-wizard-modal">
                <div class="wpspeedtestpro-modal-content">
                    <div class="wizard-header">
                        <h2>Welcome to WP SpeedTest Pro Setup Wizard</h2>
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
                                <h1>Welcome to WP SpeedTest Pro! 👋</h1>
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
                                    <p>WP SpeedTest Pro helps WordPress users choose better hosting with clear, data-driven performance insights. We identify the best hosting providers, call out the worst, and help users get more value from their hosting. Committed to the WordPress community, we offer this plguin for free.</p>
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
                                    <p class="privacy-note">Your data helps us identify trends and improve hosting recommendations for the WordPress community. You can stop sharing at any time in Settings.
                                   For more information you can view our full <a target="_new" href="https://wpspeedtestpro.com/privacy">privacy policy</a></p>
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
                                    <a href="https://uptimerobot.com/signUp" target="_blank">Sign up for free</a> - When creating an API key select "Main API key"
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
                                        <div class="test-info">
                                            <span class="test-name">Latency Test</span>
                                            <span class="test-status pending">Pending</span>
                                        </div>
                                        <div class="test-progress-bar" style="display: none;">
                                            <div class="progress-fill"></div>
                                        </div>
                                    </div>
                                    <div class="test-item" data-test="ssl">
                                        <div class="test-info">
                                            <span class="test-name">SSL Security Test</span>
                                            <span class="test-status pending">Pending</span>
                                        </div>
                                        <div class="test-progress-bar" style="display: none;">
                                            <div class="progress-fill"></div>
                                        </div>
                                    </div>
                                    <div class="test-item" data-test="performance">
                                        <div class="test-info">
                                            <span class="test-name">Performance Test</span>
                                            <span class="test-status pending">Pending</span>
                                        </div>
                                        <div class="test-progress-bar" style="display: none;">
                                            <div class="progress-fill"></div>
                                        </div>
                                    </div>
                                    <div class="test-item" data-test="pagespeed">
                                        <div class="test-info">
                                            <span class="test-name">PageSpeed Analysis</span>
                                            <span class="test-status pending">Pending</span>
                                        </div>
                                        <div class="test-progress-bar" style="display: none;">
                                            <div class="progress-fill"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="overall-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill"></div>
                                    </div>
                                    <div class="progress-label">Click on the test button when you're ready...</div>
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
        margin-bottom: 15px;
    }

    .test-info {
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

    .test-progress-bar {
        height: 4px;
        background: #e2e4e7;
        border-radius: 2px;
        overflow: hidden;
        margin-top: 5px;
    }

    .test-progress-bar .progress-fill {
        height: 100%;
        background: #2271b1;
        width: 0;
        transition: width 0.3s linear;
        animation: progress-animation 2s linear infinite;
    }

    @keyframes progress-animation {
        0% {
            width: 0%;
            opacity: 1;
        }
        50% {
            width: 100%;
            opacity: 0.5;
        }
        100% {
            width: 0%;
            opacity: 1;
        }
    }

    .overall-progress {
        margin-top: 20px;
    }

    .overall-progress .progress-bar {
        width: 100%;
        height: 8px;
        background: #e2e4e7;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .overall-progress .progress-fill {
        height: 100%;
        background: #2271b1;
        width: 0;
        transition: width 0.3s ease-in-out;
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

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-group select,
    .form-group input[type="text"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .help-text {
        color: #666;
        font-size: 0.9em;
        margin-top: 4px;
    }

    .privacy-opt {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-top: 30px;
    }

    .privacy-note {
        font-size: 0.9em;
        color: #666;
        margin-top: 8px;
    }

    /* Mission Statement */
    .mission-statement {
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .mission-statement h3 {
        margin-bottom: 10px;
        color: #1d2327;
    }

    /* Wizard Modal */
    .wpspeedtestpro-wizard-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }

    .wpspeedtestpro-modal-content {
        background: white;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        border-radius: 8px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
    }

    .wizard-header {
        padding: 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .wizard-header h2 {
        margin: 0;
    }

    .close-wizard {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .wizard-body {
        padding: 20px;
    }

    .wizard-footer {
        padding: 20px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
    }

    .wizard-footer button {
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }

    .prev-step {
        background: #f0f0f1;
        border: 1px solid #ddd;
        color: #1d2327;
    }

    .next-step,
    .finish-setup {
        background: #2271b1;
        border: none;
        color: white;
    }

    .next-step:hover,
    .finish-setup:hover {
        background: #135e96;
    }

    /* Completion Summary */
    .completion-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 4px;
        margin-top: 20px;
    }

    .setup-summary {
        list-style: none;
        padding: 0;
        margin: 10px 0 0 0;
    }

    .setup-summary li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .setup-summary li:last-child {
        border-bottom: none;
    }

    /* Skip Note */
    .skip-note {
        color: #666;
        font-style: italic;
        margin-top: 10px;
    }

    /* Test Failed Message */
    .test-failed-message {
        background: #fef7f7;
        border-left: 4px solid #c00;
        padding: 10px;
        margin-top: 10px;
        border-radius: 2px;
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
            const tests = ['latency', 'performance', 'pagespeed'];
            let completedTests = 0;
            let failedTests = [];
        
            if (hasUptimeRobotKey) {
                tests.unshift('uptimerobot');
            }
        
            $('.progress-label').text('Starting tests...');
        
            // Start SSL test separately and update UI
            const $sslTestItem = $('.test-item[data-test="ssl"]');
            const $sslStatus = $sslTestItem.find('.test-status');
            $sslStatus.removeClass('pending').addClass('running').text('Started (continues in background)');
            
            // Start SSL test without waiting
            runTest('ssl').then(() => {
                $sslStatus.removeClass('running').addClass('completed').text('Started - Check dashboard for results');
            }).catch((error) => {
                $sslStatus.removeClass('running').addClass('failed').text('Failed to start test');
                console.error('SSL test failed to start:', error);
            });
        
            // Run other tests sequentially
            for (const testType of tests) {
                const $testItem = $(`.test-item[data-test="${testType}"]`);
                const $status = $testItem.find('.test-status');
                const $progressBar = $testItem.find('.test-progress-bar');
                
                $status.removeClass('pending').addClass('running').text('Running...');
                $progressBar.show();
        
                try {
                    if (testType === 'uptimerobot') {
                        await setupUptimeRobot($('#uptimerobot-key').val());
                    } else {
                        await runTest(testType);
                        
                        if (testType === 'pagespeed') {
                            await checkTestStatus(testType);
                        }
                    }
                    
                    completedTests++;
                    $status.removeClass('running').addClass('completed').text('Completed');
                    $progressBar.hide();
                } catch (error) {
                    failedTests.push(testType);
                    $status.removeClass('running').addClass('failed').text('Failed');
                    $progressBar.hide();
                    console.error(`Test ${testType} failed:`, error);
                }
        
                // Calculate progress excluding SSL test from total
                const progress = (completedTests / tests.length) * 100;
                $('.overall-progress .progress-fill').css('width', `${progress}%`);
                $('.progress-label').text(`${completedTests} of ${tests.length} tests completed`);
            }
        
            // Final status update
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
                $('.progress-label').html(`
                    All tests completed successfully!<br>
                    <span style="font-size: 0.9em; color: #666;">
                        Note: SSL test results will be available in the dashboard when completed (typically 4-5 minutes)
                    </span>
                `);
            }
        
            // Show next step button after tests are complete
            $('.next-step').prop('disabled', false).show();
            $('.start-tests').hide();
        } 
        
        // Function to check test status for SSL and PageSpeed tests
        function checkTestStatus(testType) {
            return new Promise((resolve, reject) => {
                const checkStatus = () => {
                    const action = testType === 'ssl' ? 'check_ssl_test_status' : 'pagespeed_check_test_status';
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: action,
                            nonce: wpspeedtestpro_ajax.nonce,
                            url: window.location.origin // Only needed for PageSpeed
                        },
                        success: function(response) {
                            if (response.success) {
                                if (response.data.status === 'complete' || response.data.status === 'completed') {
                                    resolve(response);
                                } else if (response.data.status === 'running' || response.data.status === 'in_progress') {
                                    setTimeout(checkStatus, 5000); // Check again in 5 seconds
                                } else {
                                    reject(new Error(`Unexpected status: ${response.data.status}`));
                                }
                            } else {
                                reject(new Error(response.data || 'Status check failed'));
                            }
                        },
                        error: function(xhr, status, error) {
                            reject(new Error(error));
                        }
                    });
                };
        
                checkStatus();
            });
        }
        // Add function to setup UptimeRobot
        function setupUptimeRobot(apiKey) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpspeedtestpro_uptimerobot_setup_monitors',
                        api_key: apiKey,
                        nonce: wpspeedtestpro_ajax.nonce
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
                const $testItem = $(`.test-item[data-test="${testType}"]`);
                const $progressBar = $testItem.find('.test-progress-bar');
                
                // Show progress bar
                $progressBar.show();


                let ajaxData = {
                    nonce: wpspeedtestpro_ajax.nonce
                };
        
                // Configure test-specific parameters
                switch(testType) {
                    case 'performance':
                        ajaxData.action = 'wpspeedtestpro_performance_run_test';
             
                        break;
                        
                    case 'latency':
                        ajaxData.action = 'wpspeedtestpro_run_once_test';
                        break;
                        
                    case 'ssl':
                        ajaxData.action = 'start_ssl_test';
                        break;
                        
                    case 'pagespeed':
                        ajaxData.action = 'pagespeed_run_test';
                        ajaxData.url = window.location.origin; // Homepage URL
                        ajaxData.device = 'both';
                        ajaxData.frequency = 'once';
                        break;
                        
                    default:
                        reject(new Error('Unknown test type'));
                        return;
                }
        
                $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: ajaxData,
                        success: function(response) {
                            if (response.success) {
                                if (['ssl', 'pagespeed'].includes(testType)) {
                                    // Keep progress bar for tests that need status checking
                                
                                } else {
                                    // Hide progress bar for completed tests
                                    $progressBar.hide();
                                }
                                resolve(response);
                            } else {
                                $progressBar.hide();
                                reject(new Error(response.data || 'Test failed'));
                            }
                        },
                        error: function(xhr, status, error) {
                            $progressBar.hide();
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
                const hasUptimeRobotKey = $('#uptimerobot-key').val().trim() !== '';
                const tests = ['latency', 'ssl', 'performance', 'pagespeed'];
    
                if (hasUptimeRobotKey) {
                    // Check if UptimeRobot test item already exists
                    const existingUptimeRobot = $('.test-item[data-test="uptimerobot"]');
                    if (existingUptimeRobot.length === 0) {
                        const $uptimeRobotItem = $(`
                            <div class="test-item" data-test="uptimerobot">
                                <div class="test-info">
                                    <span class="test-name">UptimeRobot Setup</span>
                                    <span class="test-status pending">Pending</span>
                                </div>
                                <div class="test-progress-bar" style="display: none;">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                        `);
                        $('.test-status-container').prepend($uptimeRobotItem);
                        tests.unshift('uptimerobot');
                    }
                }

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
                    
                    // Save settings after validation
                    saveWizardSettings();
                    return true;
                    
                case 3:
                    // Save settings even if UptimeRobot is skipped
                    saveWizardSettings();
                    return true;
                    
                case 4:
                    return $('.test-status.completed, .test-status.failed').length > 0;
                default:
                    return true;
            }
        }

        function saveWizardSettings() {
            return $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_save_wizard_settings',
                    nonce: wpspeedtestpro_ajax.nonce,
                    region: $('#gcp-region').val(),
                    provider_id: $('#hosting-provider').val(),
                    package_id: $('#hosting-package').val(),
                    allow_data_collection: $('#allow-data-collection').is(':checked'),
                    uptimerobot_key: $('#uptimerobot-key').val()
                }
            });
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
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#gcp-region');
                        $select.empty();
                        // Add default option
                        $select.append('<option value="">Select a region</option>');
                        response.data.forEach(region => {
                            $select.append(`<option value="${region.region_name}">${region.region_name}</option>`);
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
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#hosting-provider');
                        $select.empty();
                        // Add default option
                        $select.append('<option value="">Select your hosting provider</option>');
                        response.data.forEach(provider => {
                            $select.append(`<option value="${provider.id}">${provider.name}</option>`);
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
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#hosting-package');
                        $select.empty();
                        $select.append('<option value="">Select your package</option>');
                        response.data.forEach(package => {
                            // Use Package_ID as value and show type + description as text
                            $select.append(`<option value="${package.Package_ID}">${package.type} - ${package.description}</option>`);
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