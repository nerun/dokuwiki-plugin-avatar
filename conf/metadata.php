<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the Avatar Plugin

 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 * @author     Daniel Dias Rodrigues <danieldiasr@gmail.com>
 */

$meta['namespace']        = array('string');
$meta['size']             = array('multichoice', '_choices' => array(20, 40, 80, 120));
$meta['rating']           = array('multichoice', '_choices' => array('X', 'R', 'PG', 'G'));
$meta['gravatar_default'] = array('multichoice', '_choices' => array('initials', 'color', '404', 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank'));
$meta['local_default']    = array('multichoice', '_choices' => array('monsterid', 'mystery_man'));

