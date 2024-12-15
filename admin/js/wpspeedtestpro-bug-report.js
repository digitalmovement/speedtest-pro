(function($) {
    'use strict';

    class WPSpeedTestProBugReport {
        constructor() {
            this.modalHtml = `
                <div id="wpspeedtestpro-bug-report" class="wpspeedtestpro-modal">
                    <div class="wpspeedtestpro-modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-bug"></i> Report a Bug</h2>
                            <button class="close-modal">&times;</button>
                        </div>
                        
                        <div class="modal-body">
                            <form id="bug-report-form">
                                <div class="form-group">
                                    <label for="bug-email">Contact Email *</label>
                                    <input type="email" id="bug-email" name="email" required>
                                </div>

                                <div class="form-group">
                                    <label for="bug-message">Issue Description *</label>
                                    <textarea id="bug-message" name="message" required></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="bug-priority">Priority *</label>
                                        <select id="bug-priority" name="priority" required>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-6">
                                        <label for="bug-severity">Severity *</label>
                                        <select id="bug-severity" name="severity" required>
                                            <option value="minor">Minor</option>
                                            <option value="major">Major</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bug-steps">Steps to Reproduce</label>
                                    <textarea id="bug-steps" name="stepsToReproduce"></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="bug-expected">Expected Behavior</label>
                                        <textarea id="bug-expected" name="expectedBehavior"></textarea>
                                    </div>
                                    <div class="form-group col-6">
                                        <label for="bug-actual">Actual Behavior</label>
                                        <textarea id="bug-actual" name="actualBehavior"></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bug-frequency">Frequency</label>
                                    <input type="text" id="bug-frequency" name="frequency" placeholder="e.g., Always, Sometimes, Random">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-4">
                                        <label for="bug-os">Operating System</label>
                                        <input type="text" id="bug-os" name="os">
                                    </div>
                                    <div class="form-group col-4">
                                        <label for="bug-browser">Browser</label>
                                        <input type="text" id="bug-browser" name="browser">
                                    </div>
                                    <div class="form-group col-4">
                                        <label for="bug-device">Device Type</label>
                                        <select id="bug-device" name="deviceType">
                                            <option value="desktop">Desktop</option>
                                            <option value="mobile">Mobile</option>
                                            <option value="tablet">Tablet</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="submit-error" style="display: none;"></div>
                            </form>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="button cancel-bug-report">Cancel</button>
                            <button type="button" class="button button-primary submit-bug-report">
                                <span class="button-text">Submit Report</span>
                                <span class="spinner" style="display: none;"></span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        init() {
            this.injectModal();
            this.bindEvents();
            this.detectEnvironment();
        }

        injectModal() {
            if (!$('#wpspeedtestpro-bug-report').length) {
                $('body').append(this.modalHtml);
            }
        }

        bindEvents() {
            const self = this;
            
            // File input handling
            $('#bug-screenshots').on('change', function() {
                const fileCount = this.files.length;
                $('.selected-files').text(fileCount ? `${fileCount} file(s) selected` : 'No files selected');
            });

            // Close modal
            $('.close-modal, .cancel-bug-report').on('click', function() {
                self.closeModal();
            });

            // Submit form
            $('.submit-bug-report').on('click', function() {
                self.submitReport();
            });

            // Close on background click
            $('#wpspeedtestpro-bug-report').on('click', function(e) {
                if ($(e.target).is('#wpspeedtestpro-bug-report')) {
                    self.closeModal();
                }
            });
        }

        detectEnvironment() {
            $('#bug-os').val(this.getOS());
            $('#bug-browser').val(this.getBrowser());
        }

        getOS() {
            const userAgent = window.navigator.userAgent;
            const platform = window.navigator.platform;
            const macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'];
            const windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'];
            
            if (macosPlatforms.indexOf(platform) !== -1) {
                return 'macOS';
            } else if (windowsPlatforms.indexOf(platform) !== -1) {
                return 'Windows';
            } else if (/Linux/.test(platform)) {
                return 'Linux';
            }
            return 'Unknown OS';
        }

        getBrowser() {
            const userAgent = navigator.userAgent;
            if (userAgent.indexOf("Chrome") > -1) {
                return "Chrome";
            } else if (userAgent.indexOf("Safari") > -1) {
                return "Safari";
            } else if (userAgent.indexOf("Firefox") > -1) {
                return "Firefox";
            } else if (userAgent.indexOf("MSIE") > -1 || userAgent.indexOf("Trident/") > -1) {
                return "Internet Explorer";
            } else if (userAgent.indexOf("Edge") > -1) {
                return "Edge";
            }
            return "Unknown Browser";
        }

        submitReport() {
            const self = this;
            const $form = $('#bug-report-form');
            const $submitBtn = $('.submit-bug-report');
            const $spinner = $submitBtn.find('.spinner');
            const $buttonText = $submitBtn.find('.button-text');
            const $error = $('.submit-error');

            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }

            const formData = new FormData($form[0]);
            formData.append('action', 'wpspeedtestpro_submit_bug_report');
            formData.append('nonce', wpspeedtestpro_ajax.nonce);
            

            $submitBtn.prop('disabled', true);
            $spinner.show();
            $buttonText.text('Submitting...');
            $error.hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Bug report submitted successfully!');
                        self.closeModal();
                    } else {
                        $error.html(response.data || 'Failed to submit bug report').show();
                    }
                },
                error: function() {
                    $error.html('Failed to submit bug report. Please try again.').show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $spinner.hide();
                    $buttonText.text('Submit Report');
                }
            });
        }

        openModal() {
            $('#wpspeedtestpro-bug-report').show();
        }

        closeModal() {
            const $modal = $('#wpspeedtestpro-bug-report');
            $modal.hide();
            $('#bug-report-form')[0].reset();
            $('.selected-files').text('No files selected');
            $('.submit-error').hide();
        }
    }

    // Initialize and expose to global scope
    window.wpSpeedTestProBugReport = new WPSpeedTestProBugReport();
    $(document).ready(() => window.wpSpeedTestProBugReport.init());

})(jQuery);