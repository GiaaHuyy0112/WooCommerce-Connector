<?php
/*
Plugin Name: WooCommerce Connector
Description: Connects to another WooCommerce site and retrieves product information.
Version: 1.0
Author: Huy Nguyen
*/

// Add a submenu item under WooCommerce menu
add_action('admin_menu', 'wpcc_add_submenu');
function wpcc_add_submenu()
{
    add_submenu_page(
        'woocommerce',
        'WooCommerce Connector',
        'WooCommerce Connector',
        'manage_options',
        'woocommerce-connector',
        'wpcc_submenu_callback'
    );
}

// Submenu callback function
function wpcc_submenu_callback()
{
?>
    <div class="wrap">
        <h1>Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('woocommerce_connector');
            do_settings_sections('woocommerce_connector');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_url">API URL</label>
                    </th>
                    <td>
                        <input type="text" id="api_url" name="api_url" value="<?php echo esc_attr(get_option('api_url')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api_key">API Key</label>
                    </th>
                    <td>
                        <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr(get_option('api_key')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api_secret">API Secret</label>
                    </th>
                    <td>
                        <input type="text" id="api_secret" name="api_secret" value="<?php echo esc_attr(get_option('api_secret')); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
<?php

    $site_url = get_option('api_url');
    $consumer_key = get_option('api_key');
    $consumer_secret = get_option('api_secret');

    $api_url = $site_url . 'wp-json/wc/v3/';

    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
        )
    );

    if (!empty($site_url)) {
        $response = wp_remote_get($api_url . 'products', $args);

        if (is_wp_error($response)) {
            echo '<div class="error"><p>Failed to connect to the other site.</p></div>';
            return;
        }

        $products = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($products)) {
            echo '<div class="notice"><p>No products found.</p></div>';
            return;
        }

        echo '<h1>Products</h1>';

        foreach ($products as $product) {
            echo '<p>Name' . $product['name'] . ' - Price: ' . $product['price'] . '</p>';
        }



        foreach ($products as $product) {
            if ($product['type'] == "simple") {
                $newProduct = new WC_Product();
                $newProduct->set_name($product['name']);
                $newProduct->set_sku($product['sku']);
                $newProduct->set_price($product['price']);
                $newProduct->set_regular_price($product['regular_price']);
                $newProduct->save();
            }
        }
    }
}

function woocommerce_connector_register_settings()
{
    register_setting('woocommerce_connector', 'api_url');
    register_setting('woocommerce_connector', 'api_key');
    register_setting('woocommerce_connector', 'api_secret');
}
add_action('admin_init', 'woocommerce_connector_register_settings');
