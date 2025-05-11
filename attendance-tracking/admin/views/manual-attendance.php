<?php
if (!defined('ABSPATH')) {
    exit;
}

$centros = DatabaseManager::get_instance()->get_centros();
?>

<div class="wrap">
    <h1>Registro Manual de Asistencia</h1>

    <div class="card">
        <form method="post" id="manual-attendance-form" class="attendance-form">
            <?php wp_nonce_field('save_manual_attendance', 'attendance_nonce'); ?>

            <div class="form-field">
                <label for="dni">DNI del Usuario <span class="required">*</span></label>
                <input type="text" 
                       id="dni" 
                       name="dni" 
                       required 
                       pattern="[0-9]{8}[A-Za-z]{1}"
                       maxlength="9">
                <p class="description" id="dni-status"></p>
            </div>

            <div class="form-field user-info" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th>Nombre:</th>
                        <td id="user-nombre"></td>
                    </tr>
                    <tr>
                        <th>Apellidos:</th>
                        <td id="user-apellidos"></td>
                    </tr>
                    <tr>
                        <th>Centro:</th>
                        <td id="user-centro"></td>
                    </tr>
                </table>
            </div>

            <div class="form-field">
                <label for="fecha">Fecha <span class="required">*</span></label>
                <input type="date" 
                       id="fecha" 
                       name="fecha" 
                       required 
                       max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-field">
                <label for="hora">Hora <span class="required">*</span></label>
                <input type="time" 
                       id="hora" 
                       name="hora" 
                       required>
            </div>

            <div class="form-field">
                <label for="firma">Firma</label>
                <div class="signature-pad-container">
                    <canvas id="signature-pad"></canvas>
                </div>
                <div class="signature-pad-controls">
                    <button type="button" class="button" id="clear-signature">Limpiar Firma</button>
                </div>
            </div>

            <input type="submit" 
                   name="submit" 
                   id="submit" 
                   class="button button-primary" 
                   value="Registrar Asistencia">
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const signaturePad = new SignaturePad(document.getElementById('signature-pad'), {
        backgroundColor: 'rgb(255, 255, 255)'
    });

    // Limpiar firma
    $('#clear-signature').on('click', function() {
        signaturePad.clear();
    });

    // Búsqueda de usuario por DNI
    let timeoutId;
    $('#dni').on('input', function() {
        const dni = $(this).val().toUpperCase();
        $(this).val(dni);

        clearTimeout(timeoutId);
        if (dni.length === 9) {
            timeoutId = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_user_by_dni',
                        nonce: '<?php echo wp_create_nonce("get-user-nonce"); ?>',
                        dni: dni
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#dni-status').html('<span class="valid">Usuario encontrado</span>');
                            $('#user-nombre').text(response.data.Nombre);
                            $('#user-apellidos').text(response.data.Apellidos);
                            $('#user-centro').text(response.data.centro);
                            $('.user-info').show();
                            $('#submit').prop('disabled', false);
                        } else {
                            $('#dni-status').html('<span class="invalid">Usuario no encontrado</span>');
                            $('.user-info').hide();
                            $('#submit').prop('disabled', true);
                        }
                    }
                });
            }, 500);
        } else {
            $('#dni-status').empty();
            $('.user-info').hide();
            $('#submit').prop('disabled', true);
        }
    });

    // Envío del formulario
    $('#manual-attendance-form').on('submit', function(e) {
        e.preventDefault();

        if (signaturePad.isEmpty()) {
            alert('Por favor, añada una firma');
            return;
        }

        const formData = {
            action: 'save_manual_attendance',
            nonce: '<?php echo wp_create_nonce("save-attendance-nonce"); ?>',
            dni: $('#dni').val(),
            fecha: $('#fecha').val(),
            hora: $('#hora').val(),
            signature: signaturePad.toDataURL()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Asistencia registrada correctamente');
                    location.reload();
                } else {
                    alert(response.data.message || 'Error al registrar la asistencia');
                }
            }
        });
    });

    // Validación de fecha y hora
    const now = new Date();
    $('#fecha').attr('max', now.toISOString().split('T')[0]);
    
    function getCurrentTime() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }
    
    if (!$('#hora').val()) {
        $('#hora').val(getCurrentTime());
    }
});
</script>

<style>
.signature-pad-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
}

#signature-pad {
    width: 100%;
    height: 200px;
}

.valid {
    color: #46b450;
}

.invalid {
    color: #dc3232;
}

.user-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
}
</style>