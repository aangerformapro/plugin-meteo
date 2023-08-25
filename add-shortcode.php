<?php

function get_communes_as_json()
{
    /* @var wpdb $wpdb */
    global $wpdb;

    static $json;

    if ( ! is_null($json))
    {
        return $json;
    }

    $data        = $wpdb->get_results(sprintf(
        'SELECT * FROM %s',
        $wpdb->prefix . 'communes'
    ), ARRAY_A);

    $data        = array_map(fn ($item) => [
        'departement' => mb_substr($item['code'], 0, 2),
    ] + $item, $data);

    return $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

?>



<form method="post" id="add-shortcode-form">

    <table class="form-table"  role="presentation">
        <tbody>
            <tr>
                <th scope="row">Ville</th>
                <td>
                    <input type="text" name="nom_ville" value="">
                </td>
            </tr>
            <tr>
                <th scope="row">Shortcode</th>
                <td>
                    <textarea name="meteo_shortcode"></textarea>
                </td>
            </tr>

            <tr>
            <th scope="row">&nbsp;</th>
                <td >
                    <button type="button" id="copy-to-clipboard" disabled>
                        Copier dans le presse papier
                    </button>
                </td>
            </tr>

        </tbody>
    </table>
</form>


<script id="data-communes" type="application/json"><?php echo get_communes_as_json(); ?></script>


<script src="<?php echo plugins_url('/meteo.js', __FILE__); ?>" type="module"></script>

<style type="text/css">
    <?php include __DIR__ . '/vanilla-autocomplete.css'; ?>
</style>
