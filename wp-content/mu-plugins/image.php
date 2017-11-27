<?php
/*
Plugin Name: Responsive retina image with unviel
Description: Load retina images with lazy load
Version: 0.0.2
Author: Dom Kapelewski
Author URI: https://www.redfrogstudio.co.uk
License: GPL2
 */
// Exit if loaded directly
if (!defined('ABSPATH'))
{
  die('-1');
}

if (!class_exists('Image'))
{

  class Image
  {
    public static $plugin_version = '0.0.2';

    /* Construct */
    public function __construct()
    {
      do_action('after_theme_setup', array($this, 'some_function'), 1);
    }

    /* Image Alt */

    public static function image_alt($attach_id)
    {
      return get_post_meta($attach_id, '_wp_attachment_image_alt', true);
    }

    /* Image function */
    public static function img_resize($attach_id, $img_url = null, $width = 150, $height = 100, $crop = false, $quality = 100, $retina = true, $figure = true, $lazy = true)
    {
      /* First check if the attachement id exists */
      if ($attach_id)
      {
        $image_src = wp_get_attachment_image_src($attach_id, 'full');
        $file_path = get_attached_file($attach_id);
        $alt       = self::image_alt($attach_id);
      }
      /* Check if the url is avaiilable and valid */

      else if ($img_url)
      {
        /* We are still glong to grab image ID to fetch the ALT Tag */
        global $wpdb;
        $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $img_url));
        $alt           = self::image_alt($attachment_id);

        $file_path = parse_url($img_url);
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];

        if (file_exists($file_path) === false)
        {
          global $blog_id;
          $file_path = parse_url($img_url);
          if (preg_match('/files/', $file_path['path']))
          {
            $path = explode('/', $file_path['path']);
            foreach ($path as $k => $v)
            {
              if ($v == 'files')
              {
                $path[$k - 1] = 'wp-content/blogs.dir/' . $blog_id;
              }
            }
            $path = implode('/', $path);
          }
          $file_path = $_SERVER['DOCUMENT_ROOT'] . $path;
        }

        //$file_path = ltrim( $file_path['path'], '/' );
        //$file_path = rtrim( ABSPATH, '/' ).$file_path['path'];

        $orig_size = getimagesize($file_path);

        $image_src[0] = $img_url;
        $image_src[1] = $orig_size[0];
        $image_src[2] = $orig_size[1];
      }

      $file_info = pathinfo($file_path);

      /* Check if file exists */
      $base_file = array_key_exists('dirname', $file_info) ? $file_info['dirname'] . '/' . $file_info['filename'] . '.' . $file_info['extension'] : '';
      if (!file_exists($base_file) || $base_file === '')
      {
        return;
      }

      $extension = '.' . $file_info['extension'];

      // the image path without the extension
      $no_ext_path      = $file_info['dirname'] . '/' . $file_info['filename'];
      $cropped_img_path = $no_ext_path . '-' . $width . 'x' . $height . $extension;

      // checking if the file size is larger than the target size
      // if it is smaller or the same size, stop right here and return
      if ($image_src[1] > $width)
      {

        // the file is larger, check if the resized version already exists (for $crop = true but will also work for $crop = false if the sizes match)
        if (file_exists($cropped_img_path))
        {

          $cropped_img_url = str_replace(basename($image_src[0]), basename($cropped_img_path), $image_src[0]);

          $vt_image = array(
            'url'    => $cropped_img_url,
            'width'  => $width,
            'height' => $height,
            'alt'    => $alt,
          );

          return $vt_image;
        }

        /* $crop = false or no height set */
        if ($crop == false or !$height)
        {

          /* calculate the size proportionaly */
          $proportional_size = wp_constrain_dimensions($image_src[1], $image_src[2], $width, $height);
          $resized_img_path  = $no_ext_path . '-' . $proportional_size[0] . 'x' . $proportional_size[1] . $extension;

          /* checking if the file already exists */
          if (file_exists($resized_img_path))
          {

            $resized_img_url = str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);

            $vt_image = array(
              'url'    => $resized_img_url,
              'width'  => $proportional_size[0],
              'height' => $proportional_size[1],
              'alt'    => $alt,
            );

            return $vt_image;
          }
        }

        /* check if image width is smaller than set width */
        $img_size = getimagesize($file_path);
        if ($img_size[0] <= $width)
        {
          $width = $img_size[0];
        }

        /* Check if GD Library installed */
        if (!function_exists('imagecreatetruecolor'))
        {
          echo 'GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library';
          return;
        }

        // no cache files - let's finally resize it

        $new_img_path = wp_get_image_editor($file_path);
        if (!is_wp_error($new_img_path))
        {
          $new_img_path->resize($width, $height, $crop);
          $new_img_path->set_quality($quality);
          $new_file_path = preg_replace('/\.(.+$)/', '-' . $width . 'x' . $height . '.$1', $file_path);
          $final_image   = $new_img_path->save($new_file_path);
        }

        $new_img_size = getimagesize($final_image['path']);
        $new_img      = str_replace(basename($image_src[0]), basename($final_image['path']), $image_src[0]);

        // resized output
        $vt_image = array(
          'url'    => $new_img,
          'width'  => $new_img_size[0],
          'height' => $new_img_size[1],
          'alt'    => $alt,
        );

        return $vt_image;
      }

      // default output - without resizing
      $vt_image = array(
        'url'    => $image_src[0],
        'width'  => $width,
        'height' => $height,
        'alt'    => $alt,
      );

      return $vt_image;
    }

    public static function image($attach_id, $img_url = null, $width = 150, $height = 100, $crop = false, $quality = 100, $retina = true, $figure = true, $lazy = true)
    {
      if ($attach_id == '' && $img_url == '')
      {
        return;
      }

      if ($lazy == true)
      {
        wp_enqueue_script('unveil', plugin_dir_url(__FILE__) . 'image/jquery.unveil.js', array('jquery'), self::$plugin_version, true);
      }

      $image_non_retina = self::img_resize($attach_id, $img_url, $width, $height, $crop, $quality, $retina, $figure, $lazy);
      if ($retina == true)
      {
        $image_retina = self::img_resize($attach_id, $img_url, $width * 2, $height * 2, $crop, $quality, $retina, $figure, $lazy);
      }

      $image = sprintf('
        %5$s
        <img src="%1$s"%2$s%3$s%7$swidth="%4$s">
        %6$s
        <noscript><img src="%1$s" alt="%2$s" width="%4$s"/></noscript>
        ',
        $image_non_retina['url'],
        $image_non_retina['alt'] ? ' alt="' . $image_non_retina['alt'] . '" ' : '',
        $retina == true && $lazy == true ? 'data-src-retina="' . $image_retina['url'] . '"' : '',
        $image_non_retina['width'],
        $figure ? '<figure>' : '',
        $figure ? '</figure>' : '',
        $lazy == true ? ' class="uv" ' : '',
        $lazy == true ? ' data-src="' . $image_non_retina['url'] . '" ' : ''
      );

      return $image;
    }

  }
}
/**
 *  Image display function
 *  Dom Kapelewski
 *
 *  php 5.2+
 *
 *  @param bool $attach_id       // Image ID
 *  @param bool $image_url      // Image URL
 *  @param int $image_height    // Image height
 *  @param int $image_width     // Image width
 *  @param bool $image_crop     // Image width true::false
 *  @param int $image_quality   // Image quality
 *  @param bool $retina         // generate retina image
 *  @param bool $figure         // wrap image in <figure> tag
 *  @param bool $lazy           // initiate lazy load
 *
 *  @return  <figure><img src="img.jpg" width="150px" alt="ALT" class="uv" data-src-retina="imgx2.jpg" /></figure>
 *  @return  <noscript><img src="img.jpg" alt="ALT" /></noscript>
 *
 *  @example  $img = image_figure('100', '', 150, 100, false, 100, true, true, true );
 * ---
 */
function image_figure($attach_id, $img_url = null, $width = 150, $height = 100, $crop = false, $quality = 100, $retina = true, $figure = true, $lazy = true)
{

  // jquery.unveil.min.js
  $image = Image::image($attach_id, $img_url, $width, $height, $crop, $quality, $retina, $figure, $lazy);
  return $image;
}

/**
 *  Image display function
 *  Dom Kapelewski
 *
 *  php 5.2+
 *
 *  @param bool $attach_id       // Image ID
 *  @param bool $image_url      // Image URL
 *  @param int $image_height    // Image height
 *  @param int $image_width     // Image width
 *  @param bool $image_crop     // Image width true::false
 *  @param int $image_quality   // Image quality
 *  @param bool $retina         // generate retina image
 *
 *  @example  $img = image_array('100', '', 150, 100, false, 100, true);
 *
 *  @return  $img['url']      // Image URL
 *  @return  $img['alt']      // Image alt
 *  @return  $img['width']    // Image width
 *  @return  $img['height']   // Image height
 *
 * ---
 */
function image_array($attach_id, $img_url = null, $width = 150, $height = 100, $crop = false, $quality = 100, $retina = true, $figure = true, $lazy = true)
{

  $image = Image::img_resize($attach_id, $img_url, $width, $height, $crop, $quality, $retina, $figure, $lazy);
  return $image;
}
