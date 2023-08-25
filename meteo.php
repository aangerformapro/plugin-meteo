<?php

/*
 * @package Meteo
 * @version 1.0.0
 *
 * Plugin Name: meteo
 * Description: Plugin Météo
 * Author: Aymeric Anger
 * Version: 1.0.0
 */

class OpenWeatherMeteoWidget
{
    public static int $widgetCount = 0;

    public static function loadScript()
    {
        static $loaded = false;

        if ( ! $loaded)
        {
            $loaded = true;
            echo '<script src="//openweathermap.org/themes/openweathermap/assets/vendor/owm/js/d3.min.js" defer></script>';
            echo '<script src="//openweathermap.org/themes/openweathermap/assets/vendor/owm/js/weather-widget-generator.js" defer></script>';
        }
    }
}

register_activation_hook(__FILE__, 'meteo_table_create');
register_deactivation_hook(__FILE__, 'meteo_table_drop');

add_action('admin_menu', 'api_key_manager_menu');
add_action('admin_init', 'api_key_settings_init');

add_shortcode('meteo', 'meteo_shortcode');

function meteo_table_create()
{
    global $wpdb;

    $prefix = $wpdb->prefix;

    foreach ([
        "CREATE TABLE IF NOT EXISTS {$prefix}shortcode (" .
        'id INT(6) NOT NULL AUTO_INCREMENT,' .
        'shortcode VARCHAR(30),' .
        'latitude FLOAT NULL,' .
        'longitude FLOAT NULL,' .
        'cityid INT(10) NULL,' .
        'PRIMARY KEY  (id)' .
        ')',
        "CREATE TABLE IF NOT EXISTS {$prefix}communes (" .
        'id INT(6) NOT NULL AUTO_INCREMENT,' .
        'code VARCHAR(6),' .
        'nom VARCHAR(30),' .
        'PRIMARY KEY  (id)' .
        ')',
    ] as $query)
    {
        $wpdb->query($query);
    }

    create_custom_page();
    add_communes();
}

function add_communes()
{
    global $wpdb;

    $table     = $wpdb->prefix . 'communes';

    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

    if ($row_count > 0)
    {
        return;
    }

    set_time_limit(600);
    $response  = wp_remote_get('https://geo.api.gouv.fr/communes');

    if ( ! is_wp_error($response))
    {
        $body = wp_remote_retrieve_body($response);

        try
        {
            $obj = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

            foreach ($obj as $entry)
            {
                $wpdb->insert(
                    $table,
                    [
                        'nom'  => $entry['nom'],
                        'code' => $entry['code'],
                    ]
                );
            }
        } catch (\Throwable)
        {
        }
    }
}

function create_custom_page()
{
    if (get_option('meteo_page_id'))
    {
        return;
    }

    // Check if the page already exists
    $page_title = 'Météo';

    $post_info  = [
        'post_title'   => $page_title,
        'post_content' => 'Ceci est le contenu de la page météo.',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];

    $page       = null;

    foreach (get_posts($post_info) as $post)
    {
        if ($post->post_title === $page_title)
        {
            $page = $post;
            break;
        }
    }

    // If the page doesn't exist, create it
    if ( ! $page)
    {
        $page_args = [
            'post_title'   => $page_title,
            'post_content' => 'Ceci est le contenu de la page météo.',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ];

        // Insert the page and get its ID
        $page_id   = wp_insert_post($page_args);

        if ($page_id)
        {
            // Page created successfully
            update_option('meteo_page_id', $page_id); // Save the page ID in an option
        }
    } else
    {
        update_option('meteo_page_id', $page->ID);
    }
}

function meteo_table_drop()
{
    global $wpdb;

    $prefix = $wpdb->prefix;

    foreach ([
        "{$prefix}shortcode",
        // "{$prefix}communes",

    ] as $table)
    {
        $wpdb->query(sprintf('DROP TABLE %s', $table));
    }
    delete_custom_page();
    // delete_option('meteo_api_key');
}

function delete_custom_page()
{
    // Specify the page ID you want to delete
    $page_id_to_delete = get_option('meteo_page_id'); // Replace with the actual page ID

    if (wp_delete_post($page_id_to_delete, true))
    {
        delete_option('meteo_page_id');
    }
}

function api_key_manager_menu()
{
    add_menu_page(
        'Gestion de la clé API Météo',
        'Météo',
        'manage_options',
        // 'edit_others_posts',
        'meteo-plugin',
        'api_key_manager_page'
    );
}

function api_key_manager_page()
{
    ?>
    <div class="wrap">
        <h2>Gestion de la clé API</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields('api_key_settings');
    do_settings_sections('api-key-manager');
    submit_button('Enregistrer clé API');
    ?>
        </form>
        <?php include __DIR__ . '/add-shortcode.php'; ?>
    </div>
    <?php
}
function api_key_settings_init()
{
    register_setting('api_key_settings', 'meteo_api_key');

    add_settings_section(
        'api_key_section',
        'Paramètres de la clé API',
        'api_key_section_callback',
        'api-key-manager'
    );

    add_settings_field(
        'api_key_field',
        'Clé API',
        'api_key_field_callback',
        'api-key-manager',
        'api_key_section'
    );
}

function api_key_section_callback()
{
    echo 'Générez ou modifiez votre clé d\'API ci-dessous.';
}

function api_key_field_callback()
{
    $api_key = get_option('meteo_api_key');
    echo '<input type="text" name="meteo_api_key" value="' . esc_attr($api_key) . '" />';
}

function call_openweather_api(string $url, array $data)
{
    $data     = array_replace([
        'appid' => get_option('meteo_api_key'),
        'units' => 'metric',
        'lang'  => 'fr',
    ], $data);

    $url .= sprintf('?%s', http_build_query($data));

    $response = wp_remote_get($url);

    if ( ! is_wp_error($response))
    {
        try
        {
            return json_decode(wp_remote_retrieve_body($response), true, JSON_THROW_ON_ERROR);
        } catch (\Throwable)
        {
        }
    }

    return null;
}

function meteo_shortcode($attr)
{
    /* @var \wpdb $wpdb */
    global $wpdb;

    if ($ville = $attr['ville'] ?? '')
    {
        if (null === $shortcodeItem = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . $wpdb->prefix . 'shortcode WHERE shortcode = %s',
            $ville
        )))
        {
            if ($entry = $wpdb->get_row($wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'communes WHERE nom = %s',
                $ville
            )))
            {
                if ($data = call_openweather_api('http://api.openweathermap.org/geo/1.0/direct', [
                    'limit' => 1,
                    'q'     => sprintf('%s,FR', $ville),
                ]))
                {
                    if (isset($data[0]))
                    {
                        if ($wpdb->insert($wpdb->prefix . 'shortcode', [
                            'shortcode' => $ville,
                            'latitude'  => $data[0]['lat'],
                            'longitude' => $data[0]['lon'],
                        ]) > 0)
                        {
                            $shortcodeItem = $wpdb->get_row(
                                $wpdb->prepare(
                                    'SELECT * FROM ' . $wpdb->prefix . 'shortcode WHERE id = %d',
                                    $wpdb->insert_id
                                )
                            );
                        }
                    }
                }
            }
        }

        if (is_object($shortcodeItem))
        {
            if ( ! $shortcodeItem->cityid)
            {
                if ($data = call_openweather_api('https://api.openweathermap.org/data/2.5/forecast', [
                    'lat' => $shortcodeItem->latitude,
                    'lon' => $shortcodeItem->longitude,
                ]))
                {
                    $shortcodeItem->cityid = $data['city']['id'];
                    $wpdb->update($wpdb->prefix . 'shortcode', [
                        'cityid' => $shortcodeItem->cityid,
                    ], ['id' => $shortcodeItem->id]);
                } else
                {
                    $shortcodeItem = null;
                }
            }

            return include __DIR__ . '/meteo-display.php';
        }
    }
    return '<strong>[METEO: Ville ' . $ville . ' inconnue.]</strong>';
}
