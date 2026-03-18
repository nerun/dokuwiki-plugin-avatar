<?php
/**
 * Avatar Plugin: displays avatar images with syntax {{avatar>email@domain.com}}
 * Optionally you can add a title attribute: {{avatar>email@domain.com|My Name}}
 *
 * For registered users the plugin looks first for a local avatar named
 * username.jpg in user namespace. If none found or for unregistered guests, the
 * avatar from Gravatar.com is taken when available. The MonsterID by Andreas
 * Gohr serves as fallback.
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Esther Brunner <wikidesign@gmail.com>
 * @author   Daniel Dias Rodrigues <danieldiasr@gmail.com> (modernization)
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
