<?php
/**
 * Options for the Avatar Plugin.
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

$conf['namespace']        = 'user';        // user namespace where local avatars are stored
$conf['size']             = 80;            // default size of avatars: 20, 40, 80 or 120 pixel
$conf['rating']           = 'PG';          // max rating of gravatar images: G, PG, R or X
$conf['gravatar_default'] = 'monsterid';   // default fallback for gravatar
$conf['local_default']    = 'monsterid';   // default fallback for local avatars
