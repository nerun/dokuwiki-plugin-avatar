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

declare(strict_types=1);

if (!defined('DOKU_INC')) die();

class helper_plugin_avatar extends DokuWiki_Plugin
{
    private const ALLOWED_FORMATS = ['.png', '.jpg', '.gif', '.webp'];
    private const GRAVATAR_BASE = 'https://secure.gravatar.com/avatar/';

    private array $avatarCache = [];

    public function getMethods(): array
    {
        return [
            [
                'name'   => 'renderXhtml',
                'desc'   => 'Returns the XHTML to display an avatar',
                'params' => [
                    'user'  => 'string|array',
                    'title' => 'string',
                    'align' => 'string',
                    'size'  => 'int'
                ],
                'return' => ['xhtml' => 'string']
            ]
        ];
    }

    /**
     * Renders the avatar as XHTML <img>
     */
    public function renderXhtml(string|array $user, string $title = '', ?string $align = '', ?int $size = null): string
    {
        $src = $this->resolveAvatarUrl($user, $title, $size);

        $title = hsc($title);

        return '<img src="' . $src . '" ' .
               'class="media' . $align . ' photo fn" ' .
               'title="' . $title . '" ' .
               'alt="' . $title . '" ' .
               'width="' . (string) $size . '" ' .
               'height="' . (string) $size . '" />';
    }

    /**
     * Gets or generates the avatar URL for a user/email
     */
    public function resolveAvatarUrl(string|array $user, string &$title, int &$size): string
    {
        $cacheKey = $this->getCacheKey($user, $size);

        if (isset($this->avatarCache[$cacheKey])) {
            return $this->avatarCache[$cacheKey];
        }

        $mail = $this->extractUserData($user, $title);
        $isEmail = mail_isvalid($mail) && (!is_array($user) || !isset($user['user']));
        
        // For emails (Gravatar)
        if ($isEmail) {
            $src = $this->getGravatarUrl($mail, $size);
        } 
        // For local users
        else {
            $src = $this->tryLocalAvatar($user, $title, $size);
            
            if (!$src) {
                // Apply fallback configured for local users only
                if ($this->getConf('local_default') === 'monsterid' && function_exists('imagecreatetruecolor')) {
                    $seed = md5(dokuwiki\Utf8\PhpString::strtolower(is_array($user) ? ($user['user'] ?? '') : $user));
                    $src = $this->getMonsterIdUrl($seed, $size);
                } else {
                    $src = $this->getDefaultImageUrl($size);
                }
            }
        }

        if (empty($title)) {
            $title = obfuscate($mail);
        }

        $this->avatarCache[$cacheKey] = $src;
        return $src;
    }

    private function resolveTokens(string $template): string
    {
        global $INFO;

        $vars = [
            '@USER@' => cleanID($INFO['client'] ?? ''),
        ];

        return strtr($template, $vars);
    }

    private function getCacheKey(string|array $user, int $size): string
    {
        $userKey = is_array($user) ? ($user['mail'] ?? ($user['user'] ?? '')) : $user;
        return md5($userKey . $size);
    }

    private function extractUserData(string|array $user, ?string &$title): string
    {
        if (is_array($user)) {
            if (empty($title) && !empty($user['name'])) {
                $title = hsc($user['name']);
            }
            return $user['mail'] ?? '';
        }
        return $user;
    }

    private function tryLocalAvatar(string|array $user, ?string &$title, int $size): ?string
    {
        global $auth;

        $username = is_array($user) ? ($user['user'] ?? '') : $user;
        $userinfo = $auth->getUserData($username);

        if (!$userinfo) return null;
        if (empty($title) && !empty($userinfo['name'])) $title = hsc($userinfo['name']);

        $ns = $this->resolveTokens($this->getConf('namespace'));
        $existingFiles = [];

        // Scan all allowed formats.
        foreach (self::ALLOWED_FORMATS as $format) {
            $imagePath = $ns . ':' . $username . $format;
            $imageFile = mediaFN($imagePath);

            if (file_exists($imageFile)) {
                $existingFiles[$imagePath] = filesize($imageFile);
            }
        }

        if ($existingFiles) {
            // Returns the file with the smallest size.
            asort($existingFiles);
            $bestPath = key($existingFiles);
            return ml($bestPath, ['w' => $size, 'h' => $size], true, '&', false);
        }

        // No local files found → generate MonsterID if it's the selected
        // fallback
        if ($this->getConf('local_default') === 'monsterid') {
            if ($this->saveMonsterIdAvatar($username, 120)) {
                $monsterPath = $ns . ':' . $username . '.png';
                if (file_exists(mediaFN($monsterPath))) {
                    return ml($monsterPath, ['w' => $size, 'h' => $size], true, '&', false);
                }
            }
        }

        return null;
    }

    private function saveMonsterIdAvatar(string $username, int $size): bool
    {
        global $INFO;

        $currentUser = cleanID($INFO['client'] ?? '');
        $username    = cleanID($username);

        // It only allows you to save your own avatar.
        if ($username !== $currentUser) {
            return false;
        }

        $ns = $this->resolveTokens($this->getConf('namespace'));
        $filename = $ns . ':' . $username . '.png';
        $filepath = mediaFN($filename);

        // If any local avatar of the user already exists, it will not generate
        // a MonsterID.
        foreach (self::ALLOWED_FORMATS as $format) {
            if (file_exists(mediaFN($ns . ':' . $username . $format))) {
                return true;
            }
        }

        // MonsterID URL for the user
        $seed = md5(dokuwiki\Utf8\PhpString::strtolower($username));
        $monsterUrl = DOKU_URL . 'lib/plugins/avatar/monsterid.php?seed=' . $seed . '&size=' . $size;

        // Download the image using file_get_contents
        $imageData = @file_get_contents($monsterUrl);
        if ($imageData === false) return false;

        // creates the directory if it does not exist
        io_makeFileDir($filepath);
        
        // Save the image
        return file_put_contents($filepath, $imageData) !== false;
    }

    private function getGravatarUrl(string $mail, int $size): string
    {
        $seed = md5(dokuwiki\Utf8\PhpString::strtolower($mail));

        $default = function_exists('imagecreatetruecolor')
            ? $this->getMonsterIdUrl($seed, $size)
            : $this->getDefaultImageUrl($size);

        if (!mail_isvalid($mail)) {
            return $default;
        }

        $params = [
            's' => $size,
            'r' => $this->getConf('rating')
        ];

        $gravatar_default = $this->getConf('gravatar_default');
        
        if ($gravatar_default !== 'default') {
            $params['d'] = $gravatar_default;
        }

        return self::GRAVATAR_BASE . $seed . '?' . http_build_query($params);
    }

    private function getMonsterIdUrl(string $seed, int $size): string
    {
        $file = 'monsterid.php?seed=' . $seed . '&size=' . $size;
        return ml(DOKU_URL . 'lib/plugins/avatar/' . $file, 'cache=recache', true, '&', true);
    }

    private function getDefaultImageUrl(int $size): string
    {
        $file = 'images/default_' . $size . '.png';
        return ml(DOKU_URL . 'lib/plugins/avatar/' . $file, 'cache=recache', true, '&', true);
    }
}
