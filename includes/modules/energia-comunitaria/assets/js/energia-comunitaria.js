(function ($) {
    'use strict';

    $(function () {
        $('.flavor-energia-panel').attr('data-module-ready', '1');

        $(document).on('submit', '.flavor-energia-form-wrapper form', function (event) {
            const form = event.currentTarget;
            const action = form.getAttribute('action');

            if (!action || action.indexOf('admin-ajax.php') === -1) {
                return;
            }

            event.preventDefault();

            const $form = $(form);
            const $submit = $form.find('button[type="submit"]');
            const formData = $form.serialize();

            $submit.prop('disabled', true);

            $.post(action, formData)
                .done(function (response) {
                    const message = response?.data?.message || response?.message || 'Guardado correctamente';
                    window.alert(message);
                    if (response?.success) {
                        if ($form.hasClass('flavor-energia-inline-form')) {
                            window.location.reload();
                            return;
                        }
                        form.reset();
                    }
                })
                .fail(function () {
                    window.alert('No se pudo completar la accion');
                })
                .always(function () {
                    $submit.prop('disabled', false);
                });
        });
    });
})(jQuery);
