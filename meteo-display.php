<?php

if ( ! isset($shortcodeItem))
{
    return;
}
$num         = ++OpenWeatherMeteoWidget::$widgetCount;

$containerId = 'openweathermap-widget-' . $num;

$widgetData  = json_encode([
    'id'          => 21,
    'cityid'      => $shortcodeItem->cityid,

    'cityName'    => $shortcodeItem->shortcode,
    'appid'       => get_option('meteo_api_key'),
    'units'       => 'metric',
    // 'lang'        => 'fr',
    'containerid' => $containerId,
]);

ob_start();

?>

<div id="<?php echo $containerId; ?>" class="my-3 d-flex justify-content-center"></div>
<script lang="fr">
   
    window.myWidgetParam ??= [];
    window.myWidgetParam.push(<?php echo $widgetData; ?>);
</script>

<?php

OpenWeatherMeteoWidget::loadScript();
return ob_get_clean();
