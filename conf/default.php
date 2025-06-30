<?php
/**
 * Options for the Avatar Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 * @author     Daniel Dias Rodrigues <danieldiasr@gmail.com>
 *
 * Understanding Image Requests:
 * https://docs.gravatar.com/sdk/images
 */

$conf['namespace']        = 'user';        // user namespace where local avatars are stored
$conf['size']             = 80;            // default size of avatars: 20, 40 or 80 pixel
$conf['rating']           = 'PG';          // max rating of gravatar images: G, PG, R or X
$conf['gravatar_default'] = 'monsterid';   // default fallback for gravatar
$conf['local_default']    = 'monsterid';   // default fallback for local avatars

