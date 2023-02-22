<?php
/*
Plugin Name: DataFeedWatch Connector
Description: DataFeedWatch enables merchants to optimize & manage product feeds on 2,000+ channels & marketplaces worldwide.
Version: 1.0.0
*/
defined('ABSPATH') or die("Cannot access pages directly.");
define('DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME', 'woocommerce_bridge_connector_is_custom');
define('DFWBC_BRIDGE_IS_INSTALLED', 'woocommerce_bridge_connector_is_installed');

if (!defined('DFWBC_STORE_BASE_DIR')) {
  define('DFWBC_STORE_BASE_DIR', ABSPATH);
}

if (!defined('DFWBC_MIN_WOO_VERSION')) {
  define('DFWBC_MIN_WOO_VERSION', '2.8.1');
}

if (!function_exists('is_dfwbc_required_plugins_active')) {
  require_once('includes/dfw-bridge-connector-functions.php');
}

if (!is_dfwbc_required_plugins_active()) {
  add_action( 'admin_notices', 'woocommerce_version_error');

  if (!function_exists('deactivate_plugins')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    deactivate_plugins(plugin_basename(__FILE__));
  }

  return;
}

include 'worker.php';
$worker = new BridgeConnector();

include_once $worker->bridgePath . $worker->configFilePath;

if (!function_exists('is_user_logged_in')) {
  require_once ABSPATH . WPINC . '/default-constants.php';
  wp_cookie_constants();
  require_once ABSPATH . WPINC . '/pluggable.php';
}

$storeKey = DFWBC_TOKEN;
$isCustom = get_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME);
$bridgeUrl = $worker->getBridgeUrl();

if (isset($_REQUEST['connector_action']) && is_user_logged_in()) {
  $action = $_REQUEST['connector_action'];
  $storeKey = BridgeConnector::generateStoreKey();
  switch ($action) {
    case 'installBridge':
      $data = [];
      update_option(DFWBC_BRIDGE_IS_INSTALLED, true);
      $status = $worker->updateToken($storeKey);

      if (!$status['success']) {
        break;
      }

      $status = $worker->installBridge();
      $data = [
        'storeKey' => $storeKey,
        'bridgeUrl' => $worker->getBridgeUrl()
      ];

      if ($status['success']) {
        update_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME, isset($status['custom']) ? $status['custom'] : false);
        update_option(DFWBC_BRIDGE_IS_INSTALLED, true);
      }
      break;
    case 'removeBridge':
      update_option(DFWBC_BRIDGE_IS_INSTALLED, false);
      $status = ['success' => true, 'message' => 'Bridge deleted'];
      $data   = [];
      delete_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME);
      delete_option(DFWBC_BRIDGE_IS_INSTALLED);
      break;
    case 'updateToken':
      $status = $worker->updateToken($storeKey);
      $data = ['storeKey' => $storeKey];
  }
  echo json_encode(['status' => $status, 'data' => $data]);
  exit();
}

function connector_plugin_action_links($links, $file)
{
  if ($file == plugin_basename(dirname(__FILE__) . '/connectorMain.php')) {
    $links[] = '<a href="' . admin_url('admin.php?page=connector-config') . '">' . __('Settings') . '</a>';
  }

  return $links;
}

add_filter('plugin_action_links', 'connector_plugin_action_links', 10, 2);


/**
 * Register routes.
 *
 * @since 1.5.0
 */
function rest_api_register_routes() {
  if (isset($GLOBALS['woocommerce']) || isset($GLOBALS['wpec'])) {
    require_once('includes/class-dfw-bridge-connector-rest-api-controller.php' );

    // v1
    $restApiController = new DFW_Bridge_Connector_V1_REST_API_Controller();
    $restApiController->register_routes();
  }
}

add_action( 'rest_api_init', 'rest_api_register_routes');

function connector_config()
{
  global $worker;
  include_once $worker->bridgePath . $worker->configFilePath;
  $storeKey = DFWBC_TOKEN;
  $isCustom = get_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME);
  $bridgeUrl = $worker->getBridgeUrl();

  wp_enqueue_style('connector-css', plugins_url('css/style.css', __FILE__));
  wp_enqueue_script('connector-js', plugins_url('js/scripts.js', __FILE__), array('jquery'));

  $showButton = 'install';
  if (get_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME)) {
    $showButton = 'uninstall';
  }

  $cartName = 'WooCommerce';
  $sourceCartName = 'WooCommerce';
  $sourceCartName = strtolower(str_replace(' ', '-', trim($sourceCartName)));
  $referertext = 'Connector: ' . $sourceCartName . ' to ' . $cartName . ' module';

  include 'settings.phtml';
  return true;
}

function connector_uninstall()
{
  delete_option(DFWBC_BRIDGE_IS_CUSTOM_OPTION_NAME);
  delete_option(DFWBC_BRIDGE_IS_INSTALLED);
}

function connector_deactivate()
{
  update_option(DFWBC_BRIDGE_IS_INSTALLED, false);
}

function connector_load_menu()
{
  add_submenu_page('plugins.php', __('DataFeedWatch Connector'), __('DataFeedWatch Connector'), 'manage_options', 'connector-config', 'connector_config');
}

register_uninstall_hook( __FILE__, 'connector_uninstall' );
register_deactivation_hook( __FILE__, 'connector_deactivate' );

add_action('admin_menu', 'connector_load_menu');
