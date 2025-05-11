<?php
/**
 * Plugin Name: Attendance Tracking
 * Plugin URI: https://your-domain.com/attendance-tracking
 * Description: Sistema de registro de asistencia con firma digital para WordPress, integrado con Amelia
 * Version: 1.0.0
 * Author: Karlos Duke
 * Author URI: https://karlosduke.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: attendance-tracking
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Last Modified: 2025-05-11 09:33:10 UTC
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('ATTENDANCE_VERSION', '1.0.0');
define('ATTENDANCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATTENDANCE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ATTENDANCE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ATTENDANCE_LAST_MODIFIED', '2025-05-11 09:33:10');
define('ATTENDANCE_AUTHOR', 'karlosduke');

// El resto del código permanece igual, solo cambiamos los textos mostrados en el admin

class AttendanceTracking {
    // ... (código anterior)

    private function init_hooks() {
        // ... (código anterior)

        // Personalizar textos del menú admin
        add_filter('admin_menu', function() {
            global $menu, $submenu;
            
            // Cambiar el texto del menú principal
            foreach($menu as $key => $item) {
                if($item[2] === 'attendance-system') {
                    $menu[$key][0] = 'Attendance Tracking';
                    break;
                }
            }

            // Cambiar textos de submenús
            if(isset($submenu['attendance-system'])) {
                $new_labels = array(
                    0 => 'Attendance Records', // Registros de Asistencia
                    1 => 'New User',          // Nuevo Usuario
                    2 => 'Users',             // Usuarios
                    3 => 'Manual Record',      // Registro Manual
                    4 => 'Centers'            // Centros
                );

                foreach($submenu['attendance-system'] as $key => $item) {
                    if(isset($new_labels[$key])) {
                        $submenu['attendance-system'][$key][0] = $new_labels[$key];
                    }
                }
            }
        }, 999);

        // Personalizar título de la página de administración
        add_filter('admin_title', function($admin_title, $title) {
            return str_replace('Sistema de Asistencia', 'Attendance Tracking', $admin_title);
        }, 10, 2);
    }

    public function activate() {
        // Verificar versión mínima de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(ATTENDANCE_PLUGIN_BASENAME);
            wp_die('This plugin requires PHP 7.4 or higher.');
        }

        // Verificar versión mínima de WordPress
        if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
            deactivate_plugins(ATTENDANCE_PLUGIN_BASENAME);
            wp_die('This plugin requires WordPress 5.8 or higher.');
        }

        // Verificar si Amelia está instalado y activado
        if (!class_exists('Amelia\\Domain\\Entity\\User\\AbstractUser')) {
            deactivate_plugins(ATTENDANCE_PLUGIN_BASENAME);
            wp_die('This plugin requires Amelia Booking to be installed and activated.');
        }

        // Crear tablas en la base de datos
        DatabaseManager::get_instance()->create_tables();
        
        // Crear directorio para firmas
        $this->create_signatures_directory();

        // Establecer capacidades
        $this->set_capabilities();

        // Crear páginas necesarias
        $this->create_pages();

        // Registrar versión instalada
        update_option('attendance_tracking_version', ATTENDANCE_VERSION);
        update_option('attendance_tracking_installed_by', wp_get_current_user()->user_login);
        update_option('attendance_tracking_installed_date', current_time('mysql'));

        // Limpiar caché de permalinks
        flush_rewrite_rules();
    }

    private function create_pages() {
        // Página de registro de asistencia
        if (!get_page_by_path('attendance-register')) {
            wp_insert_post(array(
                'post_title' => 'Attendance Register',
                'post_content' => '[attendance_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'attendance-register'
            ));
        }
    }

    // ... (resto del código permanece igual)
}

// Inicializar el plugin
function attendance_tracking_init() {
    return AttendanceTracking::get_instance();
}

// Arrancar el plugin
attendance_tracking_init();