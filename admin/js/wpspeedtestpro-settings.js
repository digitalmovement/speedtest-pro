jQuery(document).ready(function($) {
    var $providerSelect = $('#wpspeedtestpro_selected_provider');
    var $packageSelect = $('#wpspeedtestpro_selected_package');
    var hostingProviders = JSON.parse(wpspeedtestpro_ajax.hosting_providers);

        
    $('#auth-action').on('change', function() {
        var action = $(this).val();
        if (action === 'register') {
            $('#first-name, #last-name, #organization').show();
            $('#auth-submit').text('Register');
        } else {
            $('#first-name, #last-name, #organization').hide();
            $('#auth-submit').text('Login');
        }
    });

    $('#auth-submit').on('click', function(e) {
        e.preventDefault();
        var action = $('#auth-action').val();
        var $message = $('#auth-message');
        
        // Reset message
        $message.removeClass('error success').hide();

        var data = {
            action: action === 'login' ? 'ssl_login_user' : 'ssl_register_user',
            nonce: wpspeedtestpro_ajax.nonce,
            email: $('#email').val().trim()
        };

        // Validate email
        if (!isValidEmail(data.email)) {
            $message.addClass('error').text('Please enter a valid email address.').show();
            return;
        }

        if (action === 'register') {
            // Add registration fields
            data.first_name = $('#first-name').val().trim();
            data.last_name = $('#last-name').val().trim();
            data.organization = $('#organization').val().trim();

            // Validate required fields
            if (!data.first_name || !data.last_name || !data.organization) {
                $message.addClass('error').text('All fields are required for registration.').show();
                return;
            }
        }

        // Show loading state
        $('#auth-submit').prop('disabled', true).text('Processing...');

        function initializeFormState() {
            var action = $('#auth-action').val();
            var hasEmail = $('#email').val().trim() !== '';
            
            if (action === 'register') {
                $('#first-name, #last-name, #organization').show();
                $('#auth-submit').text('Register');
            } else {
                $('#first-name, #last-name, #organization').hide();
                $('#auth-submit').text('Login');
            }
    
            // If we have an email, show it as readonly in login mode
            if (hasEmail && action === 'login') {
                $('#email').prop('readonly', true);
            } else {
                $('#email').prop('readonly', false);
            }
        }
    
        // Initialize on page load
        initializeFormState();
    
        // Update form state when switching between login/register
        $('#auth-action').on('change', function() {
            initializeFormState();
        });
    
        // Clear readonly when switching to register
        $('#auth-action').on('change', function() {
            var action = $(this).val();
            if (action === 'register') {
                $('#email').prop('readonly', false);
            } else {
                // Only set readonly if we have a saved email
                if ($('#email').val().trim() !== '') {
                    $('#email').prop('readonly', true);
                }
            }
        });


        

        $.ajax({
            url: wpspeedtestpro_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success')
                        .text(response.data).show();
                    
                    // Optionally reload after successful authentication
                    setTimeout(function() {
                        //location.reload();
                    }, 1500);
                } else {
                    $message.addClass('error')
                        .text(response.data || 'An error occurred.').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                $message.addClass('error')
                    .text('An error occurred. Please try again.').show();
            },
            complete: function() {
                // Reset button state
                $('#auth-submit').prop('disabled', false)
                    .text(action === 'register' ? 'Register' : 'Login');
            }
        });
    });

    // Email validation helper
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }


    function populateProviders() {
        var currentProvider = $providerSelect.val();
        $providerSelect.empty().append('<option value="">Select a provider</option>');
        hostingProviders.providers.forEach(function(provider) {
            $providerSelect.append($('<option>', {
                value: provider.name,
                text: provider.name
            }));
        });
        if (currentProvider) {
            $providerSelect.val(currentProvider);
        }
    }

    function updatePackages() {
        var selectedProvider = $providerSelect.val();
        var currentPackage = $packageSelect.val();
        $packageSelect.empty().append('<option value="">Select a package</option>');

        if (selectedProvider) {
            var provider = hostingProviders.providers.find(p => p.name === selectedProvider);
            if (provider && provider.packages) {
                provider.packages.forEach(function(package) {
                    $packageSelect.append($('<option>', {
                        value: package.type,
                        text: package.type + ' - ' + package.description
                    }));
                });
            }
        }

        // Restore the previously selected package if it's still available
        if (currentPackage && $packageSelect.find(`option[value="${currentPackage}"]`).length > 0) {
            $packageSelect.val(currentPackage);
        }
    }

    $providerSelect.on('change', function() {
        updatePackages();
    });

    // Initial population of providers and packages
    populateProviders();
    updatePackages();

    // Handles popup modal 
    var $checkbox = $('#wpspeedtestpro_allow_data_collection');
    var $form = $('#wpspeedtestpro_settings-form');
    var $modal = $('#data-collection-modal');

    $form.on('change', function(e) {
        if (!$checkbox.is(':checked') && $checkbox.data('original') !== false) {
            e.preventDefault();
            $modal.show();
        }
    });

    $('#modal-cancel').on('click', function() {
        $modal.hide();
        $checkbox.prop('checked', true);
    });

    $('#modal-confirm').on('click', function() {
        $modal.hide();
        $form.submit();
    });

    $checkbox.data('original', $checkbox.is(':checked'));

    // Error handling
    if ($('.wpspeedtestpro-error').length) {
        alert('There was an error loading some options. Please refresh the page or try again later.');
    }
});