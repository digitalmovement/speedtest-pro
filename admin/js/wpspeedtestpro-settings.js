jQuery(document).ready(function($) {
    var $providerSelect = $('#wpspeedtestpro_selected_provider');
    var $packageSelect = $('#wpspeedtestpro_selected_package');
    var hostingProviders = JSON.parse(wpspeedtestpro_ajax.hosting_providers);
    var $userCountry = $('#wpspeedtestpro_user_country');
    const $gcpRegionSelect =$('select[name="wpspeedtestpro_selected_region"]');

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
                value: provider.id,
                text: provider.name
            }));
        });
        if (currentProvider) {
            $providerSelect.val(currentProvider);
        }
    }

    function updatePackages() {
        var selectedProviderId = $providerSelect.val();
        var currentPackage = $packageSelect.val();
        $packageSelect.empty().append('<option value="">Select a package</option>');

        if (selectedProviderId) {
            var provider = hostingProviders.providers.find(p => p.id == selectedProviderId);
            if (provider && provider.packages) {
                provider.packages.forEach(function(package) {
                    $packageSelect.append($('<option>', {
                        value: package.Package_ID,
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

        // Initialize event handlers for country selection
// Function to get the closest GCP region for a country
    function getGCPRegionForCountry(countryCode) {
        return countryToRegionMapping[countryCode] || countryToRegionMapping['DEFAULT'];
    }

    // Add this function to automatically update the GCP region when country changes
    function updateGCPRegionBasedOnCountry(countryCode) {
        const gcpRegion = getGCPRegionForCountry(countryCode);
        console.log('Country Code:', countryCode);
        console.log('Selected GCP Region:', gcpRegion);
        
        if ($gcpRegionSelect.length) {
            if ($gcpRegionSelect.find(`option[value="${gcpRegion}"]`).length) {
                $gcpRegionSelect.val(gcpRegion).trigger('change');
            } else {
                console.warn('GCP region ' + gcpRegion + ' not found in options');
            }
        } else {
            console.warn('GCP region select element not found');
        }
    }

    $providerSelect.on('change', function() {
        updatePackages();
    });
    
    $(document).on('change', '#wpspeedtestpro_user_country', function() {
        const selectedCountry = $(this).val();
        console.log('Country changed to:', selectedCountry);
        if (selectedCountry) {
            updateGCPRegionBasedOnCountry(selectedCountry);
        }
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