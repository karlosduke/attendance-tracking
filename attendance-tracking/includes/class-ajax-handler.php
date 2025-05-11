<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler {
    private static $instance = null;

    private function __construct() {
        // Admin AJAX handlers
        add_action('wp_ajax_validate_dni', array($this, 'validate_dni'));
        add_action('wp_ajax_filter_attendance', array($this, 'filter_attendance'));
        add_action('wp_ajax_export_attendance_csv', array($this, 'export_attendance_csv'));
        add_action('wp_ajax_save_manual_attendance', array($this, 'save_manual_attendance'));
        
        // Frontend AJAX handlers
        add_action('wp_ajax_process_attendance', array($this, 'process_attendance'));
        add_action('wp_ajax_nopriv_process_attendance', array($this, 'process_attendance'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function process_attendance() {
        try {
            check_ajax_referer('attendance-frontend-nonce', 'nonce');

            if (empty($_POST['dni']) || empty($_POST['signature'])) {
                throw new Exception('Faltan datos requeridos.');
            }

            $dni = sanitize_text_field($_POST['dni']);
            $signature_data = $_POST['signature'];

            // Validar DNI
            if (!$this->is_valid_dni($dni)) {
                throw new Exception('El formato del DNI no es válido.');
            }

            // Obtener usuario por DNI
            $user = DatabaseManager::get_instance()->get_user_by_dni($dni);
            if (!$user) {
                throw new Exception('Usuario no encontrado.');
            }

            // Procesar y guardar la firma
            $signature_url = $this->save_signature($signature_data, $user->id);

            // Guardar registro de asistencia
            $attendance_data = array(
                'user_id' => $user->id,
                'firma_url' => $signature_url,
                'id_centro' => $user->id_centro,
                'created_at' => current_time('mysql', true)
            );

            if (DatabaseManager::get_instance()->save_attendance($attendance_data)) {
                wp_send_json_success(array(
                    'message' => 'Asistencia registrada correctamente'
                ));
            } else {
                throw new Exception('Error al guardar la asistencia.');
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    public function filter_attendance() {
        check_ajax_referer('attendance-admin-nonce', 'nonce');

        $filters = array(
            'centro' => isset($_POST['filters']['centro']) ? intval($_POST['filters']['centro']) : 0,
            'usuario' => isset($_POST['filters']['usuario']) ? intval($_POST['filters']['usuario']) : 0,
            'fecha_inicio' => isset($_POST['filters']['fecha_inicio']) ? sanitize_text_field($_POST['filters']['fecha_inicio']) : '',
            'fecha_fin' => isset($_POST['filters']['fecha_fin']) ? sanitize_text_field($_POST['filters']['fecha_fin']) : ''
        );

        $records = DatabaseManager::get_instance()->get_filtered_attendance($filters);
        
        ob_start();
        if ($records) {
            foreach ($records as $record) {
                include ATTENDANCE_PLUGIN_DIR . 'admin/views/partials/attendance-row.php';
            }
        } else {
            echo '<tr><td colspan="8">No se encontraron registros</td></tr>';
        }
        
        wp_send_json_success(array(
            'html' => ob_get_clean()
        ));
    }

    public function export_attendance_csv() {
        check_admin_referer('export-attendance-csv');

        $filters = array(
            'centro' => isset($_GET['centro']) ? intval($_GET['centro']) : 0,
            'usuario' => isset($_GET['usuario']) ? intval($_GET['usuario']) : 0,
            'fecha_inicio' => isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '',
            'fecha_fin' => isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : ''
        );

        $records = DatabaseManager::get_instance()->get_filtered_attendance($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=registros_asistencia_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8

        // Encabezados
        fputcsv($output, array(
            'Fecha',
            'Hora',
            'Nombre',
            'Apellidos',
            'DNI',
            'Centro',
            'Firma'
        ));

        // Datos
        foreach ($records as $record) {
            fputcsv($output, array(
                $record->Fecha,
                $record->Hora,
                $record->Nombre,
                $record->Apellidos,
                $record->DNI,
                $record->centro,
                $record->firma_url
            ));
        }

        fclose($output);
        exit;
    }

    private function save_signature($signature_data, $user_id) {
        $upload_dir = wp_upload_dir();
        $signatures_dir = $upload_dir['basedir'] . '/signatures';

        // Crear directorio si no existe
        if (!file_exists($signatures_dir)) {
            wp_mkdir_p($signatures_dir);
        }

        // Generar nombre único
        $filename = sprintf(
            'signature_%s_%s_%s.png',
            $user_id,
            date('Ymd_His'),
            substr(md5(rand()), 0, 8)
        );

        $signature_path = $signatures_dir . '/' . $filename;
        $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
        $signature_data = str_replace(' ', '+', $signature_data);
        $signature_decoded = base64_decode($signature_data);

        if (file_put_contents($signature_path, $signature_decoded) === false) {
            throw new Exception('Error al guardar la firma.');
        }

        return 'signatures/' . $filename;
    }

    private function is_valid_dni($dni) {
        return preg_match('/^[0-9]{8}[A-Z]$/', $dni);
    }

    public function validate_dni() {
        check_ajax_referer('attendance-admin-nonce', 'nonce');

        $dni = sanitize_text_field($_POST['dni']);
        
        if (!$this->is_valid_dni($dni)) {
            wp_send_json_error(array(
                'message' => 'El formato del DNI no es válido'
            ));
        }

        if (DatabaseManager::get_instance()->dni_exists($dni)) {
            wp_send_json_error(array(
                'message' => 'Este DNI ya está registrado'
            ));
        }

        wp_send_json_success();
    }
}