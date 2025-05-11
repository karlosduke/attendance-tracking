<?php
if (!defined('ABSPATH')) {
    exit;
}

// Procesar el formulario de agregar/editar centro
if (isset($_POST['center_nonce']) && wp_verify_nonce($_POST['center_nonce'], 'save_center')) {
    if (isset($_POST['centro']) && !empty($_POST['centro'])) {
        $centro_name = sanitize_text_field($_POST['centro']);
        
        // Si estamos editando
        if (isset($_POST['centro_id']) && !empty($_POST['centro_id'])) {
            $result = DatabaseManager::get_instance()->update_centro(
                intval($_POST['centro_id']),
                $centro_name
            );
            $message = $result ? 'Centro actualizado correctamente.' : 'Error al actualizar el centro.';
        } 
        // Si estamos agregando uno nuevo
        else {
            $result = DatabaseManager::get_instance()->register_centro($centro_name);
            $message = $result ? 'Centro agregado correctamente.' : 'Error al agregar el centro.';
        }
        
        // Mostrar mensaje de éxito o error
        if (isset($message)) {
            echo '<div class="notice ' . ($result ? 'notice-success' : 'notice-error') . ' is-dismissible"><p>' . 
                 esc_html($message) . '</p></div>';
        }
    }
}

// Procesar eliminación de centro
if (isset($_GET['action']) && $_GET['action'] === 'delete' && 
    isset($_GET['centro_id']) && isset($_GET['_wpnonce'])) {
    
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_centro_' . $_GET['centro_id'])) {
        $centro_id = intval($_GET['centro_id']);
        
        // Verificar si el centro tiene registros asociados
        if (DatabaseManager::get_instance()->centro_has_records($centro_id)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 'No se puede eliminar el centro porque tiene registros asociados.</p></div>';
        } else {
            $result = DatabaseManager::get_instance()->delete_centro($centro_id);
            echo '<div class="notice ' . ($result ? 'notice-success' : 'notice-error') . ' is-dismissible"><p>' . 
                 ($result ? 'Centro eliminado correctamente.' : 'Error al eliminar el centro.') . '</p></div>';
        }
    }
}

// Obtener centro para edición si está en modo edición
$editing_centro = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['centro_id'])) {
    $editing_centro = DatabaseManager::get_instance()->get_centro(intval($_GET['centro_id']));
}

?>
<div class="wrap attendance-admin">
    <h1><?php echo $editing_centro ? 'Editar Centro' : 'Gestión de Centros'; ?></h1>
    
    <div class="attendance-form">
        <form method="post" action="">
            <?php wp_nonce_field('save_center', 'center_nonce'); ?>
            
            <?php if ($editing_centro): ?>
                <input type="hidden" name="centro_id" value="<?php echo esc_attr($editing_centro->id); ?>">
            <?php endif; ?>
            
            <div class="form-field">
                <label for="centro">Nombre del Centro:</label>
                <input type="text" 
                       id="centro" 
                       name="centro" 
                       value="<?php echo $editing_centro ? esc_attr($editing_centro->centro) : ''; ?>"
                       required>
            </div>
            
            <button type="submit" class="button button-primary">
                <?php echo $editing_centro ? 'Actualizar Centro' : 'Añadir Centro'; ?>
            </button>
            
            <?php if ($editing_centro): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=attendance-centers')); ?>" 
                   class="button">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped attendance-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id">ID</th>
                <th scope="col" class="manage-column column-centro">Centro</th>
                <th scope="col" class="manage-column column-actions">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $centros = DatabaseManager::get_instance()->get_centros();
            if ($centros && !empty($centros)):
                foreach ($centros as $centro):
                    ?>
                    <tr>
                        <td><?php echo esc_html($centro->id); ?></td>
                        <td><?php echo esc_html($centro->centro); ?></td>
                        <td class="actions">
                            <a href="<?php echo esc_url(add_query_arg(
                                array(
                                    'action' => 'edit',
                                    'centro_id' => $centro->id
                                )
                            )); ?>" 
                               class="button button-small">
                                <span class="dashicons dashicons-edit"></span>
                                Editar
                            </a>
                            
                            <a href="<?php echo esc_url(wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action' => 'delete',
                                        'centro_id' => $centro->id
                                    )
                                ),
                                'delete_centro_' . $centro->id
                            )); ?>"
                               class="button button-small button-link-delete" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este centro?');">
                                <span class="dashicons dashicons-trash"></span>
                                Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                endforeach;
            else:
                ?>
                <tr>
                    <td colspan="3">No hay centros registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.attendance-form {
    max-width: 500px;
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-field input[type="text"] {
    width: 100%;
}

.actions {
    display: flex;
    gap: 10px;
}

.button-link-delete {
    color: #dc3232;
}

.button-link-delete:hover {
    color: #dc3232;
    border-color: #dc3232;
}

.dashicons {
    vertical-align: middle;
    margin-right: 3px;
}
</style>