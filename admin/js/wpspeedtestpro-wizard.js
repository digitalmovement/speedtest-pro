jQuery(document).ready(function($) {
    // The wizard will now show based on the WordPress option, not localStorage
    initSetupWizard();

    function initSetupWizard() {
        const wizardHtml = `
            <div id="wpspeedtestpro-setup-wizard" class="wpspeedtestpro-wizard-modal">
                <div class="wpspeedtestpro-modal-content">
                    <div class="wizard-header">
                        <h2>Welcome to WP Speedtest Pro Setup Wizard</h2>
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
                                <div class="step-label">PageSpeed</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">4</div>
                                <div class="step-line"></div>
                                <div class="step-label">UptimeRobot</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">5</div>
                                <div class="step-line"></div>
                                <div class="step-label">Testing</div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">6</div>
                                <div class="step-label">Complete</div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-body">
                        <!-- Step 1: Welcome -->
                        <div class="wizard-step" data-step="1">
                            <div class="welcome-content">
                                <h1>Welcome to WP Speedtest Pro! 👋</h1>
                                <p class="welcome-intro">Ready to discover your site's true performance?</p>
                                
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
                                    <p>WP Speedtest Pro helps users choose better hosting with clear, data-driven performance insights. We identify the best hosting providers, call out the worst, and help users get more value from their hosting. Committed to the community, we offer this plguin for free.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Setup -->
                        <div class="wizard-step" data-step="2" style="display: none;">
                            <div class="initial-setup">
                                <h3>Basic Configuration</h3>
                                <p>Let's configure your testing environment to get the most accurate results.</p>
                                  <div class="form-group">
                                <label for="wpspeedtestpro_user_country">Select Your User Base Country</label>
                                                <select id="wpspeedtestpro_user_country" name="wpspeedtestpro_user_country" required>
                                                    <option value="">Select a country</option>
                                                    <option value="AF">Afghanistan</option>
                                                    <option value="AL">Albania</option>
                                                    <option value="DZ">Algeria</option>
                                                    <option value="AS">American Samoa</option>
                                                    <option value="AD">Andorra</option>
                                                    <option value="AO">Angola</option>
                                                    <option value="AI">Anguilla</option>
                                                    <option value="AQ">Antarctica</option>
                                                    <option value="AG">Antigua and Barbuda</option>
                                                    <option value="AR">Argentina</option>
                                                    <option value="AM">Armenia</option>
                                                    <option value="AW">Aruba</option>
                                                    <option value="AU">Australia</option>
                                                    <option value="AT">Austria</option>
                                                    <option value="AZ">Azerbaijan</option>
                                                    <option value="BS">Bahamas</option>
                                                    <option value="BH">Bahrain</option>
                                                    <option value="BD">Bangladesh</option>
                                                    <option value="BB">Barbados</option>
                                                    <option value="BY">Belarus</option>
                                                    <option value="BE">Belgium</option>
                                                    <option value="BZ">Belize</option>
                                                    <option value="BJ">Benin</option>
                                                    <option value="BM">Bermuda</option>
                                                    <option value="BT">Bhutan</option>
                                                    <option value="BO">Bolivia</option>
                                                    <option value="BA">Bosnia and Herzegovina</option>
                                                    <option value="BW">Botswana</option>
                                                    <option value="BV">Bouvet Island</option>
                                                    <option value="BR">Brazil</option>
                                                    <option value="IO">British Indian Ocean Territory</option>
                                                    <option value="BN">Brunei Darussalam</option>
                                                    <option value="BG">Bulgaria</option>
                                                    <option value="BF">Burkina Faso</option>
                                                    <option value="BI">Burundi</option>
                                                    <option value="KH">Cambodia</option>
                                                    <option value="CM">Cameroon</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="CV">Cape Verde</option>
                                                    <option value="KY">Cayman Islands</option>
                                                    <option value="CF">Central African Republic</option>
                                                    <option value="TD">Chad</option>
                                                    <option value="CL">Chile</option>
                                                    <option value="CN">China</option>
                                                    <option value="CX">Christmas Island</option>
                                                    <option value="CC">Cocos (Keeling) Islands</option>
                                                    <option value="CO">Colombia</option>
                                                    <option value="KM">Comoros</option>
                                                    <option value="CG">Congo</option>
                                                    <option value="CD">Congo, Democratic Republic of the</option>
                                                    <option value="CK">Cook Islands</option>
                                                    <option value="CR">Costa Rica</option>
                                                    <option value="CI">Cote D'Ivoire</option>
                                                    <option value="HR">Croatia</option>
                                                    <option value="CU">Cuba</option>
                                                    <option value="CY">Cyprus</option>
                                                    <option value="CZ">Czech Republic</option>
                                                    <option value="DK">Denmark</option>
                                                    <option value="DJ">Djibouti</option>
                                                    <option value="DM">Dominica</option>
                                                    <option value="DO">Dominican Republic</option>
                                                    <option value="EC">Ecuador</option>
                                                    <option value="EG">Egypt</option>
                                                    <option value="SV">El Salvador</option>
                                                    <option value="GQ">Equatorial Guinea</option>
                                                    <option value="ER">Eritrea</option>
                                                    <option value="EE">Estonia</option>
                                                    <option value="ET">Ethiopia</option>
                                                    <option value="FK">Falkland Islands (Malvinas)</option>
                                                    <option value="FO">Faroe Islands</option>
                                                    <option value="FJ">Fiji</option>
                                                    <option value="FI">Finland</option>
                                                    <option value="FR">France</option>
                                                    <option value="GF">French Guiana</option>
                                                    <option value="PF">French Polynesia</option>
                                                    <option value="TF">French Southern Territories</option>
                                                    <option value="GA">Gabon</option>
                                                    <option value="GM">Gambia</option>
                                                    <option value="GE">Georgia</option>
                                                    <option value="DE">Germany</option>
                                                    <option value="GH">Ghana</option>
                                                    <option value="GI">Gibraltar</option>
                                                    <option value="GR">Greece</option>
                                                    <option value="GL">Greenland</option>
                                                    <option value="GD">Grenada</option>
                                                    <option value="GP">Guadeloupe</option>
                                                    <option value="GU">Guam</option>
                                                    <option value="GT">Guatemala</option>
                                                    <option value="GN">Guinea</option>
                                                    <option value="GW">Guinea-Bissau</option>
                                                    <option value="GY">Guyana</option>
                                                    <option value="HT">Haiti</option>
                                                    <option value="HM">Heard Island and McDonald Islands</option>
                                                    <option value="VA">Holy See (Vatican City State)</option>
                                                    <option value="HN">Honduras</option>
                                                    <option value="HK">Hong Kong</option>
                                                    <option value="HU">Hungary</option>
                                                    <option value="IS">Iceland</option>
                                                    <option value="IN">India</option>
                                                    <option value="ID">Indonesia</option>
                                                    <option value="IR">Iran</option>
                                                    <option value="IQ">Iraq</option>
                                                    <option value="IE">Ireland</option>
                                                    <option value="IL">Israel</option>
                                                    <option value="IT">Italy</option>
                                                    <option value="JM">Jamaica</option>
                                                    <option value="JP">Japan</option>
                                                    <option value="JO">Jordan</option>
                                                    <option value="KZ">Kazakhstan</option>
                                                    <option value="KE">Kenya</option>
                                                    <option value="KI">Kiribati</option>
                                                    <option value="KP">Korea, Democratic People's Republic of</option>
                                                    <option value="KR">Korea, Republic of</option>
                                                    <option value="KW">Kuwait</option>
                                                    <option value="KG">Kyrgyzstan</option>
                                                    <option value="LA">Lao People's Democratic Republic</option>
                                                    <option value="LV">Latvia</option>
                                                    <option value="LB">Lebanon</option>
                                                    <option value="LS">Lesotho</option>
                                                    <option value="LR">Liberia</option>
                                                    <option value="LY">Libya</option>
                                                    <option value="LI">Liechtenstein</option>
                                                    <option value="LT">Lithuania</option>
                                                    <option value="LU">Luxembourg</option>
                                                    <option value="MO">Macao</option>
                                                    <option value="MK">North Macedonia</option>
                                                    <option value="MG">Madagascar</option>
                                                    <option value="MW">Malawi</option>
                                                    <option value="MY">Malaysia</option>
                                                    <option value="MV">Maldives</option>
                                                    <option value="ML">Mali</option>
                                                    <option value="MT">Malta</option>
                                                    <option value="MH">Marshall Islands</option>
                                                    <option value="MQ">Martinique</option>
                                                    <option value="MR">Mauritania</option>
                                                    <option value="MU">Mauritius</option>
                                                    <option value="YT">Mayotte</option>
                                                    <option value="MX">Mexico</option>
                                                    <option value="FM">Micronesia, Federated States of</option>
                                                    <option value="MD">Moldova</option>
                                                    <option value="MC">Monaco</option>
                                                    <option value="MN">Mongolia</option>
                                                    <option value="ME">Montenegro</option>
                                                    <option value="MS">Montserrat</option>
                                                    <option value="MA">Morocco</option>
                                                    <option value="MZ">Mozambique</option>
                                                    <option value="MM">Myanmar</option>
                                                    <option value="NA">Namibia</option>
                                                    <option value="NR">Nauru</option>
                                                    <option value="NP">Nepal</option>
                                                    <option value="NL">Netherlands</option>
                                                    <option value="NC">New Caledonia</option>
                                                    <option value="NZ">New Zealand</option>
                                                    <option value="NI">Nicaragua</option>
                                                    <option value="NE">Niger</option>
                                                    <option value="NG">Nigeria</option>
                                                    <option value="NU">Niue</option>
                                                    <option value="NF">Norfolk Island</option>
                                                    <option value="MP">Northern Mariana Islands</option>
                                                    <option value="NO">Norway</option>
                                                    <option value="OM">Oman</option>
                                                    <option value="PK">Pakistan</option>
                                                    <option value="PW">Palau</option>
                                                    <option value="PS">Palestine</option>
                                                    <option value="PA">Panama</option>
                                                    <option value="PG">Papua New Guinea</option>
                                                    <option value="PY">Paraguay</option>
                                                    <option value="PE">Peru</option>
                                                    <option value="PH">Philippines</option>
                                                    <option value="PN">Pitcairn</option>
                                                    <option value="PL">Poland</option>
                                                    <option value="PT">Portugal</option>
                                                    <option value="PR">Puerto Rico</option>
                                                    <option value="QA">Qatar</option>
                                                    <option value="RE">Reunion</option>
                                                    <option value="RO">Romania</option>
                                                    <option value="RU">Russian Federation</option>
                                                    <option value="RW">Rwanda</option>
                                                    <option value="SH">Saint Helena</option>
                                                    <option value="KN">Saint Kitts and Nevis</option>
                                                    <option value="LC">Saint Lucia</option>
                                                    <option value="PM">Saint Pierre and Miquelon</option>
                                                    <option value="VC">Saint Vincent and the Grenadines</option>
                                                    <option value="WS">Samoa</option>
                                                    <option value="SM">San Marino</option>
                                                    <option value="ST">Sao Tome and Principe</option>
                                                    <option value="SA">Saudi Arabia</option>
                                                    <option value="SN">Senegal</option>
                                                    <option value="RS">Serbia</option>
                                                    <option value="SC">Seychelles</option>
                                                    <option value="SL">Sierra Leone</option>
                                                    <option value="SG">Singapore</option>
                                                    <option value="SK">Slovakia</option>
                                                    <option value="SI">Slovenia</option>
                                                    <option value="SB">Solomon Islands</option>
                                                    <option value="SO">Somalia</option>
                                                    <option value="ZA">South Africa</option>
                                                    <option value="GS">South Georgia and the South Sandwich Islands</option>
                                                    <option value="SS">South Sudan</option>
                                                    <option value="ES">Spain</option>
                                                    <option value="LK">Sri Lanka</option>
                                                    <option value="SD">Sudan</option>
                                                    <option value="SR">Suriname</option>
                                                    <option value="SJ">Svalbard and Jan Mayen</option>
                                                    <option value="SZ">Eswatini</option>
                                                    <option value="SE">Sweden</option>
                                                    <option value="CH">Switzerland</option>
                                                    <option value="SY">Syrian Arab Republic</option>
                                                    <option value="TW">Taiwan</option>
                                                    <option value="TJ">Tajikistan</option>
                                                    <option value="TZ">Tanzania</option>
                                                    <option value="TH">Thailand</option>
                                                    <option value="TL">Timor-Leste</option>
                                                    <option value="TG">Togo</option>
                                                    <option value="TK">Tokelau</option>
                                                    <option value="TO">Tonga</option>
                                                    <option value="TT">Trinidad and Tobago</option>
                                                    <option value="TN">Tunisia</option>
                                                    <option value="TR">Türkiye</option>
                                                    <option value="TM">Turkmenistan</option>
                                                    <option value="TC">Turks and Caicos Islands</option>
                                                    <option value="TV">Tuvalu</option>
                                                    <option value="UG">Uganda</option>
                                                    <option value="UA">Ukraine</option>
                                                    <option value="AE">United Arab Emirates</option>
                                                    <option value="GB">United Kingdom</option>
                                                    <option value="US">United States</option>
                                                    <option value="UM">United States Minor Outlying Islands</option>
                                                    <option value="UY">Uruguay</option>
                                                    <option value="UZ">Uzbekistan</option>
                                                    <option value="VU">Vanuatu</option>
                                                    <option value="VE">Venezuela</option>
                                                    <option value="VN">Vietnam</option>
                                                    <option value="VG">Virgin Islands, British</option>
                                                    <option value="VI">Virgin Islands, U.S.</option>
                                                    <option value="WF">Wallis and Futuna</option>
                                                    <option value="EH">Western Sahara</option>
                                                    <option value="YE">Yemen</option>
                                                    <option value="ZM">Zambia</option>
                                                    <option value="ZW">Zimbabwe</option>
                                                </select>
                                                <p class="help-text">Select the primary country where most of your users are located</p>
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
                                        Help improve WP Speedtest Pro by allowing anonymous data collection
                                    </label>
                                    <p class="privacy-note">Your data helps us identify trends and improve hosting recommendations for the community. You can stop sharing at any time in Settings.
                                   For more information you can view our full <a target="_new" href="https://wpspeedtestpro.com/privacy">privacy policy</a></p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Google PageSpeed API -->
                        <div class="wizard-step" data-step="3" style="display: none;">
                            <h2>Google PageSpeed API Setup</h2>
                            <p>To run PageSpeed tests, you'll need a Google API key with the PageSpeed Insights API enabled.</p>
                            
                            <div class="form-group">
                                <label for="pagespeed-api-key">Google API Key</label>
                                <input type="text" id="pagespeed-api-key" name="pagespeed_api_key" placeholder="Enter your Google API key">
                                <p class="description">
                                    <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">
                                        Learn how to create a Google API key
                                    </a>
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <p class="description">You can skip this step if you don't want to run PageSpeed tests.</p>
                            </div>
                        
                        </div>

                        <div class="wizard-step" data-step="4" style="display: none;">
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

                        <!-- Step 5: Testing -->
                        <div class="wizard-step" data-step="5" style="display: none;">
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
                                </div>

                                <div class="overall-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill"></div>
                                    </div>
                                    <div class="progress-label">Click on the test button when you're ready...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: Completion -->
                        <div class="wizard-step" data-step="6" style="display: none;">
                            <h3>Setup Complete!</h3>
                            <p>You're all set to start monitoring your site's performance.</p>
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
        const totalSteps = 6;

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
            const hasPageSpeedKey = $('#pagespeed-api-key').val().trim() !== '';
            const tests = ['latency', 'performance'];
            
            // Only add PageSpeed test if API key is provided
            if (hasPageSpeedKey) {
                tests.push('pagespeed');
            }
            
            let completedTests = 0;
            let failedTests = [];
            
            if (hasUptimeRobotKey) {
                tests.unshift('uptimerobot');
            }
        
            $('.progress-label').text('Starting tests...');
        
            // Start SSL test separately and update UI
            const $sslTestItem = $('.test-item[data-test="ssl"]');
            const $sslStatus = $sslTestItem.find('.test-status');
            const $sslProgressBar = $sslTestItem.find('.test-progress-bar');

            $sslStatus.removeClass('pending').addClass('running').text('Started (continues in background)');
            
            // Start SSL test without waiting
            runTest('ssl').then(() => {
                $sslStatus.removeClass('running').addClass('completed').text('Started - Check dashboard for results');
                $sslProgressBar.hide(); // Hide progress bar after test starts
            }).catch((error) => {
                $sslStatus.removeClass('running').addClass('failed').text('Failed to start test');
                $sslProgressBar.hide(); // Hide progress bar on failure
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
                        ajaxData.api_key = $('#pagespeed-api-key').val(); // Add API key to request
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
            $('.start-tests').toggle(currentStep === 5);
            $('.finish-setup').toggle(currentStep === totalSteps);

            // Hide next button during testing phase
            if (currentStep === 5) {
                $('.next-step').hide();
                const hasUptimeRobotKey = $('#uptimerobot-key').val().trim() !== '';
                const hasPageSpeedKey = $('#pagespeed-api-key').val().trim() !== '';
                const tests = ['latency', 'ssl', 'performance'];
    
                if (hasPageSpeedKey) {
                    const existingPageSpeed = $('.test-item[data-test="pagespeed"]');
                    if (existingPageSpeed.length === 0) {
                        const $pageSpeedItem = $(`
                            <div class="test-item" data-test="pagespeed">
                                <div class="test-info">
                                    <span class="test-name">PageSpeed Test</span>
                                    <span class="test-status pending">Pending</span>
                                </div>
                                <div class="test-progress-bar" style="display: none;">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                        `);
                        $('.test-status-container').prepend($pageSpeedItem);
                        tests.unshift('pagespeed');
                    }
                }

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
                    const isValid =  $('#wpspeedtestpro_user_country').val()  && 
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
                    saveWizardSettings();
                    return true;
                             
                case 4:
                    // Save settings even if Google API key is skipped
                    saveWizardSettings();
                    return true;
                    
                case 5:
                    return $('.test-status.completed, .test-status.failed').length > 0;
                default:
                    return true;
            }
        }

        function saveWizardSettings() {

            const $countrySelect = $('#wpspeedtestpro_user_country');
            const countryCode = $countrySelect.val();
            const gcpRegion = $countrySelect.data('selected-gcp-region') || getGCPRegionForCountry(countryCode);
        
            
            return $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_save_wizard_settings',
                    nonce: wpspeedtestpro_ajax.nonce,
                    user_country: countryCode,
                    region: gcpRegion,
                    provider_id: $('#hosting-provider').val(),
                    package_id: $('#hosting-package').val(),
                    allow_data_collection: $('#allow-data-collection').is(':checked'),
                    uptimerobot_key: $('#uptimerobot-key').val(),
                    pagespeed_api_key: $('#pagespeed-api-key').val()
                }
            });
        }
        
        function getGCPRegionForCountry(countryCode) {
            const countryToRegionMapping = {
                // North America
                'US': 'us-central1', // United States
                'CA': 'us-central1', // Canada
                'MX': 'us-central1', // Mexico
                'BZ': 'us-central1', // Belize
                'CR': 'us-central1', // Costa Rica
                'SV': 'us-central1', // El Salvador
                'GT': 'us-central1', // Guatemala
                'HN': 'us-central1', // Honduras
                'NI': 'us-central1', // Nicaragua
                'PA': 'us-central1', // Panama
            
                // Caribbean (closest to US East)
                'AI': 'us-east1', // Anguilla
                'AG': 'us-east1', // Antigua and Barbuda
                'AW': 'us-east1', // Aruba
                'BS': 'us-east1', // Bahamas
                'BB': 'us-east1', // Barbados
                'VG': 'us-east1', // British Virgin Islands
                'KY': 'us-east1', // Cayman Islands
                'CU': 'us-east1', // Cuba
                'CW': 'us-east1', // Curaçao
                'DM': 'us-east1', // Dominica
                'DO': 'us-east1', // Dominican Republic
                'GD': 'us-east1', // Grenada
                'GP': 'us-east1', // Guadeloupe
                'HT': 'us-east1', // Haiti
                'JM': 'us-east1', // Jamaica
                'MQ': 'us-east1', // Martinique
                'MS': 'us-east1', // Montserrat
                'PR': 'us-east1', // Puerto Rico
                'BL': 'us-east1', // Saint Barthélemy
                'KN': 'us-east1', // Saint Kitts and Nevis
                'LC': 'us-east1', // Saint Lucia
                'MF': 'us-east1', // Saint Martin
                'VC': 'us-east1', // Saint Vincent and the Grenadines
                'TT': 'us-east1', // Trinidad and Tobago
                'TC': 'us-east1', // Turks and Caicos Islands
                'VI': 'us-east1', // U.S. Virgin Islands
            
                // South America
                'AR': 'southamerica-east1', // Argentina
                'BO': 'southamerica-east1', // Bolivia
                'BR': 'southamerica-east1', // Brazil
                'CL': 'southamerica-west1', // Chile
                'CO': 'southamerica-west1', // Colombia
                'EC': 'southamerica-west1', // Ecuador
                'FK': 'southamerica-east1', // Falkland Islands
                'GF': 'southamerica-east1', // French Guiana
                'GY': 'southamerica-east1', // Guyana
                'PY': 'southamerica-east1', // Paraguay
                'PE': 'southamerica-west1', // Peru
                'SR': 'southamerica-east1', // Suriname
                'UY': 'southamerica-east1', // Uruguay
                'VE': 'southamerica-east1', // Venezuela
            
                // Western Europe
                'GB': 'europe-west2', // United Kingdom (London)
                'IE': 'europe-west2', // Ireland
                'PT': 'europe-west1', // Portugal
                'ES': 'europe-southwest1', // Spain
                'FR': 'europe-west9', // France
                'BE': 'europe-west1', // Belgium
                'NL': 'europe-west4', // Netherlands
                'LU': 'europe-west1', // Luxembourg
                'DE': 'europe-west3', // Germany
                'AT': 'europe-west3', // Austria
                'CH': 'europe-west6', // Switzerland
                'IT': 'europe-west8', // Italy
                'VA': 'europe-west8', // Vatican City
                'SM': 'europe-west8', // San Marino
                'MT': 'europe-west8', // Malta
            
                // Northern Europe
                'DK': 'europe-north1', // Denmark
                'FO': 'europe-north1', // Faroe Islands
                'FI': 'europe-north1', // Finland
                'IS': 'europe-north1', // Iceland
                'NO': 'europe-north1', // Norway
                'SE': 'europe-north1', // Sweden
                'EE': 'europe-north1', // Estonia
                'LV': 'europe-north1', // Latvia
                'LT': 'europe-north1', // Lithuania
            
                // Central and Eastern Europe
                'PL': 'europe-central2', // Poland
                'CZ': 'europe-central2', // Czech Republic
                'SK': 'europe-central2', // Slovakia
                'HU': 'europe-central2', // Hungary
                'RO': 'europe-central2', // Romania
                'BG': 'europe-central2', // Bulgaria
                'SI': 'europe-west3', // Slovenia
                'HR': 'europe-central2', // Croatia
                'BA': 'europe-central2', // Bosnia and Herzegovina
                'RS': 'europe-central2', // Serbia
                'ME': 'europe-central2', // Montenegro
                'AL': 'europe-central2', // Albania
                'MK': 'europe-central2', // North Macedonia
                'GR': 'europe-central2', // Greece
                'CY': 'europe-central2', // Cyprus
            
                // Eastern Europe and Northern Asia
                'BY': 'europe-central2', // Belarus
                'UA': 'europe-central2', // Ukraine
                'MD': 'europe-central2', // Moldova
                'RU': 'europe-north1', // Russia (varies by region, defaulting to closest)
            
                // Middle East
                'TR': 'me-central1', // Turkey
                'GE': 'me-central1', // Georgia
                'AM': 'me-central1', // Armenia
                'AZ': 'me-central1', // Azerbaijan
                'IQ': 'me-central1', // Iraq
                'IL': 'me-central1', // Israel
                'PS': 'me-central1', // Palestine
                'JO': 'me-central1', // Jordan
                'LB': 'me-central1', // Lebanon
                'SY': 'me-central1', // Syria
                'SA': 'me-central1', // Saudi Arabia
                'YE': 'me-central1', // Yemen
                'OM': 'me-central1', // Oman
                'AE': 'me-central1', // United Arab Emirates
                'QA': 'me-central1', // Qatar
                'BH': 'me-central1', // Bahrain
                'KW': 'me-central1', // Kuwait
                'IR': 'me-central1', // Iran
            
                // Central and South Asia
                'KZ': 'asia-south1', // Kazakhstan
                'KG': 'asia-south1', // Kyrgyzstan
                'TJ': 'asia-south1', // Tajikistan
                'TM': 'asia-south1', // Turkmenistan
                'UZ': 'asia-south1', // Uzbekistan
                'AF': 'asia-south1', // Afghanistan
                'PK': 'asia-south1', // Pakistan
                'IN': 'asia-south1', // India
                'NP': 'asia-south1', // Nepal
                'BT': 'asia-south1', // Bhutan
                'BD': 'asia-south1', // Bangladesh
                'LK': 'asia-south1', // Sri Lanka
                'MV': 'asia-south1', // Maldives
            
                // East Asia
                'MN': 'asia-northeast1', // Mongolia
                'CN': 'asia-east2', // China
                'HK': 'asia-east2', // Hong Kong
                'TW': 'asia-east1', // Taiwan
                'JP': 'asia-northeast1', // Japan
                'KP': 'asia-northeast3', // North Korea
                'KR': 'asia-northeast3', // South Korea
            
                // Southeast Asia
                'BN': 'asia-southeast1', // Brunei
                'KH': 'asia-southeast1', // Cambodia
                'ID': 'asia-southeast2', // Indonesia
                'LA': 'asia-southeast1', // Laos
                'MY': 'asia-southeast1', // Malaysia
                'MM': 'asia-southeast1', // Myanmar
                'PH': 'asia-southeast1', // Philippines
                'SG': 'asia-southeast1', // Singapore
                'TH': 'asia-southeast1', // Thailand
                'TL': 'asia-southeast1', // Timor-Leste
                'VN': 'asia-southeast2', // Vietnam
            
                // Oceania
                'AU': 'australia-southeast1', // Australia
                'NZ': 'australia-southeast1', // New Zealand
                'PG': 'australia-southeast1', // Papua New Guinea
                'SB': 'australia-southeast1', // Solomon Islands
                'VU': 'australia-southeast1', // Vanuatu
                'NC': 'australia-southeast1', // New Caledonia
                'FJ': 'australia-southeast1', // Fiji
                'TO': 'australia-southeast1', // Tonga
                'WS': 'australia-southeast1', // Samoa
                'PF': 'australia-southeast1', // French Polynesia
            
                // Africa - Northern
                'DZ': 'europe-southwest1', // Algeria
                'EG': 'me-central1', // Egypt
                'LY': 'europe-southwest1', // Libya
                'MA': 'europe-southwest1', // Morocco
                'SD': 'me-central1', // Sudan
                'TN': 'europe-southwest1', // Tunisia
                'EH': 'europe-southwest1', // Western Sahara
            
                // Africa - Western
                'BJ': 'europe-west1', // Benin
                'BF': 'europe-west1', // Burkina Faso
                'CV': 'europe-west1', // Cape Verde
                'CI': 'europe-west1', // Côte d'Ivoire
                'GM': 'europe-west1', // Gambia
                'GH': 'europe-west1', // Ghana
                'GN': 'europe-west1', // Guinea
                'GW': 'europe-west1', // Guinea-Bissau
                'LR': 'europe-west1', // Liberia
                'ML': 'europe-west1', // Mali
                'MR': 'europe-west1', // Mauritania
                'NE': 'europe-west1', // Niger
                'NG': 'europe-west1', // Nigeria
                'SH': 'europe-west1', // Saint Helena
                'SN': 'europe-west1', // Senegal
                'SL': 'europe-west1', // Sierra Leone
                'TG': 'europe-west1', // Togo
            
                // Africa - Central
                'AO': 'europe-west1', // Angola
                'CM': 'europe-west1', // Cameroon
                'CF': 'europe-west1', // Central African Republic
                'TD': 'europe-west1', // Chad
                'CG': 'europe-west1', // Republic of the Congo
                'CD': 'europe-west1', // DR Congo
                'GQ': 'europe-west1', // Equatorial Guinea
                'GA': 'europe-west1', // Gabon
                'ST': 'europe-west1', // São Tomé and Príncipe
            
                // Africa - Eastern
                'BI': 'me-central1', // Burundi
                'KM': 'me-central1', // Comoros
                'DJ': 'me-central1', // Djibouti
                'ER': 'me-central1', // Eritrea
                'ET': 'me-central1', // Ethiopia
                'KE': 'me-central1', // Kenya
                'MG': 'me-central1', // Madagascar
                'MW': 'me-central1', // Malawi
                'MU': 'me-central1', // Mauritius
                'YT': 'me-central1', // Mayotte
                'MZ': 'me-central1', // Mozambique
                'RE': 'me-central1', // Réunion
                'RW': 'me-central1', // Rwanda
                'SC': 'me-central1', // Seychelles
                'SO': 'me-central1', // Somalia
                'SS': 'me-central1', // South Sudan
                'TZ': 'me-central1', // Tanzania
                'UG': 'me-central1', // Uganda
                'ZM': 'me-central1', // Zambia
                'ZW': 'me-central1', // Zimbabwe
            
                // Africa - Southern
                'BW': 'europe-west1', // Botswana
                'LS': 'europe-west1', // Lesotho
                'NA': 'europe-west1', // Namibia
                'ZA': 'europe-west1', // South Africa
                'SZ': 'europe-west1', // Eswatini (Swaziland)
            
                // Default fallback
                'DEFAULT': 'us-central1'
            };

            return countryToRegionMapping[countryCode] || countryToRegionMapping['DEFAULT'];
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

        $(document).on('change', '#wpspeedtestpro_user_country', function() {
            const selectedCountry = $(this).val();
            if (selectedCountry) {
                const gcpRegion = getGCPRegionForCountry(selectedCountry);
                // Store the selected GCP region to be used when saving
                $(this).data('selected-gcp-region', gcpRegion);
            }
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
                 `UptimeRobot Integration: ${$('#uptimerobot-key').val() ? 'Configured' : 'Skipped'}`,
                `PageSpeed API Key: ${$('#pagespeed-api-key').val() ? 'Configured' : 'Skipped'}`
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

        // Function to dismiss the wizard (when X is clicked)
        function dismissWizard() {
            // Send AJAX request to mark wizard as dismissed
            $.ajax({
                url: wpspeedtestpro_wizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpspeedtestpro_dismiss_wizard',
                    nonce: wpspeedtestpro_wizard.nonce
                },
                success: function(response) {
                    $('#wpspeedtestpro-setup-wizard').remove();
                },
                error: function(xhr, status, error) {
                    console.error('Error dismissing wizard:', error);
                }
            });
        }
        
        // Function to complete the wizard (when finish button is clicked)
        function completeWizard() {

            // The save_wizard_settings AJAX call already marks the wizard as completed
            $('#wpspeedtestpro-setup-wizard').remove();
            window.location.href = wpspeedtestpro_wizard.dashboard_url;
        }
    }
});