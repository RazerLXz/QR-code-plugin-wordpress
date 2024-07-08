<?php
/*
Plugin Name: QR Generator
Description: Este plugin es para generar tantos QR como entradas fueron compradas.
Author: ADlive
Version: 1.0.0
*/

//-----------------------------------------------------------------------------------------
function my_plugin_activation() {
    require_once(plugin_dir_path(__FILE__) . '/install_db/install_db.php');
}
register_activation_hook(__FILE__, 'my_plugin_activation');
//-----------------------------------------------------------------------------------------
function my_plugin_uninstallation() {
    require_once(plugin_dir_path(__FILE__) . '/uninstall_db/uninstall_db.php');
}
register_uninstall_hook(__FILE__, 'my_plugin_uninstallation');
//-----------------------------------------------------------------------------------------
function myplugin_register_menu() {
    add_menu_page(
        'QR Generator',
        'QR Generator',
        'manage_options',
        'qr_generator',
        'qr_generator_admin_page',
        'dashicons-admin-generic',
        20
    );

    add_submenu_page(
        'qr_generator',           
        'Añadir Usuarios',              
        'Añadir Usuarios',               
        'manage_options',              
        'add_users_page',               
        'display_add_users_page'         
        );

    add_submenu_page(
            'qr_generator',
            'Visualización y Gestion de Usuarios',
            'Visualización y Gestion de Usuarios',
            'manage_options',
            'user_managament',
            'user_managament_page'
        );



    add_submenu_page(
            'qr_generator',
            'QR Generados',
            'QR Generados',
            'manage_options',
            'qr_generated',
            'display_qr_generated'
        );
    }

    
add_action('admin_menu', 'myplugin_register_menu');
//-----------------------------------------------------------------------------------------
function qr_generator_admin_page() {
    ?>
    <h1>QR Generator</h1>
    <br>
    <section>
        <div>
            <label>Añadir Usuarios</label>

        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=add_users_page'); ?>" class="button button-primary">Administrar</a>
        </div>
    </section>
    <br>
    <section>
        <div>
            <label>Modificar Usuario</label>

        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=modify_user'); ?>" class="button button-primary">Administrar</a>
        </div>
    </section>
    <br>
    <section>
        <div>
            <label>Eliminar Usuario</label>

        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=delete_user'); ?>" class="button button-primary">Administrar</a>
        </div>
    </section>
    <br>
    <section>
        <div>
            <label>Códigos QR Generados</label>

        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=qr_generated'); ?>" class="button button-primary">Administrar</a>
        </div>
    </section>
    <br>
    <?php
}

//-----------------------------------------------------------------------------------------
function enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_register_script('qr-portal-validation', plugin_dir_url(__FILE__) . 'js/qr-portal-validation.js');
    wp_localize_script('qr-portal-validation', 'frontendajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    wp_enqueue_script('qr-portal-validation');
    wp_enqueue_style('custom-styles', plugin_dir_url(__FILE__) . 'css/styles.css');
}

add_action('wp_enqueue_scripts', 'enqueue_scripts');

function generate_code() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'entrace_code_table';

    // Generar un código único
    do {
        $code = bin2hex(random_bytes(16));
        $code_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE code = %s", $code));

    } while ($code_exists);

    return $code;
}

//-----------------------------------------------------------------------------------------
add_action('woocommerce_thankyou', 'send_email');

function send_email($order_id) {
    global $wpdb;
    $quantities = array();
    $order = wc_get_order($order_id);
    $products_name = array();
    $items_prices = array();
    if (!$order->has_status('failed')) {
        $items = $order->get_items();

        foreach ($items as $item) {
            $products_name[] = $item->get_name();
            $quantities[] = $item->get_quantity();
            $items_prices[] = $item->get_total();
        }
 
    }

    $payment_method_title = $order->get_payment_method_title();

    $recipient = $order->get_billing_email();
    $client_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

        $qrCodeFiles = array();
        for ($j = 0; $j < count($products_name); $j++){
            $quantity = $quantities[$j];
            for ($i = 1; $i < $quantity + 1; $i++) {
                $codeExists = true;
                while ($codeExists){
                    $code = generate_code();

                    $entrace_code_table = $wpdb->prefix . 'entrace_code_table';

                    // Consulta para verificar si el código existe en la tabla
                    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $entrace_code_table WHERE code = %s", $code);
                    $codeExists = $wpdb->get_var($sql) > 0;

                }

                date_default_timezone_set('America/Bogota');

                // Get the current date
                $current_date = date('Y-m-d H:i:s');

                // Calculate the death day (current date + 10 days)
                $death_day = date('Y-m-d H:i:s', strtotime($current_date . ' + 10 days'));

                // Store the code in the database with creation_day and death_day
                $table = $wpdb->prefix . 'entrace_code_table';


                $result = $wpdb->insert($table, array('code' => $code, 'creation_day' => $current_date, 'death_day' => $death_day, 'product_name' => $products_name[$j], 'client_email' => $recipient, 'cliente_name' => $client_name, 'payment_method' => $payment_method_title));

                $qrCodeFiles[] = qr_gen($code, $products_name[$j], $i);
            }
        }
        // variables de los productos
        
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $date = $order->get_date_modified();
        $order_total = $order->get_total();
        
        $customer_name = $billing_first_name . ' ' . $billing_last_name;
        /////////////////////////////////////////////
        
        $quantity = 0;

        for($i = 0; $i < count($quantities); $i++){
            $quantity += $quantities[$i];
        }

        $pathToHtmlFile = __DIR__ . '/html/cuenta-bancaria.html';
        $message = file_get_contents($pathToHtmlFile);
        $message = str_replace('{{quantity}}', $quantity, $message);
        $message = str_replace('{{name}}', $customer_name, $message);
        $message = str_replace('{{fecha_compra}}', date('d/m/Y', strtotime($date)), $message);

        $product_list = '';
        for($i = 0; $i < count($products_name); $i++){
            $product_list .= "<tr style='height: 50px;'>";
            $product_list .= "<th style=\"font-weight: bold; font-family: 'Open Sans', Sans-serif; font-size: 16px; border: 1px solid #b2acac; text-align: left;\">".$products_name[$i]." x ".$quantities[$i]."</th>";            
            $product_list .= "<td style=\"font-weight: normal; font-family: 'Open Sans', Sans-serif; font-size: 12px; border: 1px solid #b2acac;\">$ ". number_format($items_prices[$i], 2, ',', '.')."</td>";
            $product_list .= "</tr>";
        }

        $message = str_replace('{{producto}}', $product_list, $message);

        $message = str_replace('{{monto}}', '$ ' . number_format($order_total, 2, ',', '.'), $message);
        $message = str_replace('{{metodo}}', $payment_method_title, $message);

        $subject = 'Multiparque';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Attach the QR code files to the email
        $attachments = $qrCodeFiles;

        // Send the email using the WordPress wp_mail function
        $result = wp_mail($recipient, $subject, $message, $headers, $attachments);

        foreach ($qrCodeFiles as $file) {
            unlink($file); // Delete the QR files
        }

    }

//-----------------------------------------------------------------------------------------
function qr_gen($code, $product_name, $index) {
    // Path where the QR code image will be saved
    //$qrCodeFile = 'wp-content/uploads/qrcodes/' . $code . '`.png`';

    $qrCodeFile = __DIR__ . '/qrcodes/' . $product_name . ' ' . $index . '.png';
    // Generate the QR code
    if (!defined('QR_MODE_NUL')) {
        include "lib/full/qrlib.php";
    }
    QRcode::png($code, $qrCodeFile, QR_ECLEVEL_L, 10);

    // Return the QR code file name to be stored in the array
    return $qrCodeFile;
}
//-----------------------------------------------------------------------------------------
function display_qr_validation_portal() {

    ?>

<!DOCTYPE html>   
<html>   
<head>  
<meta name="viewport" content="width=device-width, initial-scale=1">     
</head>    
<body>    
    <center><h1 id="login_text"> Login </h1></center>   
    <form method="post" id="form">  
        <div class="container">   
            <label>Email : </label>   
            <input type="text" placeholder="Enter Email" name="email" required>  
            <label>Password : </label>   
            <input type="password" placeholder="Enter Password" name="password" required>  
            <button type="submit" name="login_button">Login</button>   
 
        </div>   
    </form>     
</body>     
</html>  
<style>   
Body {  
  font-family: Calibri, Helvetica, sans-serif;  
  background-color: lightgray;  
}  
button {   
       background-color: lightgray;   
       width: 100%;  
        color: black;   
        padding: 15px;   
        margin: 10px 0px;   
        border: none;   
        cursor: pointer;   
         }   
 form {   
        border: 3px;   
    }   
 input[type=text], input[type=password] {   
        width: 100%;   
        margin: 8px 0;  
        padding: 12px 20px;   
        display: inline-block;   
        border: 2px solid gray;   
        box-sizing: border-box;   
    }  
 button:hover {   
        opacity: 0.7;   
    }     
     
 .container {   
        padding: 25px;   
        background-color: white;  
    }   
</style> 
    
    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_button'])) {
        handle_portal_login();
    }
}

function handle_portal_login() {
    global $wpdb;

    // Recuperar los datos del formulario
    $email = sanitize_text_field($_POST['email']);
    $email = trim($email);
    $password = sanitize_text_field($_POST['password']);
    $users_table = $wpdb->prefix . 'users_table';

    $user_data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $users_table WHERE email = %s", $email)
    );
    
    if ($user_data) {
        // Verificar la contraseña usando wp_check_password
        $password_match = wp_check_password($password, $user_data->password_hash);
    
        if ($password_match) {
    
            // El inicio de sesión fue exitoso, imprimir script jQuery para ocultar el formulario
            echo '<script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $("#form").hide();
                        $("#login_text").hide();
                    });
                  </script>';
    
            // Mostrar el contenido del portal de validación
            display_qr_validation_portal_content();
            return; // Salir de la función para evitar que se imprima el formulario nuevamente
        } else {
            // Contraseña incorrecta, mostrar un mensaje de error
            echo '<div class="error"><p>Error: ha ingresado un correo o contraseña errado</p></div>';
        }
    }
} 


function display_qr_validation_portal_content() {

    ?>

     <div id="qr-validation-portal">
    <script
        src="https://code.jquery.com/jquery-3.6.4.js"
        integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E="
        crossorigin="anonymous">
        </script>
        <h1>Portal de Validación</h1>
        <form id="qr_validation_form">
            <label for="qr_code_input">Código QR:</label>
            <input type="text" id="qr_code_input" name="qr_code_input" required>
            <input type="submit" value="Buscar" id="search_button" style="border-radius: 30px; border-color: #262161; color: white; font-weight: bold; background-color: #262161;">
        </form>
        <table id="validation_results_table" style="display: none;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Fecha de Creación</th>
                    <th>Fecha de Vencimiento</th>
                    <th>Estado</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div style="display: flex; justify-content: center; align-items: center;">
            <form id="qr_validate_form">
                <input type="hidden" id="qr_code_input_validate" name="qr_code_input_validate">
                <input type="submit" value="Redimir" id="validate_button" style="display:none; border-radius: 30px; border-color: #262161; color: white; font-weight: bold; background-color: #262161;">
            </form>
            <button id="clear_results" style="display:none; border-radius: 30px; border-color: #262161; color: white; font-weight: bold; background-color: #262161;">Limpiar</button>
        </div>
    </div>
    <script>
        <?php //include(plugin_dir_path(__FILE__) . 'js/qr-portal-validation.js'); ?>
    </script>
    
    <?php


}

// Shortcode to display the QR validation portal
function qr_validation_portal_shortcode() {
    ob_start();
    display_qr_validation_portal();
    return ob_get_clean();
}
add_shortcode('qr_portal', 'qr_validation_portal_shortcode');
//-----------------------------------------------------------------------------------------
add_action('wp_ajax_search_qr_code', 'search_qr_code');
add_action('wp_ajax_nopriv_search_qr_code', 'search_qr_code');

function search_qr_code() {
    global $wpdb;

    $response = array();

    if (isset($_POST['qrCode'])) {
        $qrCode = sanitize_text_field($_POST['qrCode']);

        $table_name = $wpdb->prefix . 'entrace_code_table';
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $qrCode);
        $result = $wpdb->get_row($sql);

        if ($result) {

            date_default_timezone_set('America/Bogota');

            // Check if the code is expired
            $currentDate = date('Y-m-d H:i:s');
            $expirationDateTime = strtotime($result->death_day);
            
            $response['product'] = $result->product_name;
            $response['code'] = $qrCode;
            $response['creationDate'] = date('d/m/Y', strtotime($result->creation_day));
            $response['expirationDate'] = date('d/m/Y', strtotime($result->death_day));
            $response['validate_day'] = date('d/m/Y', strtotime($result->validate_day));
            $response['isUsed'] = (bool) $result->used;
            $response['email'] = $result->client_email;
            $response['name'] = $result->client_name;

            if ($expirationDateTime > strtotime($currentDate)) {
                $response['isValid'] = true;

                // If the code is valid and not used, mark it as used in the database
                /*
                if (!$result->used) {
                    $wpdb->update($table_name, array('used' => 1), array('code' => $qrCode));
                }
                */
            } else {
                $response['isValid'] = false;
            }
        } else {
            $response['isValid'] = false;
        }
    } else {
        $response['error'] = 'No se proporcionó un código QR válido';
    }

    echo wp_json_encode($response);

    wp_die();
}
//-----------------------------------------------------------------------------------------
add_action('wp_ajax_validate_qr_code', 'validate_qr_code');
add_action('wp_ajax_nopriv_validate_qr_code', 'validate_qr_code');

function validate_qr_code() {
    global $wpdb;

    $response = array();

    if (isset($_POST['qrCode'])) {
        $qrCode = sanitize_text_field($_POST['qrCode']);

        $table_name = $wpdb->prefix . 'entrace_code_table';

        date_default_timezone_set('America/Bogota');

        // Get the current date
        $current_date = date('Y-m-d H:i:s');

        $wpdb->update($table_name, array('used' => 1), array('code' => $qrCode));

        $wpdb->update($table_name, array('validate_day' => $current_date), array('code' => $qrCode));

        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $qrCode);
        $result = $wpdb->get_row($sql);

        if ($result) {

            date_default_timezone_set('America/Bogota');

            // Check if the code is expired
            $currentDate = date('Y-m-d H:i:s');
            $expirationDateTime = strtotime($result->death_day);
            
            $response['product'] = $result->product_name;
            $response['code'] = $qrCode;
            $response['creationDate'] = date('d/m/Y', strtotime($result->creation_day));
            $response['expirationDate'] = date('d/m/Y', strtotime($result->death_day));
            $response['validate_day'] = date('d/m/Y', strtotime($result->validate_day));
            $response['isUsed'] = (bool) $result->used;
            $response['email'] = $result->client_email;
            $response['name'] = $result->cliente_name;

            if ($expirationDateTime > strtotime($currentDate)) {
                $response['isValid'] = true;

            } else {
                $response['isValid'] = false;
            }
        } else {
            $response['isValid'] = false;
        }
    } else {
        $response['error'] = 'No se proporcionó un código QR válido';
    }

    echo wp_json_encode($response);

    wp_die();
}

//--------------------------------------------------------------------------------
function display_add_users_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
        // Procesar el formulario si se ha enviado
        handle_add_user_form();
    }

    // Mostrar el formulario
    show_add_user_form();
}

function show_add_user_form() {
    ?>
    <div class="wrap">
        <h1>Añadir Usuarios</h1>
        <form method="post" action="">
            <label for="first_name">Nombre:</label>
            <input type="text" name="first_name" required><br>

            <label for="last_name">Apellido:</label>
            <input type="text" name="last_name" required><br>

            <label for="document_type">Tipo de Identificación:</label>
            <select name="document_type" required>
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="CE">Cédula de Extranjería</option>
                <option value="NIT">NIT</option>
                <option value="PPT">PPT</option>
            </select><br>

            <label for="document_number">Número de Identificación:</label>
            <input type="text" name="document_number" pattern="\d+" title="Debe ser un número" required><br>

            <label for="email">Email:</label>
            <input type="text" name="email" required><br>

            <label for="password_hash">Contraseña:</label>
            <input type="password" name="password" required><br>

            <input type="submit" name="add_user" class="button button-primary" value="Añadir Usuario">
        </form>
    </div>
        <?php
}

function handle_add_user_form() {
    global $wpdb;
    $users_table = $wpdb->prefix . 'users_table';

    // Validar los campos del formulario
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $id_type = sanitize_text_field($_POST['document_type']);
    $id_number = sanitize_text_field($_POST['document_number']);
    $email = sanitize_text_field($_POST['email']);
    $password = sanitize_text_field($_POST['password']);

    // Verificar si ya existe un usuario con el mismo correo
    $existing_user = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $users_table WHERE email = %s", $email));

    if ($existing_user) {
        // Muestra un mensaje de error específico si ya existe un usuario con el mismo correo
        echo '<div class="error"><p>Error: El correo ya existe.</p></div>';
        return;
    }

    $existing_document = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $users_table WHERE document_type = %s AND document_number = %s", $id_type, $id_number));

    if ($existing_document) {
        // Muestra un mensaje de error específico si ya existe un usuario con el mismo tipo y número de documento
        echo '<div class="error"><p>Error: El documento ya está registrado.</p></div>';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="error"><p>Error: El correo electrónico no es válido.</p></div>';
        return;
    }


    if (!check_password_requirements($password)) {
        // Muestra un mensaje de error si la contraseña no cumple con los requisitos
        echo '<div class="error"><p>Error: La contraseña no cumple con los requisitos.</p></div>';
        return;
    }



    // Agregar lógica para guardar el usuario en la base de datos
    $user_data = array(
        'document_type' => $id_type,
        'document_number' => $id_number,
        'email' => $email,
        'password_hash' => wp_hash_password($password),
        'first_name' => $first_name,
        'last_name' => $last_name,
    );

    $result = $wpdb->insert($users_table, $user_data);

    if ($result !== false) {
        // Muestra una alerta indicando que el usuario se ha añadido correctamente
        echo '<div class="updated"><p>Usuario añadido correctamente.</p></div>';
    } else {
        // Muestra un mensaje de error si la inserción falló
        echo '<div class="error"><p>Ocurrió un error al añadir el usuario.</p></div>';
    }
}


function check_password_requirements($password) {
    // Verificar que la contraseña cumple con los requisitos especificados
    $length_requirement = strlen($password) >= 8;
    $number_requirement = preg_match('/\d/', $password);
    $uppercase_requirement = preg_match('/[A-Z]/', $password);
    $special_character_requirement = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);

    if (!$length_requirement || !$number_requirement || !$uppercase_requirement || !$special_character_requirement) {
        return false;
    } else {

            return true;
        }
    


    return $length_requirement && $number_requirement && $uppercase_requirement && $special_character_requirement;
}



//-----------------------------------------------------------------------------------------

function user_managament_page() {
    $user_data = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['search_user'])) {
            $user_data = search_and_display_user();
        } elseif (isset($_POST['edit_user'])) {
            $user_data = get_user_data_by_id($_POST['user_id']); 
            show_modify_user_form($user_data);
        } elseif (isset($_POST['update_user'])) {
            handle_update_user_form();
        } elseif(isset($_POST['confirm_delete_user'])) {
            $user_data = get_user_data_by_id($_POST['user_id']);
            display_confirm_delete_user_form($user_data);
        }
    } else {
        display_search_user_form();
    }
}

function display_search_user_form() {
    ?>
    <div class="wrap">
        <h1>Buscar Usuario</h1>
        <form method="post" action="">
            <label for="document_type">Tipo de Documento:</label>
            <select name="document_type" required>
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="CE">Cédula de Extranjería</option>
                <option value="NIT">NIT</option>
                <option value="PPT">PPT</option>
            </select><br>

            <label for="document_number">Número de Documento:</label>
            <input type="text" name="document_number" pattern="\d+" title="Debe ser un número" required><br>

            <input type="submit" name="search_user" class="button button-primary" value="Buscar Usuario">
        </form>
    </div>
    <?php
}

function search_and_display_user() {
    global $wpdb;

    $id_type = sanitize_text_field($_POST['document_type']);
    $id_number = sanitize_text_field($_POST['document_number']);

    $users_table = $wpdb->prefix . 'users_table';
    $user_data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $users_table WHERE document_type = %s AND document_number = %s", $id_type, $id_number)
    );

    if ($user_data) {
        display_user($user_data);
    } else {
        echo '<div class="error"><p>No se encontró al usuario con los datos proporcionados.</p></div>';
        display_search_user_form();
    }

    return $user_data;
}

function get_user_data_by_id($user_id) {
    global $wpdb;
    $users_table = $wpdb->prefix . 'users_table';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $users_table WHERE user_id = %d", $user_id), OBJECT);
}

function display_user($user_data) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Administración de Usuarios</title>
    </head>
    <body>
        <h2>Administración de Usuarios</h2>

        <?php
        if ($user_data) {
        ?>
            <div>
                <h3>Datos del Usuario</h3>
                <p>ID: <?php echo $user_data->user_id; ?></p>
                <p>Nombre: <?php echo $user_data->first_name . ' ' . $user_data->last_name; ?></p>
                <p>Tipo de Documento: <?php echo $user_data->document_type; ?></p>
                <p>Número de Documento: <?php echo $user_data->document_number; ?></p>
                <p>Email: <?php echo $user_data->email; ?></p>

                <form method="post" action="">
                    <input type="hidden" name="user_id" value="<?php echo $user_data->user_id; ?>">
                    <button method="post" type="submit" name="edit_user">Editar</button>
                    <button method="post" type="submit" name="confirm_delete_user">Borrar</button>
                </form>
            </div>
        <?php
        } else {
            echo '<p>No se encontraron datos de usuario.</p>';
        }
        ?>
    </body>
    </html>
    <?php
    
}

function show_modify_user_form($user_data) {
    if ($user_data) {
        ?>
        <div class="wrap">
            <h1>Modificar Usuario</h1>
            <form method="post" action="">
                <p>ID: <?php echo $user_data->user_id; ?></p>
                <label for="first_name">Nombre:</label>
                <input type="text" name="first_name" value="<?php echo isset($user_data->first_name) ? esc_attr($user_data->first_name) : ''; ?>" required><br>

                <label for="last_name">Apellido:</label>
                <input type="text" name="last_name" value="<?php echo isset($user_data->last_name) ? esc_attr($user_data->last_name) : ''; ?>" required><br>

                <label for="email">Correo Electrónico:</label>
                <input type="email" name="email" value="<?php echo isset($user_data->email) ? esc_attr($user_data->email) : ''; ?>" required><br>

                <label for="password_hash">Contraseña:</label>
                <input type="password" name="password" required><br>

                <input type="hidden" name="user_id" value="<?php echo $user_data->user_id; ?>">
                <input type="submit" name="update_user" class="button button-primary" value="Actualizar Usuario">
            </form>
        </div>
        <?php
    } else {
        echo '<div class="error"><p>Error: No se encontraron datos de usuario para editar.</p></div>';
        display_search_user_form();
    }
}

function handle_update_user_form() {
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
        $user_id = sanitize_text_field($_POST['user_id']);
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

    if (!check_password_requirements($password)) {
            // Muestra un mensaje de error si la contraseña no cumple con los requisitos
        echo '<div class="error"><p>Error: La contraseña no cumple con los requisitos.</p></div>';
        display_search_user_form();
        return;
    }

    $password = wp_hash_password($password);
        // Validar los datos según tus necesidades antes de actualizar

        $users_table = $wpdb->prefix . 'users_table';
        $data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password_hash' => $password, // Asegúrate de manejar adecuadamente las contraseñas en tu aplicación
        );

        $where = array('user_id' => $user_id);

        $wpdb->update($users_table, $data, $where);

        echo '<div class="updated"><p>Usuario actualizado correctamente.</p></div>';
        

        display_search_user_form(); 
    }
}


function display_confirm_delete_user_form($user_data) {
    ?>

    <div class="wrap">
        <h1>Confirmar Eliminación de Usuario</h1>
        <p>¿Estás seguro de que deseas eliminar al siguiente usuario?</p>
        <ul>
                <li><strong>Nombre de usuario:</strong> <?php echo isset($user_data->email) ? $user_data->email : ''; ?></li>
                <li><strong>Nombre:</strong> <?php echo isset($user_data->first_name) ? $user_data->first_name : ''; ?></li>
                <li><strong>Apellido:</strong> <?php echo isset($user_data->last_name) ? $user_data->last_name : ''; ?></li>
                <li><strong>Tipo de Identificación:</strong> <?php echo isset($user_data->document_type) ? $user_data->document_type : ''; ?></li>
                <li><strong>Número de Identificación:</strong> <?php echo isset($user_data->document_number) ? $user_data->document_number : ''; ?></li>
        </ul>
        <form method="post" action="">
            <input type="hidden" name="user_id" value="<?php echo $user_data->user_id; ?>">
            <input type="submit" name="delete_user" class="button button-primary" value="Eliminar Usuario">
        </form>
    </div>
    <?php
}

// Verificar si se ha enviado el formulario de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    handle_delete_user_form();
}

// Función para manejar el formulario de eliminación de usuario
function handle_delete_user_form() {
    global $wpdb;

    // Obtener el ID del usuario a eliminar
    $user_id = sanitize_text_field($_POST['user_id']);

    // Realizar la eliminación del usuario en la base de datos
    $users_table = $wpdb->prefix . 'users_table';
    $where = array('user_id' => $user_id);

    $del = $wpdb->delete($users_table, $where);

    // Mostrar un mensaje indicando que el usuario ha sido eliminado
    echo '<div class="updated"><p>Usuario eliminado correctamente.</p></div>';
    
    if ($del) {

        display_search_user_form();
    }

    
}

//////////////////////////////////////////////////////////////////////////////


function display_qr_generated() {
    ?>
    <div class="wrap">
        <h1>Códigos QR Generados</h1>
        <form method="post" action="">

            <section>
                <div>
                    <h3>Buscar por Código:</h3>
                </div>
                <div>
                    <input type="text" name="qr_code">
                </div>
            </section>
            <br>
            <section>
                <div>
                    <h3>Rango de fechas:</h3>
                </div>
                <div>
                    <label for="start_date">Fecha de inicio:</label>
                </div>
                <div>
                    <input type="text" name="start_date" class="datepicker" placeholder="31/12/2030">
                </div>
                <div>
                    <label for="end_date">Fecha de fin:</label>
                </div>
                <div>
                    <input type="text" name="end_date" class="datepicker" placeholder="31/12/2030">
                </div>
            </section>
            <br>
            <section>
                <div>
                    <h3>Email:</h3>
                </div>
                <div>
                    <input type="text" name="email" class="datepicker">
                </div>
            </section>
            <br>
            <input type="submit" name="search_qr" class="button button-primary" value="Buscar">
        </form>

    </div>

    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_qr'])) {
        // Capture form data into $filters array
        $filters = array(
            'qr_code' => isset($_POST['qr_code']) ? sanitize_text_field($_POST['qr_code']) : '',
            'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '',
            'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '',
            'email' => isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '',
        );
        
        display_qr_table($filters);
    }
}


function display_qr_table($filters) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'entrace_code_table';

    // Build the base SQL query
    $sql = "SELECT * FROM $table_name WHERE 1 = 1";

    // Add filters to the SQL query based on user input
    if (!empty($filters['qr_code'])) {
        $sql .= $wpdb->prepare(" AND code = %s", $filters['qr_code']);
    }

    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $start_date = date('Y-m-d', strtotime(str_replace('/', '-', $filters['start_date'])));
        $end_date = date('Y-m-d', strtotime(str_replace('/', '-', $filters['end_date'])));
        $sql .= $wpdb->prepare(" AND creation_day BETWEEN %s AND %s", $start_date, $end_date);
    }
    
    if (!empty($filters['email'])) {
        $sql .= $wpdb->prepare(" AND client_email = %s", $filters['email']);
    }

    // Execute the SQL query
    $qr_codes = $wpdb->get_results($sql);

    // Display the results in an HTML table
    echo '<table class="widefat">';
    echo '<thead><tr><th>Código</th><th>Estado</th><th>Fecha de Creación</th><th>Fecha de Vencimiento</th><th>Fecha de Validación</th><th>Producto</th><th>Método de pago</th><th>Email</th><th>Nombre</th></tr></thead>';
    echo '<tbody>';

    foreach ($qr_codes as $qr_code) {
        echo '<tr>';
        //echo '<td><a href="javascript:void(0);" onclick="showQrDetails(\'' . esc_js($qr_code->code) . '\');">' . esc_html($qr_code->code) . '</a></td>';
        echo '<td>' . esc_html($qr_code->code) . '</td>';
        if($qr_code->used){
            echo '<td>' . esc_html("Redimido") . '</td>';
        }
        else{
            echo '<td>' . esc_html("No Redimido") . '</td>';
        }
        echo '<td>' . esc_html(date('d/m/Y', strtotime($qr_code->creation_day))) . '</td>';
        echo '<td>' . esc_html(date('d/m/Y', strtotime($qr_code->death_day))) . '</td>';
        if($qr_code->used){
            echo '<td>' . esc_html(date('d/m/Y', strtotime($qr_code->validate_day))) . '</td>';
        }
        else{
            echo '<td>' . esc_html('-') . '</td>';
        }
        echo '<td>' . esc_html($qr_code->product_name) . '</td>';
        echo '<td>' . esc_html($qr_code->payment_method) . '</td>';
        echo '<td>' . esc_html($qr_code->client_email) . '</td>';
        echo '<td>' . esc_html($qr_code->cliente_name) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

//-----------------------------------------------------------------------------------------------------------------------------

