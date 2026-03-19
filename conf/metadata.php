<?php
/**
 * Metadata for configuration manager plugin.
 * Additions for the Avatar Plugin.
 * DokuWiki Avatar Plugin: displays avatar images with syntax, see:
 * <https://www.dokuwiki.org/plugin:avatar>.
 *
 * Copyright (C) 2005-2007 by Esther Brunner <wikidesign@gmail.com>
 * Copyright (C) 2008-2009 by Gina Häußge, Michael Klier <dokuwiki@chimeric.de>
 * Copyright (C) 2013 by Michael Hamann <michael@content-space.de>
 * Copyright (C) 2023 by Daniel Dias Rodrigues <danieldiasr@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, see <https://www.gnu.org/licenses/>.
 */

$meta['namespace']        = array('string');
$meta['size']             = array('multichoice', '_choices' => array(20, 40, 80, 120));
$meta['rating']           = array('multichoice', '_choices' => array('X', 'R', 'PG', 'G'));
$meta['gravatar_default'] = array('multichoice', '_choices' => array('default', '404', 'blank', 'color', 'identicon', 'initials', 'monsterid', 'mp', 'retro', 'robohash', 'wavatar'));
$meta['local_default']    = array('multichoice', '_choices' => array('monsterid', 'mystery_man'));
