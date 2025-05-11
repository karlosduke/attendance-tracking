<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$current_date = current_time('mysql');

// Obtener parámetros de filtro
$centro = isset($_GET['centro']) ? intval($_GET['centro']) : 0;
$usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : '';

// Obtener registros filtrados
$registros = DatabaseManager::get_instance()->get_filtered_attendance([
    'centro' => $centro,
    'usuario' => $usuario,
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin
]);

// Obtener listas para filtros
$centros = DatabaseManager::get_instance()->get_centros();
$usuarios = DatabaseManager::get_instance()->get_all_users();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Registros de Asistencia</h1>
    
    <!-- Botón exportar CSV -->
    <a href="<?php echo wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'export_attendance_csv',
                'centro' => $centro,
                'usuario' => $usuario,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ),
            admin_url('admin-ajax.php')
        ),
        'export-attendance-csv'
    ); ?>" class="page-title-action">
        Exportar a CSV
    </a>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" class="attendance-filters">
            <input type="hidden" name="page" value="attendance-system">
            
            <div class="alignleft actions">
                <select name="centro" id="filter-centro">
                    <option value="">Todos los centros</option>
                    <?php foreach ($centros as $c): ?>
                        <option value="<?php echo esc_attr($c->id); ?>" 
                                <?php selected($centro, $c->id); ?>>
                            <?php echo esc_html($c->centro); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="usuario" id="filter-usuario">
                    <option value="">Todos los usuarios</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo esc_attr($u->id); ?>" 
                                <?php selected($usuario, $u->id); ?>>
                            <?php echo esc_html($u->Nombre . ' ' . $u->Apellidos); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" 
                       name="fecha_inicio" 
                       id="filter-fecha-inicio" 
                       value="<?php echo esc_attr($fecha_inicio); ?>" 
                       placeholder="Fecha inicio">

                <input type="date" 
                       name="fecha_fin" 
                       id="filter-fecha-fin" 
                       value="<?php echo esc_attr($fecha_fin); ?>" 
                       placeholder="Fecha fin">

                <input type="submit" class="button" value="Filtrar">
            </div>
        </form>
    </div>

    <!-- Tabla de registros -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Hora</th>
                <th scope="col">Nombre</th>
                <th scope="col">Apellidos</th>
                <th scope="col">DNI</th>
                <th scope="col">Centro</th>
                <th scope="col">Firma</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registros): ?>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($registro->Fecha))); ?></td>
                        <td><?php echo esc_html($registro->Hora); ?></td>
                        <td><?php echo esc_html($registro->Nombre); ?></td>
                        <td><?php echo esc_html($registro->Apellidos); ?></td>
                        <td><?php echo esc_html($registro->DNI); ?></td>
                        <td><?php echo esc_html($registro->centro); ?></td>
                        <td>
                            <?php if ($registro->firma_url): ?>
                                <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $registro->firma_url); ?>" 
                                   target="_blank">
                                    Ver firma
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if (current_user_can('manage_options')): ?>
                                <button type="button" 
                                        class="button button-small view-details" 
                                        data-record-id="<?php echo esc_attr($registro->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No se encontraron registros.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para ver detalles -->
<div id="attendance-details-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Detalles del Registro</h2>
        <div id="modal-content"></div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.attendance-filters {
    display: flex;
    gap: 10px;
    margin: 15px 0;
}

.attendance-filters input[type="date"] {
    width: 150px;
}

@media screen and (max-width: 782px) {
    .attendance-filters {
        flex-direction: column;
    }
    
    .attendance-filters select,
    .attendance-filters input[type="date"] {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Manejador para ver detalles
    $('.view-details').on('click', function() {
        const recordId = $(this).data('record-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_attendance_details',
                nonce: '<?php echo wp_create_nonce("attendance-details-nonce"); ?>',
                record_id: recordId
            },
            success: function(response) {
                if (response.success) {
                    $('#modal-content').html(response.data.html);
                    $('#attendance-details-modal').show();
                }
            }
        });
    });

    // Cerrar modal
    $('.close').on('click', function() {
        $('#attendance-details-modal').hide();
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).is('#attendance-details-modal')) {
            $('#attendance-details-modal').hide();
        }
    });

    // Validar fechas
    $('#filter-fecha-fin').on('change', function() {
        const fechaInicio = $('#filter-fecha-inicio').val();
        const fechaFin = $(this).val();
        
        if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
            alert('La fecha final no puede ser anterior a la fecha inicial');
            $(this).val('');
        }
    });
});
</script>