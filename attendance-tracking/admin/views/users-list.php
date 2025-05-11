<?php
if (!defined('ABSPATH')) {
    exit;
}

// Procesar eliminación de usuario si se solicita
if (isset($_GET['action']) && $_GET['action'] === 'delete' && 
    isset($_GET['user_id']) && isset($_GET['_wpnonce'])) {
    
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_user_' . $_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        
        if (DatabaseManager::get_instance()->user_has_attendance($user_id)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 'No se puede eliminar el usuario porque tiene registros de asistencia asociados.</p></div>';
        } else {
            $result = DatabaseManager::get_instance()->delete_user($user_id);
            echo '<div class="notice ' . ($result ? 'notice-success' : 'notice-error') . ' is-dismissible"><p>' . 
                 ($result ? 'Usuario eliminado correctamente.' : 'Error al eliminar el usuario.') . '</p></div>';
        }
    }
}

// Parámetros de paginación y ordenamiento
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'id';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Obtener datos paginados
$total_users = DatabaseManager::get_instance()->get_total_users($search);
$total_pages = ceil($total_users / $per_page);
$users = DatabaseManager::get_instance()->get_users_paginated($per_page, $current_page, $orderby, $order, $search);

// Función para generar enlaces de ordenamiento
function get_sort_link($column, $current_orderby, $current_order) {
    $new_order = ($current_orderby === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $args = array_merge($_GET, [
        'orderby' => $column,
        'order' => $new_order
    ]);
    $url = add_query_arg($args);
    $class = $current_orderby === $column ? ' sorted ' . strtolower($current_order) : ' sortable asc';
    
    $column_labels = [
        'id' => 'ID',
        'Nombre' => 'Nombre',
        'Apellidos' => 'Apellidos',
        'DNI' => 'DNI',
        'email' => 'Email',
        'telefono' => 'Teléfono'
    ];
    
    return sprintf(
        '<a href="%s" class="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
        esc_url($url),
        esc_attr($class),
        esc_html($column_labels[$column])
    );
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Listado de Usuarios</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=attendance-add-user')); ?>" class="page-title-action">
        Añadir Nuevo Usuario
    </a>

    <!-- Filtros y búsqueda -->
    <form method="get">
        <input type="hidden" name="page" value="attendance-users">
        <p class="search-box">
            <label class="screen-reader-text" for="user-search-input">Buscar usuarios:</label>
            <input type="search" 
                   id="user-search-input" 
                   name="s" 
                   value="<?php echo esc_attr($search); ?>"
                   placeholder="Buscar por nombre, DNI o email...">
            <input type="submit" id="search-submit" class="button" value="Buscar">
        </p>
    </form>

    <!-- Tabla Responsive -->
    <div class="wp-list-table-wrap">
        <table class="wp-list-table widefat fixed striped users-list">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-id">
                        <?php echo get_sort_link('id', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-nombre">
                        <?php echo get_sort_link('Nombre', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-apellidos">
                        <?php echo get_sort_link('Apellidos', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-dni">
                        <?php echo get_sort_link('DNI', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-email">
                        <?php echo get_sort_link('email', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-telefono">
                        <?php echo get_sort_link('telefono', $orderby, $order); ?>
                    </th>
                    <th scope="col" class="manage-column column-centro">Centro</th>
                    <th scope="col" class="manage-column column-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && !empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-colname="ID">
                                <?php echo esc_html($user->id); ?>
                            </td>
                            <td data-colname="Nombre">
                                <?php echo esc_html($user->Nombre); ?>
                            </td>
                            <td data-colname="Apellidos">
                                <?php echo esc_html($user->Apellidos); ?>
                            </td>
                            <td data-colname="DNI">
                                <?php echo esc_html($user->DNI); ?>
                            </td>
                            <td data-colname="Email">
                                <?php echo esc_html($user->email); ?>
                            </td>
                            <td data-colname="Teléfono">
                                <?php echo esc_html($user->telefono); ?>
                            </td>
                            <td data-colname="Centro">
                                <?php echo esc_html($user->centro); ?>
                            </td>
                            <td data-colname="Acciones" class="actions">
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(add_query_arg(
                                            array(
                                                'page' => 'attendance-edit-user',
                                                'user_id' => $user->id
                                            ),
                                            admin_url('admin.php')
                                        )); ?>">
                                            Editar
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo esc_url(wp_nonce_url(
                                            add_query_arg(
                                                array(
                                                    'action' => 'delete',
                                                    'user_id' => $user->id
                                                )
                                            ),
                                            'delete_user_' . $user->id
                                        )); ?>"
                                           onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?');"
                                           class="submitdelete">
                                            Eliminar
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url(add_query_arg(
                                            array(
                                                'page' => 'attendance-user-records',
                                                'user_id' => $user->id
                                            ),
                                            admin_url('admin.php')
                                        )); ?>">
                                            Ver Asistencias
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-items">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-id">ID</th>
                    <th scope="col" class="manage-column column-nombre">Nombre</th>
                    <th scope="col" class="manage-column column-apellidos">Apellidos</th>
                    <th scope="col" class="manage-column column-dni">DNI</th>
                    <th scope="col" class="manage-column column-email">Email</th>
                    <th scope="col" class="manage-column column-telefono">Teléfono</th>
                    <th scope="col" class="manage-column column-centro">Centro</th>
                    <th scope="col" class="manage-column column-actions">Acciones</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s elemento', '%s elementos', $total_users),
                        number_format_i18n($total_users)
                    ); ?>
                </span>
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'add_args' => array_filter([
                        's' => $search,
                        'orderby' => $orderby !== 'id' ? $orderby : false,
                        'order' => $order !== 'DESC' ? $order : false,
                    ])
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Estilos responsive */
@media screen and (max-width: 782px) {
    .users-list {
        display: block;
    }

    .users-list thead {
        display: none;
    }

    .users-list tbody,
    .users-list tr,
    .users-list td {
        display: block;
    }

    .users-list tr {
        position: relative;
        margin-bottom: 1em;
        border: 1px solid #ccd0d4;
    }

    .users-list td {
        padding: 8px;
        border: none;
    }

    .users-list td:before {
        content: attr(data-colname);
        font-weight: bold;
        float: left;
        width: 30%;
        margin-right: 10px;
    }

    .users-list td.actions {
        background: #f8f9fa;
    }

    .users-list .row-actions {
        position: relative;
        left: 0;
        padding-left: 30%;
    }

    .users-list td:not(.check-column) {
        text-align: left;
        clear: both;
        border-bottom: 1px solid #eee;
    }

    .users-list td:last-child {
        border-bottom: none;
    }
}

/* Estilos generales */
.wp-list-table-wrap {
    position: relative;
    overflow-x: auto;
    margin-top: 1em;
}

.row-actions {
    visibility: visible;
    padding: 2px 0;
}

.users-list tr:hover .row-actions {
    visibility: visible;
}

.submitdelete {
    color: #b32d2e;
}

.submitdelete:hover {
    color: #dc3232;
}
</style>