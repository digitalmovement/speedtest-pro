jQuery(document).ready(function($) {
    var $providerSelect = $('#wpspeedtestpro_selected_provider');
    var $packageSelect = $('#wpspeedtestpro_selected_package');
    var selectedPackage = $packageSelect.val(); // Store the initially selected package

    function updatePackages() {
        var provider = $providerSelect.val();
        $packageSelect.prop('disabled', !provider);
        $packageSelect.find('option').hide();
        $packageSelect.find('option[value=""]').show();
        
        if (provider) {
            $packageSelect.find('option[data-provider="' + provider + '"]').show();
        }
        
        // Reset package selection if the current selection is not valid for the new provider
        if ($packageSelect.find('option:selected:hidden').length) {
            $packageSelect.val('');
        }
    }

    $providerSelect.on('change', updatePackages);

    // Initial update on page load
    updatePackages();

    // If a package was previously selected, try to reselect it
    if (selectedPackage) {
        $packageSelect.val(selectedPackage);
        // If the previously selected package is not available, reset to default
        if (!$packageSelect.val()) {
            $packageSelect.val('');
        }
    }

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