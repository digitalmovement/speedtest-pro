jQuery(document).ready(function($) {
    $('#wpspeedtestpro-server-tabs').tabs();

    // Make tables responsive
    $('.wp-list-table').wrap('<div class="table-responsive"></div>');

    // Style PHPInfo output
    $('.phpinfo-wrapper table').addClass('wp-list-table widefat fixed striped');
    $('.phpinfo-wrapper td.e').addClass('row-title');
});