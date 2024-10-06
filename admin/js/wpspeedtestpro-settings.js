jQuery(document).ready(function($) {
    var $providerSelect = $('#wpspeedtestpro_selected_provider');
    var $packageSelect = $('#wpspeedtestpro_selected_package');
    var hostingProviders = JSON.parse(wpspeedtestpro_ajax.hosting_providers);

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