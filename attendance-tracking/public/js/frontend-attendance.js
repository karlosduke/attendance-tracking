jQuery(document).ready(function($) {
    // Inicializar SignaturePad
    const canvas = document.getElementById('signature-pad');
    if (canvas) {
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 0.5,
            maxWidth: 2.5
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
        $('#clear-signature').on('click', function(e) {
            e.preventDefault();
            signaturePad.clear();
            validateForm();
        });

        // Validar firma al dibujar
        signaturePad.addEventListener("endStroke", validateForm);

        // Búsqueda de usuario por DNI
        let dniTimeout;
        $('#dni').on('input', function() {
            const dni = $(this).val().toUpperCase();
            $(this).val(dni);
            clearTimeout(dniTimeout);

            // Limpiar información de usuario
            $('.user-info').hide();
            $('#submit-attendance').prop('disabled', true);

            if (dni.length === 9) {
                $('.dni-status').html('<span class="spinner is-active"></span>');
                
                dniTimeout = setTimeout(function() {
                    $.ajax({
                        url: attendanceFrontend.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'verify_user',
                            nonce: attendanceFrontend.nonce,
                            dni: dni
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.dni-status').html('<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>');
                                $('#user-nombre').text(response.data.nombre);
                                $('#user-apellidos').text(response.data.apellidos);
                                $('#user-centro').text(response.data.centro);
                                $('.user-info').slideDown();
                                validateForm();
                            } else {
                                $('.dni-status').html('<span class="dashicons dashicons-no-alt" style="color: #d63638;"></span>');
                                showMessage(response.data.message, 'error');
                            }
                        },
                        error: function() {
                            $('.dni-status').html('<span class="dashicons dashicons-warning" style="color: #dba617;"></span>');
                            showMessage('Error de conexión', 'error');
                        }
                    });
                }, 500);
            } else {
                $('.dni-status').empty();
            }
        });

        // Validar formulario
        function validateForm() {
            const dni = $('#dni').val();
            const isValidDni = /^[0-9]{8}[A-Z]$/.test(dni);
            const hasSignature = !signaturePad.isEmpty();
            const userFound = $('.user-info').is(':visible');

            $('#submit-attendance').prop('disabled', !(isValidDni && hasSignature && userFound));
        }

        // Enviar formulario
        $('#attendance-form').on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $('#submit-attendance');
            const $buttonText = $submitButton.find('.button-text');
            const $spinner = $submitButton.find('.spinner');

            // Deshabilitar botón y mostrar spinner
            $submitButton.prop('disabled', true);
            $buttonText.text('Registrando...');
            $spinner.show();

            $.ajax({
                url: attendanceFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'process_attendance',
                    nonce: attendanceFrontend.nonce,
                    dni: $('#dni').val(),
                    signature: signaturePad.toDataURL()
                },
                success: function(response) {
                    if (response.success) {
                        // Guardar cookie de registro
                        const now = new Date();
                        document.cookie = `attendance_registered=${JSON.stringify({
                            date: now.toISOString().split('T')[0],
                            time: now.toTimeString().split(' ')[0]
                        })}; path=/; max-age=86400`;

                        // Mostrar mensaje de éxito
                        const template = document.getElementById('attendance-success-template');
                        const content = template.content.cloneNode(true);
                        
                        content.querySelector('.attendance-date').textContent = 
                            new Date().toLocaleDateString();
                        content.querySelector('.attendance-time').textContent = 
                            new Date().toLocaleTimeString();

                        $('#attendance-form').slideUp(400, function() {
                            $('#attendance-response')
                                .html(content)
                                .fadeIn();
                        });
                    } else {
                        showMessage(response.data.message, 'error');
                        $submitButton.prop('disabled', false);
                    }
                },
                error: function() {
                    showMessage('Error de conexión', 'error');
                    $submitButton.prop('disabled', false);
                },
                complete: function() {
                    $buttonText.text('Registrar Asistencia');
                    $spinner.hide();
                }
            });
        });

        // Mostrar mensajes
        function showMessage(message, type) {
            const $message = $('<div>')
                .addClass('status-message')
                .addClass(type)
                .text(message)
                .hide();

            $('.status-message').remove();
            $('.attendance-form-container').prepend($message);
            $message.slideDown();

            if (type !== 'error') {
                setTimeout(function() {
                    $message.slideUp(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    }
});