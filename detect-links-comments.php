<?php
/*
Plugin Name: Detectar Links en Comentarios
Description: Evita que los comentarios con enlaces o palabras prohibidas sean enviados, oculta el campo "web" del formulario de comentarios y permite personalizar el mensaje de error.
Version: 1.6
Author: Juanma Aranda
URL: https://wpnovatos.com
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
}

// Añadir la página de ajustes al menú de administración
function detectar_links_menu() {
    add_submenu_page(
        'edit-comments.php',
        'Ajustes SPAM',
        'Ajustes SPAM',
        'manage_options',
        'detectar-links-ajustes',
        'detectar_links_pagina_ajustes'
    );
}

// Contenido de la página de ajustes
function detectar_links_pagina_ajustes() {
    ?>
    <div class="wrap">
        <h1>Ajustes del Plugin SPAM</h1>
        <p>Este plugin permite bloquear comentarios que contengan enlaces o palabras prohibidas. Además, puedes personalizar los mensajes de error y la URL de redirección.</p>
        <?php if (isset($_GET['settings-updated'])): ?>
            <div id="message" class="updated notice is-dismissible" style="border-left: 4px solid green;">
                <p><strong>Cambios guardados. Las restricciones serán aplicadas a partir de este momento.</strong></p>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields('detectar_links_ajustes_grupo'); ?>
            <?php do_settings_sections('detectar_links_ajustes_grupo'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Título del mensaje de error</th>
                    <td><input type="text" name="detectar_links_titulo_error" value="<?php echo esc_attr(get_option('detectar_links_titulo_error')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mensaje de error</th>
                    <td><textarea name="detectar_links_mensaje_error" rows="5" cols="50"><?php echo esc_textarea(get_option('detectar_links_mensaje_error')); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mensaje de redirección</th>
                    <td><input type="text" name="detectar_links_mensaje_redireccion" value="<?php echo esc_attr(get_option('detectar_links_mensaje_redireccion')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">URL de redirección</th>
                    <td><input type="url" name="detectar_links_url_redireccion" value="<?php echo esc_url(get_option('detectar_links_url_redireccion')); ?>" /></td>
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
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Función para detectar enlaces y palabras prohibidas en los comentarios
function detectar_links_en_comentarios($commentdata) {
    $comentario = $commentdata['comment_content'];
    $palabras_prohibidas = explode(',', get_option('detectar_links_palabras_prohibidas'));
    $considerar_links_spam = get_option('detectar_links_considerar_links_spam');
    
    // Buscar enlaces en el comentario usando una expresión regular
    if (($considerar_links_spam && preg_match('/http[s]?:\/\/[^\s]+/', $comentario)) || detectar_palabras_prohibidas($comentario, $palabras_prohibidas)) {
        $titulo_error = esc_html(get_option('detectar_links_titulo_error', 'Comentario Bloqueado'));
        $mensaje_error = wp_kses_post(get_option('detectar_links_mensaje_error', 'El SPAM no está permitido en esta web.'));
        $mensaje_redireccion = esc_html(get_option('detectar_links_mensaje_redireccion', 'Serás redirigido en breve...'));
        $url_redireccion = esc_url(get_option('detectar_links_url_redireccion', 'https://google.com'));

        // Redirigir y mostrar mensaje de error con estilo personalizado
        $mensaje = '
        <div style="text-align: center; margin-top: 50px;">
            <h1 style="color: #d32f2f;">' . $titulo_error . '</h1>
            <p style="font-size: 18px;">' . $mensaje_error . '</p>
            <p>' . $mensaje_redireccion . '</p>
            <script>
                setTimeout(function(){ window.location.href = "' . $url_redireccion . '"; }, 3000);
            </script>
        </div>';

        wp_die($mensaje, $titulo_error, array('response' => 200));
    }

    return $commentdata;
}

// Función para detectar palabras prohibidas
function detectar_palabras_prohibidas($comentario, $palabras_prohibidas) {
    foreach ($palabras_prohibidas as $palabra) {
        if (stripos($comentario, trim($palabra)) !== false) {
            return true;
        }
    }
    return false;
}

// Función para ocultar el campo "web" del formulario de comentarios
function ocultar_campo_web_en_comentarios($fields) {
    $ocultar_campo_web = get_option('detectar_links_ocultar_campo_web');
    if ($ocultar_campo_web && isset($fields['url'])) {
        unset($fields['url']);
    }
    return $fields;
}

// Añadir filtros y acciones
add_action('admin_menu', 'detectar_links_menu');
add_action('admin_init', 'detectar_links_registrar_ajustes');
add_filter('preprocess_comment', 'detectar_links_en_comentarios');
add_filter('comment_form_default_fields', 'ocultar_campo_web_en_comentarios');

?>
