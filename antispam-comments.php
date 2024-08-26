<?php
/*
Plugin Name: AntiSpam Comments
Description: Evita que los comentarios con enlaces o palabras prohibidas sean enviados, oculta el campo "web" del formulario de comentarios y permite personalizar el mensaje de error.
Version: 1.8
Author: Juanma Aranda
Author URI: https://wpnovatos.com/
License: GPLv2 or later
*/

// Registrar los ajustes del plugin
function detectar_links_registrar_ajustes() {
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_titulo_error');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_mensaje_error');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_url_redireccion');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_palabras_prohibidas');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_mensaje_redireccion');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_considerar_links_spam');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_ocultar_campo_web');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_desactivar_comentarios');
    register_setting('detectar_links_ajustes_grupo', 'detectar_links_post_types_desactivar_comentarios');
}

// Añadir la página de ajustes al menú de administración dentro de "WPnovatos"
function detectar_links_menu() {
    // Crear el menú principal "WPnovatos" que no es clicable
    add_menu_page(
        'WPnovatos',       // Nombre del menú principal
        'WPnovatos',       // Título del menú
        'manage_options',  // Capacidad requerida
        'wpnovatos',       // Slug del menú
        '',                // No se usa función de contenido para que no sea clicable
        'https://juanmaaranda.com/wp-content/uploads/2024/08/wpnovatos-byn-20.png', // Icono del menú
        3                  // Posición en el menú
    );

    // Añadir el submenú "AntiSpam Comments"
    add_submenu_page(
        'wpnovatos',               // Slug del menú principal
        'AntiSpam Comments',       // Título de la página
        'AntiSpam Comments',       // Título del submenú
        'manage_options',          // Capacidad requerida
        'detectar-links-ajustes',  // Slug del submenú
        'detectar_links_pagina_ajustes' // Función que muestra la página de ajustes
    );

    // Remover el enlace duplicado "WPnovatos" del submenú
    remove_submenu_page('wpnovatos', 'wpnovatos');
}

// Añadir el enlace "Ajustes" en la página de listado de plugins
function detectar_links_ajustes_enlace($links) {
    $settings_link = '<a href="admin.php?page=detectar-links-ajustes">Ajustes</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Contenido de la página de ajustes
function detectar_links_pagina_ajustes() {
    $custom_post_types = get_post_types(array('public' => true), 'objects');
    ?>
    <div class="wrap">
        <h1>Ajustes del Plugin AntiSpam Comments</h1>
        <p>Este plugin permite bloquear comentarios que contengan enlaces o palabras prohibidas. Además, puedes personalizar los mensajes de error, la URL de redirección, y desactivar los comentarios.</p>
        <?php if (isset($_GET['settings-updated'])): ?>
            <div id="message" class="updated notice is-dismissible" style="border-left: 4px solid green;">
                <p><strong>Cambios guardados. Las restricciones serán aplicadas a partir de este momento.</strong></p>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            // Verificar el nonce antes de procesar los datos del formulario
            if (!isset($_POST['detectar_links_nonce']) || !wp_verify_nonce($_POST['detectar_links_nonce'], 'detectar_links_guardar_ajustes')) {
                wp_nonce_field('detectar_links_guardar_ajustes', 'detectar_links_nonce');
            }
            ?>
            <?php settings_fields('detectar_links_ajustes_grupo'); ?>
            <?php do_settings_sections('detectar_links_ajustes_grupo'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Título del mensaje de error</th>
                    <td>
                        <input type="text" name="detectar_links_titulo_error" value="<?php echo esc_attr(get_option('detectar_links_titulo_error')); ?>" />
                        <p class="description">Título que se mostrará en la página de error que ve el usuario cuando intenta enviar un mensaje que contiene SPAM.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mensaje de error</th>
                    <td>
                        <textarea name="detectar_links_mensaje_error" rows="5" cols="50"><?php echo esc_textarea(get_option('detectar_links_mensaje_error')); ?></textarea>
                        <p class="description">Explicación que verá el usuario en la página de error cuando intenta enviar un mensaje que contiene SPAM.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mensaje de redirección</th>
                    <td>
                        <input type="text" name="detectar_links_mensaje_redireccion" value="<?php echo esc_attr(get_option('detectar_links_mensaje_redireccion')); ?>" />
                        <p class="description">Mensaje que verá el usuario antes de ser sacado fuera de tu sitio.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">URL de redirección</th>
                    <td>
                        <input type="url" name="detectar_links_url_redireccion" value="<?php echo esc_url(get_option('detectar_links_url_redireccion')); ?>" />
                        <p class="description">Dirección web a la que quieres enviar al usuario 3 segundos después de mostrar el texto anterior.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Palabras prohibidas (separadas por comas)</th>
                    <td><textarea name="detectar_links_palabras_prohibidas" rows="5" cols="50"><?php echo esc_textarea(get_option('detectar_links_palabras_prohibidas')); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Considerar spam los comentarios con links</th>
                    <td><input type="checkbox" name="detectar_links_considerar_links_spam" value="1" <?php checked(1, get_option('detectar_links_considerar_links_spam'), true); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Ocultar el campo "web" del formulario de comentarios</th>
                    <td><input type="checkbox" name="detectar_links_ocultar_campo_web" value="1" <?php checked(1, get_option('detectar_links_ocultar_campo_web'), true); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Desactivar todos los comentarios</th>
                    <td><input type="checkbox" name="detectar_links_desactivar_comentarios" value="1" <?php checked(1, get_option('detectar_links_desactivar_comentarios'), true); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Desactivar comentarios en tipos de post personalizados</th>
                    <td>
                        <?php foreach ($custom_post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" name="detectar_links_post_types_desactivar_comentarios[<?php echo $post_type->name; ?>]" value="1" <?php checked(1, get_option("detectar_links_post_types_desactivar_comentarios")[$post_type->name] ?? 0, true); ?> />
                                <?php echo $post_type->labels->name; ?>
                            </label><br />
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field('detectar_links_guardar_ajustes', 'detectar_links_nonce'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Función para detectar enlaces y palabras prohibidas en los comentarios
function detectar_links_en_comentarios($commentdata) {
    // Verificar el nonce antes de procesar los datos del formulario
    if (!isset($_POST['detectar_links_nonce']) || !wp_verify_nonce($_POST['detectar_links_nonce'], 'detectar_links_guardar_ajustes')) {
        wp_nonce_field('detectar_links_guardar_ajustes', 'detectar_links_nonce');
    }

    $considerar_links_spam = get_option('detectar_links_considerar_links_spam', 0);
    $palabras_prohibidas = explode(',', get_option('detectar_links_palabras_prohibidas', ''));
    $comentario_contenido = $commentdata['comment_content'];

    // Detectar enlaces en el comentario
    if ($considerar_links_spam && preg_match('/https?:\/\/[^\s]+/', $comentario_contenido)) {
        wp_die(get_option('detectar_links_mensaje_error', 'Lo sentimos, pero tu comentario parece spam.'));
    }

    // Detectar palabras prohibidas en el comentario
    foreach ($palabras_prohibidas as $palabra) {
        if (stripos($comentario_contenido, trim($palabra)) !== false) {
            wp_die(get_option('detectar_links_mensaje_error', 'Lo sentimos, pero tu comentario contiene palabras no permitidas.'));
        }
    }

    return $commentdata;
}

// Desactivar comentarios si la opción está activada
function detectar_links_desactivar_comentarios() {
    $desactivar_comentarios = get_option('detectar_links_desactivar_comentarios', 0);

    if ($desactivar_comentarios) {
        // Desactivar comentarios en entradas
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        // Eliminar sección de comentarios
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    // Desactivar comentarios en tipos de post personalizados seleccionados
    $post_types = get_option('detectar_links_post_types_desactivar_comentarios', array());

    foreach ($post_types as $post_type => $value) {
        if ($value) {
            add_action('init', function() use ($post_type) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            });
        }
    }
}

// Ocultar el campo "web" en el formulario de comentarios si la opción está activada
function detectar_links_ocultar_campo_web() {
    $ocultar_campo_web = get_option('detectar_links_ocultar_campo_web', 0);

    if ($ocultar_campo_web) {
        add_filter('comment_form_default_fields', function($fields) {
            if (isset($fields['url'])) {
                unset($fields['url']);
            }
            return $fields;
        });
    }
}

// Ejecutar las funciones del plugin
add_action('admin_init', 'detectar_links_registrar_ajustes');
add_action('admin_menu', 'detectar_links_menu');
add_action('preprocess_comment', 'detectar_links_en_comentarios');
add_action('wp', 'detectar_links_desactivar_comentarios');
add_action('wp', 'detectar_links_ocultar_campo_web');

// Añadir enlace a la página de ajustes en el listado de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'detectar_links_ajustes_enlace');
?>
