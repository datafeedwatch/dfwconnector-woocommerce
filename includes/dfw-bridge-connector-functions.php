<?php
/**
 * Functions used by plugins
 */
if (!class_exists('DFWBC_Dependencies')) {
  require_once 'class-dfwbridge-connector-dependencies.php';
}

/**
 * WC Detection
 */
if (!function_exists('is_dfwbc_required_plugins_active')) {
  function is_dfwbc_required_plugins_active()
  {
    return DFWBC_Dependencies::required_plugins_active_check();
  }
}

function woocommerce_version_error()
{
  ?>
    <div class="error notice">
      <p><?php printf(__('Requires WooCommerce version %s or later or WP-E-Commerce.'), DFWBC_MIN_WOO_VERSION); ?></p>
    </div>
  <?php
}