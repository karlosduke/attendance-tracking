<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<tr>
    <td data-colname="Fecha">
        <?php echo esc_html(date('d/m/Y', strtotime($record->Fecha))); ?>
    </td>
    <td data-colname="Hora">
        <?php echo esc_html($record->Hora); ?>
    </td>
    <td data-colname="Nombre">
        <?php echo esc_html($record->Nombre); ?>
    </td>
    <td data-colname="Apellidos">
        <?php echo esc_html($record->Apellidos); ?>
    </td>
    <td data-colname="DNI">
        <?php echo esc_html($record->DNI); ?>
    </td>
    <td data-colname="Centro">
        <?php echo esc_html($record->centro); ?>
    </td>
    <td data-colname="Firma">
        <?php if ($record->firma_url): ?>
            <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $record->firma_url); ?>" 
               target="_blank" 
               class="button button-small">
                <span class="dashicons dashicons-welcome-write-blog"></span>
                Ver firma
            </a>
        <?php endif; ?>
    </td>
    <td class="actions" data-colname="Acciones">
        <?php if (current_user_can('manage_options')): ?>
            <div class="row-actions">
                <span class="view">
                    <button type="button" 
                            class="button button-small view-details" 
                            data-record-id="<?php echo esc_attr($record->id); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        Detalles
                    </button>
                </span>
            </div>
        <?php endif; ?>
    </td>
</tr>