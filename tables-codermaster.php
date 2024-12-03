<?php
/**
 * Plugin Name: Tables Codermaster
 * Description: Plugin para generar múltiples tablas dinámicas con nombres y descripciones personalizables.
 * Version: 1.7
 * Author: Leonardo Alexander Peñaranda Angarita
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Crear las tablas en la base de datos al activar el plugin
function tables_codermaster_create_db() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla para las tablas
    $tables_table = $wpdb->prefix . 'tables_codermaster';
    $sql_tables = "CREATE TABLE $tables_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        table_name VARCHAR(255) NOT NULL,
        shortcode VARCHAR(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabla para los archivos asociados a cada tabla
    $files_table = $wpdb->prefix . 'tables_codermaster_files';
    $sql_files = "CREATE TABLE $files_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        table_id mediumint(9) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        file_url TEXT NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_tables);
    dbDelta($sql_files);
}
register_activation_hook(__FILE__, 'tables_codermaster_create_db');

// Crear el menú en el panel de administración
function tables_codermaster_admin_menu() {
    add_menu_page(
        'Tables Codermaster',
        'Tables Codermaster',
        'manage_options',                  
        'tables-codermaster',              
        'tables_codermaster_admin_page',   
        'dashicons-media-spreadsheet',     
        25                                 
    );
}
add_action('admin_menu', 'tables_codermaster_admin_menu');

// Página de administración principal
function tables_codermaster_admin_page() {
    global $wpdb;
    $tables_table = $wpdb->prefix . 'tables_codermaster';

    // Agregar una nueva tabla
    if (isset($_POST['add_table'])) {
        $table_name = sanitize_text_field($_POST['table_name']);
        $shortcode = '[tables_codermaster id="' . uniqid() . '"]';

        $wpdb->insert(
            $tables_table,
            [
                'table_name' => $table_name,
                'shortcode' => $shortcode
            ]
        );

        echo '<div class="updated"><p>Tabla agregada con éxito.</p></div>';
    }

    // Obtener todas las tablas
    $tables = $wpdb->get_results("SELECT * FROM $tables_table");

    echo '<div class="wrap">';
    echo '<h1>Tables Codermaster</h1>';

    // Formulario para agregar una nueva tabla
    echo '<h2>Agregar Nueva Tabla</h2>';
    echo '<form method="post" style="margin-bottom: 20px;">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="table_name">Nombre de la Tabla</label></th>';
    echo '<td><input type="text" name="table_name" id="table_name" required></td>';
    echo '</tr>';
    echo '</table>';
    echo '<button type="submit" name="add_table" class="button button-primary">Agregar Tabla</button>';
    echo '</form>';

    // Mostrar tablas existentes
    echo '<h2>Tablas Existentes</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nombre de la Tabla</th><th>Shortcode</th><th>Acciones</th></tr></thead>';
    echo '<tbody>';

    foreach ($tables as $table) {
        echo '<tr>';
        echo '<td>' . esc_html($table->id) . '</td>';
        echo '<td>' . esc_html($table->table_name) . '</td>';
        echo '<td><code>' . esc_html($table->shortcode) . '</code></td>';
        echo '<td><a href="' . admin_url('admin.php?page=tables-codermaster-files&table_id=' . $table->id) . '" class="button">Gestionar Archivos</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Subpágina para gestionar archivos de una tabla específica
function tables_codermaster_manage_files_page() {
    global $wpdb;
    $files_table = $wpdb->prefix . 'tables_codermaster_files';
    $table_id = intval($_GET['table_id']);

    // Insertar un nuevo archivo
    if (isset($_POST['add_file'])) {
        $file_name = sanitize_text_field($_POST['file_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $file_url = esc_url_raw($_POST['file_url']);

        $wpdb->insert(
            $files_table,
            [
                'table_id' => $table_id,
                'file_name' => $file_name,
                'description' => $description,
                'file_url' => $file_url
            ]
        );

        echo '<div class="updated"><p>Archivo agregado con éxito.</p></div>';
    }

    // Guardar cambios en nombres y descripciones
    if (isset($_POST['save'])) {
        $id = intval($_POST['id']);
        $file_name = sanitize_text_field($_POST['file_name']);
        $description = sanitize_textarea_field($_POST['description']);

        $wpdb->update(
            $files_table,
            ['file_name' => $file_name, 'description' => $description],
            ['id' => $id]
        );

        echo '<div class="updated"><p>Archivo actualizado con éxito.</p></div>';
    }

    // Obtener todos los archivos de esta tabla
    $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM $files_table WHERE table_id = %d", $table_id));

    echo '<div class="wrap">';
    echo '<h1>Gestión de Archivos</h1>';

    // Formulario para agregar un nuevo archivo
    echo '<h2>Agregar Nuevo Archivo</h2>';
    echo '<form method="post" style="margin-bottom: 20px;">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="file_name">Nombre del Archivo</label></th>';
    echo '<td><input type="text" name="file_name" id="file_name" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="description">Descripción</label></th>';
    echo '<td><textarea name="description" id="description" rows="2" style="width: 100%;"></textarea></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="file_url">URL del Archivo</label></th>';
    echo '<td><input type="url" name="file_url" id="file_url" required></td>';
    echo '</tr>';
    echo '</table>';
    echo '<button type="submit" name="add_file" class="button button-primary">Agregar Archivo</button>';
    echo '</form>';

    // Mostrar archivos existentes
    echo '<h2>Archivos Existentes</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nombre del Archivo</th><th>Descripción</th><th>URL</th><th>Acciones</th></tr></thead>';
    echo '<tbody>';

    foreach ($files as $file) {
        echo '<tr>';
        echo '<td>' . esc_html($file->id) . '</td>';
        echo '<td>' . esc_html($file->file_name) . '</td>';
        echo '<td>' . esc_html($file->description) . '</td>';
        echo '<td>' . esc_url($file->file_url) . '</td>';
        echo '<td>';
        echo '<form method="post" style="display: inline-block;">';
        echo '<input type="hidden" name="id" value="' . esc_attr($file->id) . '">';
        echo '<input type="text" name="file_name" value="' . esc_attr($file->file_name) . '">';
        echo '<textarea name="description" rows="2" style="width: 100%;">' . esc_textarea($file->description) . '</textarea>';
        echo '<button type="submit" name="save" class="button button-primary">Guardar</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Agregar subpágina para gestionar archivos
function tables_codermaster_add_files_page() {
    add_submenu_page(
        null,
        'Gestionar Archivos',
        'Gestionar Archivos',
        'manage_options',
        'tables-codermaster-files',
        'tables_codermaster_manage_files_page'
    );
}
add_action('admin_menu', 'tables_codermaster_add_files_page');

// Shortcode para mostrar una tabla específica
function tables_codermaster_shortcode($atts) {
    global $wpdb;
    $files_table = $wpdb->prefix . 'tables_codermaster_files';

    // Obtener el ID de la tabla del shortcode
    $atts = shortcode_atts(['id' => ''], $atts);
    $table_id = intval($atts['id']);

    // Obtener los archivos de esta tabla
    $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM $files_table WHERE table_id = %d", $table_id));

    if (empty($files)) {
        return '<p>No hay archivos para mostrar.</p>';
    }

    // Generar la tabla
    $output = '<table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Nombre del Archivo</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Descripción</th>';
    $output .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Acción</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($files as $file) {
        $output .= '<tr>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($file->file_name) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($file->description) . '</td>';
        $output .= '<td style="border: 1px solid #ddd; padding: 8px;"><a href="' . esc_url($file->file_url) . '" target="_blank" style="padding: 8px 90px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 4px;">Descargar</a></td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    return $output;
}
add_shortcode('tables_codermaster', 'tables_codermaster_shortcode');
