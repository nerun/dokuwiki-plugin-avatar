<?php
/**
 * Options for the Avatar Plugin
 *
 * Understanding Image Requests:
 * https://docs.gravatar.com/sdk/images
 */

$conf['namespace'] = 'user';        // user namespace where local avatars are stored
$conf['size']      = 80;            // default size of gravatar: 20, 40 or 80 pixel
$conf['rating']    = 'PG';          // max rating of gravatar images: G, PG, R or X
$conf['default']   = 'monsterid';   // type of default images of gravatar

