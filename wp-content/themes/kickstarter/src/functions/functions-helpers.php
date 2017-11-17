<?php
/*  ********************************************************
 *   Helpers functions
 *  ********************************************************
 */

/* Backstretch output */
if (!function_exists('backstretch'))
{

  function backstretch($image_url, $image_url_retina)
  {
    $jquery = '';
    if ($image_url == '')
    {
      return;
    }

    $jquery .= '
    <script>
    jQuery(function($) {
      $(\'#page-header\').backstretch([';

      // display standard image
      $jquery .= '{ width: 1080, url: "' . $image_url . '", pixelRatio: 1 },';

    // Set image for retina
      if ($image_url_retina)
      {

        $jquery .= '{ width: 1080, url: "' . $image_url_retina . '", pixelRatio: 2 },';

      }

      $jquery .= ']);
    });
    </script>';

    return $jquery;
  }
}
