jQuery(function($) {
    var config = window.flavorPermissionsAdmin || {};

    $('.toggle-capabilities').on('click', function() {
        var grupo = $(this).data('grupo');
        var checked = $(this).prop('checked');
        $('input[data-grupo="' + grupo + '"]').prop('checked', checked);
    });

    $('.delete-role-btn').on('click', function(e) {
        if (!window.confirm(config.confirmDeleteRole || '')) {
            e.preventDefault();
        }
    });

    $('#filter-users').on('keyup', function() {
        var filter = $(this).val().toLowerCase();
        $('.user-permissions-card').each(function() {
            var $card = $(this);
            var nombre = $card.find('.user-name').text().toLowerCase();
            var email = $card.find('.user-email').text().toLowerCase();
            $card.toggle(nombre.indexOf(filter) > -1 || email.indexOf(filter) > -1);
        });
    });

    $('.module-role-select').on('change', function() {
        var $form = $(this).closest('form');
        $form.find('.assign-btn').prop('disabled', !$(this).val());
    }).trigger('change');
});
