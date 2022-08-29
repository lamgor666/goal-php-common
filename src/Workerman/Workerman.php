<?php

namespace Goal\Common\Workerman;

use Goal\Common\Util\StringUtils;
use Throwable;

final class Workerman
{
    private static $worker = null;

    private function __construct()
    {
    }

    public static function setWorker($worker): void
    {
        if (!is_object($worker)) {
            return;
        }

        if (StringUtils::ensureLeft(get_class($worker), "\\") !== '\Workerman\Worker') {
            return;
        }

        self::$worker = $worker;
    }

    public static function getWorker()
    {
        return self::$worker;
    }

    public static function getWorkerId(): int
    {
        $worker = self::$worker;

        if (!is_object($worker) || !property_exists($worker, 'id')) {
            return -1;
        }

        try {
            $id = $worker->id;
        } catch (Throwable $ex) {
            return -1;
        }

        return is_int($id) && $id >= 0 ? $id : -1;
    }
}
