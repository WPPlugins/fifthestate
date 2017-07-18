<?php
/*
This is basically a login page that allows the user to log into their FifthEstate account.
If they are logged in, this page will display their username; and one-way
synchronisation to FifthEstate is turned on.
If they are not logged in, this page will display a username textbox, password textbox and
'default category' dropdown menu that will allow them to log in; one-way synchronisation to
FifthEstate is turned off.
Note: 'one-way synchronisation' means that changes in posts on their site are being sent to
FifthEstate, but changes made on FifthEstate won't be sent back to their site
*/
namespace FifthEstate;

require_once 'utilities.php';

function handle_login_form() {
    if ( !is_email( $_POST['email'] ) ) {
        echo '<p>', _('Enter a valid email address.'), '</p>';
        return;
    }
    $app_state = get_option('fifthestate');
    $data = 'email=' . urlencode($_POST['email']) .
        '&password=' . urlencode($_POST['password']) .
        '&grant_type=password&scope=ingest';
    $response = json_decode($raw_response = curl_post(API_BASE_URL . '/tokens', $data, array('application/x-www-form-urlencoded')), true);

    if ( isset( $response['access_token'] ) ) {
        $app_state['logged_in'] = true;
        $app_state['token'] = $response['access_token'];
        if ($app_state['email'] !== $_POST['email']) {
            $app_state['email'] = $_POST['email'];
            $app_state['category'] = '';
        }
        update_option( 'fifthestate', $app_state );
    } else {
        if (isset($response['error'])) {
            //server returns an error
            _e('<p>' . htmlspecialchars($response['error_description']) . '.</p>');
        } else {
            _e('<p>Server Error</p>');
            if (JSON_ERROR_SYNTAX === json_last_error()) {
                _e('<p>' . htmlspecialchars($raw_response) . '</p>');
            }
        }
    }
}

function handle_logout_form() {
    $app_state = get_option('fifthestate');
    $authorization_header = 'Authorization: Bearer ' . $app_state['token'];

    $response = json_decode($raw_response = curl_post(API_BASE_URL . '/logout', '',
        array($authorization_header)), true);

    if (isset($response['success']) && $response['success']) {
        _e("<p>You've been logged out!</p>");
        $app_state['logged_in'] = false;
        $app_state['token'] = '';
        update_option( 'fifthestate', $app_state );
    } else {
        if (isset($response['error'])) {
            //server returns an error
            _e('<p>' . htmlspecialchars($response['error_description']) . '.</p>');
        } else {
            _e('<p>Server Error</p>');
            if (JSON_ERROR_SYNTAX === json_last_error()) {
                _e('<p>' . htmlspecialchars($raw_response) . '</p>');
            }
        }
    }
}

function handle_update_form() {
    $app_state = get_option('fifthestate');
    $app_state['category'] = $_POST['category_id'];
    update_option( 'fifthestate', $app_state );
}

function settings_page() {
    if (!current_user_can( 'manage_options' )) {
        wp_die( __( 'Access not granted. Please log into WordPress again.'));
    }

    echo '<div class="wrap">';
    echo '<h1>', _(APP_NAME), '</h1>';

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
        $form_descriptors = array(
            'log_in' => array(
                'nonce_field_action' => 'fifthestate-login',
                'nonce_field_name' => 'login_nonce',
                'processing_cb' => 'handle_login_form',
            ),
            'log_out' => array(
                'nonce_field_action' => 'fifthestate-logout-update',
                'nonce_field_name' => 'logout_update_nonce',
                'processing_cb' => 'handle_logout_form',
            ),
            'update_category' => array(
                'nonce_field_action' => 'fifthestate-logout-update',
                'nonce_field_name' => 'logout_update_nonce',
                'processing_cb' => 'handle_update_form',
            ),
        );
        // process the submission
        foreach ($form_descriptors as $action => $descriptor) {
            if(isset($_POST[$action])) {
                if(check_admin_referer($descriptor['nonce_field_action'], $descriptor['nonce_field_name'])) {
                    call_user_func(__NAMESPACE__ . '\\' . $descriptor['processing_cb']);
                } else {
                    echo '<p>', _('An error has occurred'), '</p>';
                }
            }
        }
    }

    // create display depending on the current application state
    $app_state = get_option('fifthestate');
    $logged_in = $app_state['logged_in'];

    if ($logged_in) {
        logged_in_view( $app_state['email'], $app_state['category'] );
    } else {
        logged_out_view();
    }
    echo '</div>';
}

function logged_out_view() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field( 'fifthestate-login', 'login_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="email"><?php _e( 'Email' ) ?></label></th>
                <td><input name="email" type="text" id="email" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="password"><?php _e( 'Password' ) ?></label></th>
                <td><input name="password" type="password" id="password" class="regular-text" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="log_in" class="button button-primary" value="Log in baby!" />
        </p>
    </form>
    <a target="_blank" href="<?= SITE_URL ?>">Register</a>
<?php
}

function logged_in_view( $email, $category ) {
    echo "<p>".__( "You are connected to FifthEstate as " )."<em>".$email."</em>.</p>" ;
    if ( empty( $category ) ) {
        _e( "<p><b>Please select a category before synchronisation begins.</b></p>" );
    } else {
        $category_name = json_decode( curl_get( API_BASE_URL . '/categories/' . $category, '' ) )->name;
        _e( "<p>The category you are currently posting to is <em>$category_name</em>.</p>" );
    }
    //GET data here because CORS is annoying
    $category_tree = curl_get( SITE_URL . '/data/categories.json', '' );
    $country_data = curl_get( SITE_URL . '/data/country_data.json', '' );
    ?>
    <div id="category_tree" style="display:none"><?php echo $category_tree ?></div>
    <div id="country_data" style="display:none"><?php echo $country_data ?></div>
    <form method="post" action="">
        <?php
        wp_nonce_field( 'fifthestate-logout-update', 'logout_update_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php _e( 'Change Category' ) ?></label></th>
                <td>
                    <span class="category-dropdowns"></span>
                </td>
                <td>
                    <input type="submit" name="update_category" class="button button-primary" value="Update" />
                </td>
            <tr>
        </table>
        <p class="submit">
            <input type="submit" name="log_out" class="button button-primary" value="Log out" />
        </p>
    </form>
    <script>
        jQuery('.category-dropdowns').categorySelector('<?=SITE_URL?>/data/categories.json', '<?=SITE_URL?>/data/country_data.json', '<?=$category?>');
    </script>
<?php
}

//enqueues style and script
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_script( 'script', plugins_url( 'js/category-helper.js', __FILE__ ), array(), APP_VERSION );
} );

//creates settings page
add_action( 'admin_menu', function() {
    add_options_page(
        APP_NAME,
        APP_NAME,
        'manage_options',
        'fifthestate.php',
        __NAMESPACE__ . '\\' . 'settings_page'
    );
} );
