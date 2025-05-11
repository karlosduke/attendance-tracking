<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si el usuario ya registró asistencia hoy
$already_registered = false;
if (isset($_COOKIE['attendance_registered'])) {
    $last_attendance = json_decode(stripslashes($_COOKIE['attendance_registered']), true);
    if ($last_attendance['date'] === date('Y-m-d')) {
        $already_registered = true;
    }
}
?>

<div class="attendance-form-container">
    <?php if ($already_registered): ?>
        <div class="status-message info">
            Ya has registrado tu asistencia hoy. 
            Último registro: <?php echo date('H:i', strtotime($last_attendance['time'])); ?>
        </div>
    <?php else: ?>
        <form id="attendance-form" class="attendance-form">
            <?php wp_nonce_field('process_attendance', 'attendance_nonce'); ?>
            
            <div class="form-field">
                <label for="dni">DNI <span class="required">*</span></label>
                <div class="input-group">
                    <input type="text" 
                           id="dni" 
                           name="dni" 
                           required 
                           pattern="[0-9]{8}[A-Za-z]{1}"
                           maxlength="9"
                           placeholder="12345678A"
                           autocomplete="off">
                    <span class="dni-status"></span>
                </div>
                <p class="description">Introduce tu DNI (8 números y 1 letra)</p>
            </div>

            <div class="user-info" style="display: none;">
                <div class="info-box">
                    <h4>Datos del usuario:</h4>
                    <p><strong>Nombre:</strong> <span id="user-nombre"></span></p>
                    <p><strong>Apellidos:</strong> <span id="user-apellidos"></span></p>
                    <p><strong>Centro:</strong> <span id="user-centro"></span></p>
                </div>
            </div>

            <div class="form-field signature-field">
                <label for="signature-pad">Firma <span class="required">*</span></label>
                <div class="signature-pad-container">
                    <canvas id="signature-pad"></canvas>
                </div>
                <p class="description">Firma con el dedo o el ratón dentro del recuadro</p>
                <div class="signature-controls">
                    <button type="button" id="clear-signature" class="button">
                        <span class="dashicons dashicons-dismiss"></span>
                        Borrar firma
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" id="submit-attendance" class="button button-primary" disabled>
                    <span class="button-text">Registrar Asistencia</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>

        <div id="attendance-response" class="response-message" style="display: none;"></div>
    <?php endif; ?>
</div>

<template id="attendance-success-template">
    <div class="success-content">
        <div class="success-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <h3>¡Asistencia Registrada!</h3>
        <div class="success-details">
            <p><strong>Fecha:</strong> <span class="attendance-date"></span></p>
            <p><strong>Hora:</strong> <span class="attendance-time"></span></p>
            <p class="success-message">Gracias por registrar tu asistencia.</p>
        </div>
    </div>
</template>

<style>
/* Contenedor principal */
.attendance-form-container {
    max-width: 600px;
    margin: 2em auto;
    padding: 2em;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Campos del formulario */
.form-field {
    margin-bottom: 1.5em;
}

.form-field label {
    display: block;
    margin-bottom: 0.5em;
    font-weight: 600;
    color: #333;
}

.form-field .required {
    color: #d63638;
}

.form-field input[type="text"] {
    width: 100%;
    padding: 0.8em;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-field input[type="text"]:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

/* Pad de firma */
.signature-pad-container {
    border: 2px dashed #ddd;
    border-radius: 4px;
    margin-bottom: 1em;
    background: #fff;
    position: relative;
}

#signature-pad {
    width: 100%;
    height: 200px;
    background: #fff;
}

.signature-controls {
    margin-top: 1em;
}

/* Información del usuario */
.user-info {
    margin: 1.5em 0;
}

.info-box {
    background: #f0f6fc;
    padding: 1em;
    border-radius: 4px;
    border-left: 4px solid #2271b1;
}

.info-box h4 {
    margin: 0 0 0.5em 0;
    color: #1d2327;
}

/* Botones */
.button {
    padding: 0.8em 1.5em;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    transition: all 0.3s ease;
}

.button-primary {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}

.button-primary:hover {
    background: #135e96;
    border-color: #135e96;
}

.button-primary:disabled {
    background: #a7aaad !important;
    border-color: #a7aaad !important;
    cursor: not-allowed;
}

/* Estados y mensajes */
.status-message {
    padding: 1em;
    margin-bottom: 1.5em;
    border-radius: 4px;
}

.info {
    background-color: #f0f6fc;
    border-left: 4px solid #72aee6;
}

.success {
    background-color: #f0f6e6;
    border-left: 4px solid #00a32a;
}

.error {
    background-color: #fcf0f1;
    border-left: 4px solid #d63638;
}

/* Spinner de carga */
.spinner {
    display: none;
    width: 20px;
    height: 20px;
    margin-left: 10px;
    border: 2px solid #fff;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mensajes de éxito */
.success-content {
    text-align: center;
    padding: 2em;
}

.success-icon {
    font-size: 48px;
    color: #00a32a;
    margin-bottom: 1em;
}

.success-details {
    margin-top: 1.5em;
    text-align: left;
}

/* Responsive */
@media screen and (max-width: 600px) {
    .attendance-form-container {
        margin: 1em;
        padding: 1em;
    }

    .form-field input[type="text"] {
        font-size: 16px; /* Previene zoom en iOS */
    }

    .button {
        width: 100%;
        margin-bottom: 0.5em;
    }
}
</style>