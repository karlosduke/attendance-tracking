<?php
if (!defined('ABSPATH')) {
    exit;
}

// Procesar el formulario cuando se envía
if (isset($_POST['user_nonce']) && wp_verify_nonce($_POST['user_nonce'], 'save_user')) {
    try {
        // Validar campos requeridos
        $required_fields = array('nombre', 'apellidos', 'dni', 'email', 'id_centro');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception('Todos los campos marcados con * son requeridos.');
            }
        }

        // Validar formato de DNI (8 números y una letra)
        $dni = strtoupper(trim($_POST['dni']));
        if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
            throw new Exception('El formato del DNI no es válido. Debe contener 8 números y una letra.');
        }

        // Validar email
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            throw new Exception('El formato del email no es válido.');
        }

        // Validar teléfono (opcional, pero si se proporciona debe ser válido)
        $telefono = trim($_POST['telefono']);
        if (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
            throw new Exception('El formato del teléfono no es válido. Debe contener 9 números.');
        }

        // Verificar si el DNI ya existe
        if (DatabaseManager::get_instance()->dni_exists($dni)) {
            throw new Exception('Ya existe un usuario con este DNI.');
        }

        // Verificar si el email ya existe
        if (DatabaseManager::get_instance()->email_exists($email)) {
            throw new Exception('Ya existe un usuario con este email.');
        }

        // Preparar datos para guardar
        $user_data = array(
            'nombre' => sanitize_text_field($_POST['nombre']),
            'apellidos' => sanitize_text_field($_POST['apellidos']),
            'dni' => $dni,
            'email' => $email,
            'telefono' => $telefono,
            'id_centro' => intval($_POST['id_centro']),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql', true)
        );

        // Guardar usuario
        $user_id = DatabaseManager::get_instance()->save_user($user_data);

        if ($user_id) {
            // Registrar en el log
            error_log(sprintf(
                '[New User Created] ID: %d, DNI: %s, Created by: %s, Date: %s',
                $user_id,
                $dni,
                wp_get_current_user()->user_login,
                current_time('mysql', true)
            ));

            // Redirigir a la lista de usuarios con mensaje de éxito
            wp_redirect(add_query_arg(
                array(
                    'page' => 'attendance-users',
                    'message' => 'user-created'
                ),
                admin_url('admin.php')
            ));
            exit;
        } else {
            throw new Exception('Error al crear el usuario.');
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Obtener la lista de centros
$centros = DatabaseManager::get_instance()->get_centros();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Nuevo Usuario</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=attendance-users')); ?>" class="page-title-action">
        Volver al Listado
    </a>
    <hr class="wp-header-end">

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="post" action="" class="validate">
            <?php wp_nonce_field('save_user', 'user_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="nombre">Nombre <span class="description">(requerido)</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo isset($_POST['nombre']) ? esc_attr($_POST['nombre']) : ''; ?>"
                               class="regular-text" 
                               required>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="apellidos">Apellidos <span class="description">(requerido)</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="apellidos" 
                               name="apellidos" 
                               value="<?php echo isset($_POST['apellidos']) ? esc_attr($_POST['apellidos']) : ''; ?>"
                               class="regular-text" 
                               required>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="dni">DNI <span class="description">(requerido)</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="dni" 
                               name="dni" 
                               value="<?php echo isset($_POST['dni']) ? esc_attr($_POST['dni']) : ''; ?>"
                               class="regular-text" 
                               pattern="[0-9]{8}[A-Za-z]{1}"
                               title="Formato: 8 números seguidos de una letra"
                               required>
                        <p class="description">Formato: 12345678A</p>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="email">Email <span class="description">(requerido)</span></label>
                    </th>
                    <td>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                               class="regular-text" 
                               required>
                    </td>
                </tr>

                <tr class="form-field">
                    <th scope="row">
                        <label for="telefono">Teléfono</label>
                    </th>
                    <td>
                        <input type="tel" 
                               id="telefono" 
                               name="telefono" 
                               value="<?php echo isset($_POST['telefono']) ? esc_attr($_POST['telefono']) : ''; ?>"
                               class="regular-text" 
                               pattern="[0-9]{9}"
                               title="9 dígitos numéricos">
                        <p class="description">Formato: 123456789</p>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="centro">Centro <span class="description">(requerido)</span></label>
                    </th>
                    <td>
                        <select id="centro" name="id_centro" class="regular-text" required>
                            <option value="">Seleccione un centro</option>
                            <?php foreach ($centros as $centro): ?>
                                <option value="<?php echo esc_attr($centro->id); ?>"
                                        <?php selected(isset($_POST['id_centro']) ? $_POST['id_centro'] : '', $centro->id); ?>>
                                    <?php echo esc_html($centro->centro); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" 
                       name="submit" 
                       id="submit" 
                       class="button button-primary" 
                       value="Crear Usuario">
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Convertir DNI a mayúsculas automáticamente
    $('#dni').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Validación del formulario
    $('form').on('submit', function(e) {
        var dni = $('#dni').val();
        var telefono = $('#telefono').val();

        // Validar DNI
        if (!/^[0-9]{8}[A-Z]$/.test(dni)) {
            alert('El formato del DNI no es válido. Debe contener 8 números y una letra.');
            e.preventDefault();
            return false;
        }

        // Validar teléfono si se ha proporcionado
        if (telefono && !/^[0-9]{9}$/.test(telefono)) {
            alert('El formato del teléfono no es válido. Debe contener 9 números.');
            e.preventDefault();
            return false;
        }
    });
});
</script>