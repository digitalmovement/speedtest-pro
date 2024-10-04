jQuery(document).ready(function($) {
    var $providerSelect = $('#wpspeedtestpro_selected_provider');
    var $packageSelect = $('#wpspeedtestpro_selected_package');
    var selectedPackage = $packageSelect.val(); // Store the initially selected package

    function updatePackages(provider, callback) {
        $packageSelect.empty().append('<option value="">Select a package</option>');

        if (provider) {
            $.ajax({
                url: wpspeedtestpro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_provider_packages',
                    provider: provider,
                    nonce: wpspeedtestpro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var packages = response.data;
                        packages.forEach(function(package) {
                            $packageSelect.append($('<option>', {
                                value: package.type,
                                text: package.type
                            }));
                        });
                        if (callback) callback();
                    }
                }
            });
        }
    }

    $providerSelect.on('change', function() {
        var provider = $(this).val();
        updatePackages(provider);
    });

    // If a provider is already selected on page load
    if ($providerSelect.val()) {
        updatePackages($providerSelect.val(), function() {
            // After populating packages, set the previously selected package
            if (selectedPackage) {
                $packageSelect.val(selectedPackage);
            }
        });
    }

	// Handles popup modal 
	var $checkbox = $('#wpspeedtestpro_allow_data_collection');
    var $form = $('#wpspeedtestpro-settings-form');
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


});