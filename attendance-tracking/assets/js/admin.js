jQuery(document).ready(function($) {
    // Inicializar SignaturePad en páginas de administración que lo necesiten
    if ($('#signature-pad').length) {
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });

        // Ajustar tamaño del canvas
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        // Botón para limpiar firma
        $('#clear-signature').on('click', function() {
            signaturePad.clear();
        });
    }

    // Manejo de exportación CSV
    $('#export-csv').on('click', function(e) {
        e.preventDefault();
        const filters = {
            centro: $('#filter-centro').val(),
            usuario: $('#filter-usuario').val(),
            fecha_inicio: $('#filter-fecha-inicio').val(),
            fecha_fin: $('#filter-fecha-fin').val()
        };

        window.location.href = ajaxurl + '?action=export_attendance_csv&' + $.param(filters);
    });

    // Filtros dinámicos para la tabla de asistencias
    $('.attendance-filters select, .attendance-filters input').on('change', function() {
        const filters = {
            centro: $('#filter-centro').val(),
            usuario: $('#filter-usuario').val(),
            fecha_inicio: $('#filter-fecha-inicio').val(),
            fecha_fin: $('#filter-fecha-fin').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filter_attendance',
                nonce: attendanceAdmin.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    $('.attendance-table tbody').html(response.data.html);
                }
            }
        });
    });

    // Confirmación para eliminar registros
    $('.delete-record').on('click', function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) {
            e.preventDefault();
        }
    });

    // Validación de DNI en tiempo real
    $('#dni').on('input', function() {
        const dni = $(this).val();
        if (dni.length === 9) { // DNI completo (8 números + letra)
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_dni',
                    nonce: attendanceAdmin.nonce,
                    dni: dni
                },
                success: function(response) {
                    if (response.success) {
                        $('#dni-message').html('<span class="valid">DNI válido</span>');
                    } else {
                        $('#dni-message').html('<span class="invalid">' + response.data.message + '</span>');
                    }
                }
            });
        }
    });

    // Datepickers para filtros de fecha
    if ($.fn.datepicker) {
        $('.date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '0'
        });
    }
});