<?php
if (!defined('ABSPATH')) {
    exit;
}

class Frontend_Forms {
    private static $instance = null;

    private function __construct() {
        add_shortcode('attendance_form', array($this, 'render_attendance_form'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_attendance_form($atts) {
        // Encolar scripts y estilos necesarios
        wp_enqueue_style('attendance-frontend');
        wp_enqueue_script('signature-pad');
        wp_enqueue_script('attendance-frontend');

        ob_start();
        ?>
        <div class="attendance-form-container">
            <form id="attendance-form" class="attendance-form">
                <div class="form-group">
                    <label for="dni">DNI <span class="required">*</span></label>
                    <input type="text" 
                           id="dni" 
                           name="dni" 
                           required 
                           pattern="[0-9]{8}[A-Za-z]{1}"
                           title="Formato: 8 números y una letra"
                           maxlength="9"
                           placeholder="12345678A">
                </div>

                <div class="form-group">
                    <label for="signature-pad">Firma <span class="required">*</span></label>
                    <div class="signature-pad-container">
                        <canvas id="signature-pad" class="signature-pad"></canvas>
                    </div>
                    <div class="signature-pad-controls">
                        <button type="button" id="clear-signature" class="button">
                            Limpiar Firma
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submit-attendance" class="button button-primary">
                        Registrar Asistencia
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}