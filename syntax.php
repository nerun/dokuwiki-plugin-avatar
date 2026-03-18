<?php
/**
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

if(!defined('DOKU_INC')) die();

class syntax_plugin_avatar extends DokuWiki_Syntax_Plugin {

    const SIZE_SMALL = 20;
    const SIZE_MEDIUM = 40;
    const SIZE_LARGE = 80;
    const SIZE_XLARGE = 120;

    public function getType(): string { return 'substition'; }
    public function getSort(): int { return 315; }

    public function connectTo($mode): void {
        $this->Lexer->addSpecialPattern("{{(?:gr|)avatar>.+?}}", $mode, 'plugin_avatar');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler): ?array {
        $parts = explode('>', substr($match, 2, -2), 2);

        if (count($parts) !== 2) {
            return null; // Malformed input → discards and interrupts handle()
        }

        $match = $parts[1]; //  $parts[0] = 'avatar' or 'gravatar'
        
        if (!preg_match('/^([^?|]+)(?:\?([^|]*))?(?:\|(.*))?$/', $match, $matches)) {
            return null;
        }

        $user = $matches[1];

        /* Final check:
         * Even if something strange slipped through the first regex, here the
         * string is checked in isolation. This ensures that the renderer will
         * only receive a clean username or a valid email, preventing parsing
         * problems, CSS errors, HTML injection, etc.
         */
        if (filter_var($user, FILTER_VALIDATE_EMAIL) === false &&
            !preg_match('/^\s*[a-zA-Z0-9._-]+\s*$/', $user)) {
            return null;
        }

        $param = isset($matches[2]) ? trim(strtolower($matches[2])) : '';
        $title = isset($matches[3]) ? trim($matches[3]) : '';
        
        // Determine alignment
        $align = null;
        if ($user !== ltrim($user)) $align = 'right';
        if ($user !== rtrim($user)) $align = $align ? 'center' : 'left';
        $user = trim($user);

        // Determine size
        switch ($param) {
            case 's':  $size = self::SIZE_SMALL; break;
            case 'm':  $size = self::SIZE_MEDIUM; break;
            case 'l':  $size = self::SIZE_LARGE; break;
            case 'xl': $size = self::SIZE_XLARGE; break;
            default:
                $size = max(1, (int) $this->getConf('size')) ?: self::SIZE_MEDIUM;
                break;
        }

        return [$user, $title, $align, $size];
    }

    public function render($mode, Doku_Renderer $renderer, $data): bool {
        if ($mode !== 'xhtml') return false;
        
        if ($data === null) {
            $renderer->doc .= '<span style="color:red;font-family:monospace;' .
                              'font-weight:bold;">' .
                              'Error: Avatar plugin: Invalid username or' .
                              ' email</span>';
            return true;
        }

        if ($my = plugin_load('helper', 'avatar')) {
            $renderer->doc .= '<span class="vcard">' . 
                $my->renderXhtml($data[0], $data[1], $data[2], $data[3]) . 
                '</span>';
        }
        return true;
    }
}
