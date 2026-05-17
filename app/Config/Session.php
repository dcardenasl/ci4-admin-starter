<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Session\Handlers\MemcachedHandler;
use CodeIgniter\Session\Handlers\RedisHandler;

class Session extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Session Driver
     * --------------------------------------------------------------------------
     *
     * The session storage driver to use. Resolved at construction time
     * from the `SESSION_DRIVER` env var (audit B10.3, 2026-05-07):
     *
     *   - `file`       → `FileHandler` (default; **single-server only**)
     *   - `redis`      → `RedisHandler` (recommended for multi-server)
     *   - `database`   → `DatabaseHandler`
     *   - `memcached`  → `MemcachedHandler`
     *
     * Multi-server deployments MUST switch off `file` — otherwise sticky
     * sessions on a load balancer become a hard requirement and a single
     * pod restart logs everyone out. See `docs/DEPLOYMENT.md` for the
     * Redis configuration recipe.
     *
     * @var class-string<BaseHandler>
     */
    public string $driver = FileHandler::class;

    /**
     * --------------------------------------------------------------------------
     * Session Cookie Name
     * --------------------------------------------------------------------------
     *
     * The session cookie name, must contain only [0-9a-z_-] characters
     */
    public string $cookieName = 'ci4_admin_session';

    /**
     * --------------------------------------------------------------------------
     * Session Expiration
     * --------------------------------------------------------------------------
     *
     * The number of SECONDS you want the session to last.
     * Setting to 0 (zero) means expire when the browser is closed.
     */
    public int $expiration = 7200;

    /**
     * --------------------------------------------------------------------------
     * Session Save Path
     * --------------------------------------------------------------------------
     *
     * The location to save sessions to and is driver dependent.
     *
     * For the 'files' driver, it's a path to a writable directory.
     * WARNING: Only absolute paths are supported!
     *
     * For the 'database' driver, it's a table name.
     * Please read up the manual for the format with other session drivers.
     *
     * IMPORTANT: You are REQUIRED to set a valid save path!
     */
    public string $savePath = WRITEPATH . 'session';

    /**
     * --------------------------------------------------------------------------
     * Session Match IP
     * --------------------------------------------------------------------------
     *
     * Whether to match the user's IP address when reading the session data.
     *
     * WARNING: If you're using the database driver, don't forget to update
     *          your session table's PRIMARY KEY when changing this setting.
     */
    public bool $matchIP = false;

    /**
     * --------------------------------------------------------------------------
     * Session Time to Update
     * --------------------------------------------------------------------------
     *
     * How many seconds between CI regenerating the session ID.
     */
    public int $timeToUpdate = 300;

    /**
     * --------------------------------------------------------------------------
     * Session Regenerate Destroy
     * --------------------------------------------------------------------------
     *
     * Whether to destroy session data associated with the old session ID
     * when auto-regenerating the session ID. When set to FALSE, the data
     * will be later deleted by the garbage collector.
     */
    public bool $regenerateDestroy = true;

    /**
     * --------------------------------------------------------------------------
     * Session Database Group
     * --------------------------------------------------------------------------
     *
     * DB Group for the database session.
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------------
     * Lock Retry Interval (microseconds)
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Time (microseconds) to wait if lock cannot be acquired.
     * The default is 100,000 microseconds (= 0.1 seconds).
     */
    public int $lockRetryInterval = 100_000;

    /**
     * --------------------------------------------------------------------------
     * Lock Max Retries
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Maximum number of lock acquisition attempts.
     * The default is 300 times. That is lock timeout is about 30 (0.1 * 300)
     * seconds.
     */
    public int $lockMaxRetries = 300;

    public function __construct()
    {
        parent::__construct();

        // Audit B10.3 (2026-05-07): resolve session driver from env so
        // multi-server deployments can pick Redis without editing this
        // file. The map is conservative — unknown values fall back to
        // FileHandler with a warning so a typo doesn't lock everyone out.
        $configured = strtolower(trim((string) (getenv('SESSION_DRIVER') ?: env('SESSION_DRIVER', ''))));
        if ($configured === '') {
            return; // keep the property default (FileHandler)
        }

        $map = [
            'file'      => FileHandler::class,
            'files'     => FileHandler::class,
            'redis'     => RedisHandler::class,
            'database'  => DatabaseHandler::class,
            'db'        => DatabaseHandler::class,
            'memcached' => MemcachedHandler::class,
        ];

        if (! isset($map[$configured])) {
            log_message(
                'warning',
                "Session: unrecognized SESSION_DRIVER='{$configured}'. Falling back to FileHandler."
            );

            return;
        }

        $this->driver = $map[$configured];

        // For Redis the savePath is `tcp://host:port` (or full DSN). Honor
        // SESSION_SAVE_PATH if set; otherwise leave the default for FileHandler.
        $savePath = (string) (getenv('SESSION_SAVE_PATH') ?: env('SESSION_SAVE_PATH', ''));
        if ($savePath !== '') {
            $this->savePath = $savePath;
        }
    }
}
