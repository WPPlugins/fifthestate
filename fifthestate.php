<?php
/*
Plugin Name: FifthEstate
Description: Connect your WordPress.org site to FifthEstate.
Version: 1.1.1
Author: FifthEstate
Author URI: https://profiles.wordpress.org/fifthestate
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
namespace FifthEstate;
const APP_NAME = 'FifthEstate';
const APP_VERSION = '1.1.1';

// This is an optional local configuration file that you can create.
// It is meant for FifthEstate devs.
// Here is an example configuration:
//     <?php
//     namespace FifthEstate;
//     // http://blog.teamtreehouse.com/how-to-debug-in-php
//     ini_set('display_errors', 'On');
//     error_reporting(E_ALL | E_STRICT);
//
//     // http://stackoverflow.com/a/3193704/1796894
//     const SITE_URL = 'http://localhost';
//     const API_BASE_URL = 'http://localhost:4238';
if (file_exists(__DIR__ . '/local-config.php'))
    include_once 'local-config.php';

if (!defined(__NAMESPACE__ . '\\' . 'SITE_URL'))
    define(__NAMESPACE__ . '\\' . 'SITE_URL', 'https://fifthestate.com');

if (!defined(__NAMESPACE__ . '\\' . 'API_BASE_URL'))
    define(__NAMESPACE__ . '\\' . 'API_BASE_URL', 'https://fifthestate.com/api');

require_once 'post-handler.php';
require_once 'settings.php';

$default_app_state = array(
    'logged_in' => false,
    'token' => '',
    'email' => '',
    'category' => '',
);
add_option( 'fifthestate', $default_app_state );
