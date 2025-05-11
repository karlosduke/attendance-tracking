<?php
if (!defined('ABSPATH')) {
    exit;
}

class Admin_Menu {
    private static $instance = null;

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_menus() {
        // Menú principal
        add_menu_page(
            'Sistema de Asistencia',
            'Asistencia',
            'manage_options',
            'attendance-system',
            array($this, 'render_attendance_list'),
            'dashicons-clipboard',
            30
        );

        // Submenús
        add_submenu_page(
            'attendance-system',
            'Registros de Asistencia',
            'Registros',
            'manage_options',
            'attendance-system',
            array($this, 'render_attendance_list')
        );

        add_submenu_page(
            'attendance-system',
            'Nuevo Usuario',
            'Nuevo Usuario',
            'manage_options',
            'attendance-add-user',
            array($this, 'render_new_user')
        );

        add_submenu_page(
            'attendance-system',
            'Usuarios',
            'Usuarios',
            'manage_options',
            'attendance-users',
            array($this, 'render_users_list')
        );

        add_submenu_page(
            'attendance-system',
            'Registro Manual',
            'Registro Manual',
            'manage_options',
            'attendance-manual',
            array($this, 'render_manual_attendance')
        );

        add_submenu_page(
            'attendance-system',
            'Centros',
            'Centros',
            'manage_options',
            'attendance-centers',
            array($this, 'render_centers')
        );
    }

    public function render_attendance_list() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        require_once ATTENDANCE_PLUGIN_DIR . 'admin/views/attendance-list.php';
    }

    public function render_new_user() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        require_once ATTENDANCE_PLUGIN_DIR . 'admin/views/new-user.php';
    }

    public function render_users_list() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        require_once ATTENDANCE_PLUGIN_DIR . 'admin/views/users-list.php';
    }

    public function render_manual_attendance() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        require_once ATTENDANCE_PLUGIN_DIR . 'admin/views/manual-attendance.php';
    }

    public function render_centers() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        require_once ATTENDANCE_PLUGIN_DIR . 'admin/views/centers.php';
    }
}