<?php
if (!defined('ABSPATH')) {
    exit;
}

$editing = isset($user) && $user;
$centros = DatabaseManager::get_instance()->get_centros();
?>

<div class="wrap">
    <h1><?php echo $editing ? 'Editar Usuario' : 'Nuevo Usuario'; ?></h1>

    <form method="post" id="user-form" class="user-form">
        <?php wp_nonce_field($editing ? 'edit_user' : 'create_user', 'user_nonce'); ?>
        
        <?php if ($editing): ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user->id); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="nombre">Nombre <span class="description">(requerido)</span></label>
                </th>
                <td>
                    <input name="nombre" 
                           type="text" 
                           id="nombre" 
                           value="<?php echo $editing ? esc_attr($user->Nombre) : ''; ?>" 
                           required>
                </td>
            </tr>
            
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="apellidos">Apellidos <span class="description">(requerido)</span></label>
                </th>
                <td>
                    <input name="apellidos" 
                           type="text" 
                           id="apellidos" 
                           value="<?php echo $editing ? esc_attr($user->Apellidos) : ''; ?>" 
                           required>
                </td>
            </tr>
            
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="dni">DNI <span class="description">(requerido)</span></label>
                </th>
                <td>
                    <input name="dni" 
                           type="text" 
                           id="dni" 
                           value="<?php echo $editing ? esc_attr($user->DNI) : ''; ?>"
                           <?php echo $editing ? 'readonly' : 'required'; ?>
                           pattern="[0-9]{8}[A-Za-z]{1}"
                           maxlength="9">
                    <p class="description">Formato: 8 números y una letra (ej: 12345678A)</p>
                </td>
            </tr>

            <tr class="form-field form-required">
                <th scope="row">
                    <label for="email">Email <span class="description">(requerido)</span></label>
                </th>
                <td>
                    <input name="email" 
                           type="email" 
                           id="email" 
                           value="<?php echo $editing ? esc_attr($user->email) : ''; ?>" 
                           required>
                </td>
            </tr>

            <tr class="form-field">
                <th scope="row">
                    <label for="telefono">Teléfono</label>
                </th>
                <td>
                    <input name="telefono" 
                           type="tel" 
                           id="telefono" 
                           value="<?php echo $editing ? esc_attr($user->telefono) : ''; ?>"
                           pattern="[0-9]{9}"
                           maxlength="9">
                    <p class="description">9 dígitos numéricos</p>
                </td>
            </tr>

            <tr class="form-field form-required">
                <th scope="row">
                    <label for="centro">Centro <span class="description">(requerido)</span></label>
                </th>
                <td>
                    <select name="id_centro" id="centro" required>
                        <option value="">Seleccione un centro</option>
                        <?php foreach ($centros as $centro): ?>
                            <option value="<?php echo esc_attr($centro->id); ?>" 
                                    <?php echo $editing && $user->id_centro == $centro->id ? 'selected' : ''; ?>>
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
                   value="<?php echo $editing ? 'Actualizar Usuario' : 'Crear Usuario'; ?>">
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=attendance-users')); ?>" 
               class="button button-secondary">
                Cancelar
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Convertir DNI a mayúsculas
    $('#dni').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Validación del formulario
    $('#user-form').on('submit', function(e) {
        const dni = $('#dni').val();
        const telefono = $('#telefono').val();

        if (!/^[0-9]{8}[A-Z]$/.test(dni)) {
            alert('El formato del DNI no es válido');
            e.preventDefault();
            return;
        }

        if (telefono && !/^[0-9]{9}$/.test(telefono)) {
            alert('El formato del teléfono no es válido');
            e.preventDefault();
            return;
        }
    });

    // Validación de DNI en tiempo real
    $('#dni').on('change', function() {
        const dni = $(this).val();
        if (!dni) return;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'validate_dni',
                nonce: '<?php echo wp_create_nonce("validate-dni-nonce"); ?>',
                dni: dni
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data.message);
                    $('#dni').val('').focus();
                }
            }
        });
    });
});
</script>