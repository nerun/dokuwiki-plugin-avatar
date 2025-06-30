<?php
/**
 * Avatar Plugin for DokuWiki
 * 
 * Displays avatar images with syntax {{avatar>email@domain.com}}
 * Supports local avatars, Gravatar.com, and MonsterID fallback
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 * @author     Daniel Dias Rodrigues <danieldiasr@gmail.com> (modernization)
 */

declare(strict_types=1);

if (!defined('DOKU_INC')) die();

class helper_plugin_avatar extends DokuWiki_Plugin
{
    private const DEFAULT_SIZES = [
        'small'  => 20,
        'medium' => 40,
        'large'  => 80,
        'xlarge' => 120
    ];

    private const ALLOWED_FORMATS = ['.png', '.jpg', '.gif'];
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
    public function resolveAvatarUrl(string|array $user, ?string &$title = null, ?int &$size = null): string
    {
        global $auth;

        $size = $this->normalizeSize($size);
        $cacheKey = $this->getCacheKey($user, $title, $size);

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

    private function normalizeSize(?int $size): int
    {
        if ($size && $size > 0) {
            return $size;
        }

        $confSize = (int) $this->getConf('size');
        return $confSize > 0 ? $confSize : self::DEFAULT_SIZES['medium'];
    }

    private function getCacheKey(string|array $user, ?string $title, int $size): string
    {
        $userKey = is_array($user) ? ($user['mail'] ?? '') : $user;
        return md5($userKey . $title . $size);
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

        if (!$userinfo) {
            return null;
        }

        if (empty($title) && !empty($userinfo['name'])) {
            $title = hsc($userinfo['name']);
        }

        $ns = $this->getConf('namespace');
        foreach (self::ALLOWED_FORMATS as $format) {
            $imagePath = $ns . ':' . $username . $format;
            $imageFile = mediaFN($imagePath);

            if (file_exists($imageFile)) {
                return ml($imagePath, ['w' => $size, 'h' => $size], true, '&', false);
            }
        }

        // Only generate MonsterID if it's the selected fallback
        if ($this->getConf('local_default') === 'monsterid') {
            if (is_string($user) && $user === $username) {
                if ($this->saveMonsterIdAvatar($username, 120)) {
                    $imagePath = $ns . ':' . $username . '.png';
                    if (file_exists(mediaFN($imagePath))) {
                        return ml($imagePath, ['w' => $size, 'h' => $size], true, '&', false);
                    }
                }
            }
        }

        return null;
    }

    private function saveMonsterIdAvatar(string $username, int $size): bool
    {
        $ns = $this->getConf('namespace');
        $filename = $ns . ':' . $username . '.png';
        $filepath = mediaFN($filename);

        // Monsterid URL for the user
        $seed = md5(dokuwiki\Utf8\PhpString::strtolower($username));
        $monsterUrl = DOKU_URL . 'lib/plugins/avatar/monsterid.php?seed=' . $seed . '&size=' . $size;

        // Download the image using file_get_contents
        $imageData = @file_get_contents($monsterUrl);
        if ($imageData === false) {
            return false;
        }

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
            'd' => $this->getConf('gravatar_default'),
            'r' => $this->getConf('rating')
        ];

        return self::GRAVATAR_BASE . $seed . '?' . http_build_query($params);
    }

    private function getMonsterIdUrl(string $seed, int $size): string
    {
        $file = 'monsterid.php?seed=' . $seed . '&size=' . $size . '&.png';
        return ml(DOKU_URL . 'lib/plugins/avatar/' . $file, 'cache=recache', true, '&', true);
    }

    private function getDefaultImageUrl(int $size): string
    {
        $validSizes = array_values(self::DEFAULT_SIZES);
        $realSize = in_array($size, $validSizes, true) ? $size : self::DEFAULT_SIZES['xlarge'];
        $file = 'images/default_' . $realSize . '.png';
        return ml(DOKU_URL . 'lib/plugins/avatar/' . $file, 'cache=recache', true, '&', true);
    }
}

