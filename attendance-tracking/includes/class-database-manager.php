<?php
if (!defined('ABSPATH')) {
    exit;
}

class DatabaseManager {
    private static $instance = null;
    
    private function __construct() {}

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de centros
        $sql_centros = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asistencia_th_centros (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            centro varchar(100) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql_centros);

        // Tabla de usuarios
        $sql_usuarios = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asistencia_th_usuarios (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            Nombre varchar(100) NOT NULL,
            Apellidos varchar(200) NOT NULL,
            DNI varchar(20) NOT NULL UNIQUE,
            email varchar(100) NOT NULL,
            telefono varchar(15),
            id_centro bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (id_centro) REFERENCES {$wpdb->prefix}asistencia_th_centros(id) 
            ON DELETE SET NULL
        ) $charset_collate;";
        
        dbDelta($sql_usuarios);

        // Tabla de registros
        $sql_registros = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asistencia_th_registros (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            id_user_asistencia bigint(20) NOT NULL,
            Fecha date NOT NULL,
            Hora time NOT NULL,
            id_centro bigint(20),
            firma_url varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (id_user_asistencia) REFERENCES {$wpdb->prefix}asistencia_th_usuarios(id) 
            ON DELETE CASCADE,
            FOREIGN KEY (id_centro) REFERENCES {$wpdb->prefix}asistencia_th_centros(id) 
            ON DELETE SET NULL
        ) $charset_collate;";
        
        dbDelta($sql_registros);
    }

    public function get_filtered_attendance($filters) {
        global $wpdb;

        $sql = "SELECT r.*, u.Nombre, u.Apellidos, u.DNI, c.centro 
                FROM {$wpdb->prefix}asistencia_th_registros r 
                JOIN {$wpdb->prefix}asistencia_th_usuarios u ON r.id_user_asistencia = u.id 
                LEFT JOIN {$wpdb->prefix}asistencia_th_centros c ON r.id_centro = c.id 
                WHERE 1=1";
        
        $params = array();

        if (!empty($filters['centro'])) {
            $sql .= " AND r.id_centro = %d";
            $params[] = $filters['centro'];
        }

        if (!empty($filters['usuario'])) {
            $sql .= " AND r.id_user_asistencia = %d";
            $params[] = $filters['usuario'];
        }

        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND r.Fecha >= %s";
            $params[] = $filters['fecha_inicio'];
        }

        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND r.Fecha <= %s";
            $params[] = $filters['fecha_fin'];
        }

        $sql .= " ORDER BY r.Fecha DESC, r.Hora DESC";

        return $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
    }

    // ... (otros métodos ya definidos anteriormente)

    public function save_attendance($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'asistencia_th_registros',
            array(
                'id_user_asistencia' => $data['user_id'],
                'Fecha' => current_time('Y-m-d'),
                'Hora' => current_time('H:i:s'),
                'id_centro' => $data['id_centro'],
                'firma_url' => $data['firma_url'],
                'created_at' => current_time('mysql', true)
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );
    }

    public function save_user($data) {
        global $wpdb;
        
        // Comenzar transacción
        $wpdb->query('START TRANSACTION');

        try {
            // Insertar en asistencia_th_usuarios
            $result = $wpdb->insert(
                $wpdb->prefix . 'asistencia_th_usuarios',
                array(
                    'Nombre' => $data['nombre'],
                    'Apellidos' => $data['apellidos'],
                    'DNI' => $data['dni'],
                    'email' => $data['email'],
                    'telefono' => isset($data['telefono']) ? $data['telefono'] : '',
                    'id_centro' => $data['id_centro'],
                    'created_at' => current_time('mysql', true)
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );

            if ($result === false) {
                throw new Exception('Error al insertar usuario');
            }

            $user_id = $wpdb->insert_id;

            // Insertar en amelia_users
            $result = $wpdb->insert(
                $wpdb->prefix . 'amelia_users',
                array(
                    'type' => 'customer',
                    'status' => 'visible',
                    'firstName' => $data['nombre'],
                    'lastName' => $data['apellidos'],
                    'email' => $data['email'],
                    'phone' => isset($data['telefono']) ? $data['telefono'] : ''
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                throw new Exception('Error al insertar cliente en Amelia');
            }

            $wpdb->query('COMMIT');
            return $user_id;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    public function update_user($user_id, $data) {
        global $wpdb;
        
        // Comenzar transacción
        $wpdb->query('START TRANSACTION');

        try {
            // Actualizar en asistencia_th_usuarios
            $result = $wpdb->update(
                $wpdb->prefix . 'asistencia_th_usuarios',
                array(
                    'Nombre' => $data['nombre'],
                    'Apellidos' => $data['apellidos'],
                    'email' => $data['email'],
                    'telefono' => isset($data['telefono']) ? $data['telefono'] : '',
                    'id_centro' => $data['id_centro']
                ),
                array('id' => $user_id),
                array('%s', '%s', '%s', '%s', '%d'),
                array('%d')
            );

            if ($result === false) {
                throw new Exception('Error al actualizar usuario');
            }

            // Actualizar en amelia_users
            $result = $wpdb->update(
                $wpdb->prefix . 'amelia_users',
                array(
                    'firstName' => $data['nombre'],
                    'lastName' => $data['apellidos'],
                    'email' => $data['email'],
                    'phone' => isset($data['telefono']) ? $data['telefono'] : ''
                ),
                array('email' => $data['email_original']),
                array('%s', '%s', '%s', '%s'),
                array('%s')
            );

            if ($result === false) {
                throw new Exception('Error al actualizar cliente en Amelia');
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
}