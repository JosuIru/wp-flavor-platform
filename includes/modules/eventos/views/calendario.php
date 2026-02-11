<?php
/** Calendario de Eventos * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-calendario-eventos">
    <h1><?php _e('Calendario de Eventos', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-calendar-toolbar">
        <div class="flavor-calendar-nav"><button id="btn-mes-ant" class="button"><span class="dashicons dashicons-arrow-left-alt2"></span></button><h2 id="mes-actual"></h2><button id="btn-mes-sig" class="button"><span class="dashicons dashicons-arrow-right-alt2"></span></button><button id="btn-hoy" class="button"><?php _e('Hoy', 'flavor-chat-ia'); ?></button></div>
        <div class="flavor-view-modes"><button class="flavor-view-btn active" data-mode="month"><?php _e('Mes', 'flavor-chat-ia'); ?></button><button class="flavor-view-btn" data-mode="week"><?php _e('Semana', 'flavor-chat-ia'); ?></button><button class="flavor-view-btn" data-mode="day"><?php _e('Día', 'flavor-chat-ia'); ?></button></div>
        <select id="filtro-categoria-cal" class="flavor-select"><option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option></select>
    </div>
    <div id="calendar-container" class="flavor-calendar-container"></div>
</div>
<style>
.flavor-calendario-eventos{margin:20px;}.flavor-calendar-toolbar{background:#fff;padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;}.flavor-calendar-nav{display:flex;align-items:center;gap:10px;}.flavor-calendar-nav h2{margin:0;min-width:200px;text-align:center;}.flavor-view-modes{display:flex;gap:5px;border:1px solid #ddd;border-radius:4px;overflow:hidden;}.flavor-view-btn{padding:8px 16px;border:none;background:#fff;cursor:pointer;font-size:13px;border-right:1px solid #ddd;}.flavor-view-btn:last-child{border-right:none;}.flavor-view-btn.active{background:#2271b1;color:#fff;}.flavor-calendar-container{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;min-height:600px;}.flavor-calendar{width:100%;}.flavor-calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px;}.flavor-calendar-cell{min-height:100px;border:1px solid #e5e7eb;border-radius:4px;padding:8px;}.flavor-calendar-cell.today{border-color:#2271b1;background:#f0f6fc;}.flavor-evento-mini{font-size:11px;padding:3px 6px;margin-bottom:3px;border-radius:3px;cursor:pointer;background:#dbeafe;border-left:3px solid #2271b1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
</style>
<script>
jQuery(document).ready(function($) {
    let currentDate = new Date();
    let currentView = 'month';
    renderizarCalendario();
    $('.flavor-view-btn').on('click', function() {
        currentView = $(this).data('mode');
        $('.flavor-view-btn').removeClass('active');
        $(this).addClass('active');
        renderizarCalendario();
    });
    $('#btn-mes-ant').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderizarCalendario();
    });
    $('#btn-mes-sig').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderizarCalendario();
    });
    $('#btn-hoy').on('click', function() {
        currentDate = new Date();
        renderizarCalendario();
    });
    function renderizarCalendario() {
        $('#mes-actual').text(currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }));
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_obtener_calendario', year: currentDate.getFullYear(), month: currentDate.getMonth() + 1, categoria: $('#filtro-categoria-cal').val() },
            success: function(response) {
                if (response.success) {
                    generarCalendarioHTML(response.data);
                }
            }
        });
    }
    function generarCalendarioHTML(eventos) {
        let html = '<div class="flavor-calendar"><div class="flavor-calendar-grid">';
        ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'].forEach(day => {
            html += `<div style="text-align:center;font-weight:600;padding:10px;background:#f3f4f6;border-radius:4px;">${day}</div>`;
        });
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        for (let i = 0; i < firstDay.getDay(); i++) {
            html += '<div class="flavor-calendar-cell" style="background:#f9fafb;"></div>';
        }
        const today = new Date();
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = today.getDate() === day && today.getMonth() === currentDate.getMonth() && today.getFullYear() === currentDate.getFullYear();
            html += `<div class="flavor-calendar-cell ${isToday ? 'today' : ''}"><div style="font-weight:600;margin-bottom:5px;">${day}</div>`;
            if (eventos[dateStr]) {
                eventos[dateStr].forEach(evento => {
                    html += `<div class="flavor-evento-mini" title="<?php echo esc_attr__('${evento.titulo}', 'flavor-chat-ia'); ?>">${evento.hora} ${evento.titulo}</div>`;
                });
            }
            html += '</div>';
        }
        html += '</div></div>';
        $('#calendar-container').html(html);
    }
    $('#filtro-categoria-cal').on('change', renderizarCalendario);
});
</script>
