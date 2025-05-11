jQuery(document).ready(function($) {
    // Inicializar SignaturePad
    const canvas = document.getElementById('signature-pad');
    if (canvas) {
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

        // Limpiar firma
        $('#clear-signature').on('click', function() {
            signaturePad.clear();
        });

        // Enviar formulario
        $('#attendance-form').on('submit', function(e) {
            e.preventDefault();

            // Validar DNI
            const dni = $('#dni').val();
            if (!dni || dni.length !== 9) {
                showMessage('El DNI debe tener 8 números y una letra', 'error');
                return;
            }

            // Validar firma
            if (signaturePad.isEmpty()) {
                showMessage('Por favor, firme antes de enviar', 'error');
                return;
            }

            // Obtener datos de la firma
            const signatureData = signaturePad.toDataURL();

            // Enviar datos
            $.ajax({
                url: attendanceFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'process_attendance',
                    nonce: attendanceFrontend.nonce,
                    dni: dni,
                    signature: signatureData
                },
                beforeSend: function() {
                    $('#submit-attendance').prop('disabled', true).text('Enviando...');
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('Asistencia registrada correctamente', 'success');
                        signaturePad.clear();
                        $('#attendance-form')[0].reset();
                    } else {
                        showMessage(response.data.message || 'Error al registrar la asistencia', 'error');
                    }
                },
                error: function() {
                    showMessage('Error de conexión', 'error');
                },
                complete: function() {
                    $('#submit-attendance').prop('disabled', false).text('Registrar Asistencia');
                }
            });
        });
    }

    // Función para mostrar mensajes
    function showMessage(message, type) {
        const messageDiv = $('<div>')
            .addClass('status-message')
            .addClass(type)
            .text(message)
            .hide();

        $('.attendance-form-container').prepend(messageDiv);
        messageDiv.slideDown();

        setTimeout(function() {
            messageDiv.slideUp(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Convertir DNI a mayúsculas
    $('#dni').on('input', function() {
        this.value = this.value.toUpperCase();
    });
});