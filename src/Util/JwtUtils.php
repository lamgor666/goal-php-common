<?php

namespace Goal\Common\Util;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Goal\Common\Cast;
use Throwable;

final class JwtUtils
{
    private function __construct()
    {
    }

    public static function getSignKey(string $pemFilepath): Key
    {
        return Key\InMemory::file($pemFilepath);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param int $default
     * @return int
     */
    public static function intClaim($arg0, string $name, int $default = PHP_INT_MIN): int
    {
        return Cast::toInt(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param float $default
     * @return float
     */
    public static function floatClaim($arg0, string $name, float $default = PHP_FLOAT_MIN): float
    {
        return Cast::toFloat(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public static function booleanClaim($arg0, string $name, bool $default = false): bool
    {
        return Cast::toBoolean(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function stringClaim($arg0, string $name, string $default = ''): string
    {
        return Cast::toString(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @return array
     */
    public static function arrayClaim($arg0, string $name): array
    {
        $ret = self::claim($arg0, $name);
        return is_array($ret) ? $ret : [];
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @return mixed
     */
    private static function claim($arg0, string $name)
    {
        $jwt = null;

        if ($arg0 instanceof Token) {
            $jwt = $arg0;
        } else if (is_string($arg0) && $arg0 !== '') {
            try {
                $jwt = (new Parser())->parse($arg0);
            } catch (Throwable $ex) {
                $jwt = null;
            }
        }

        if (!($jwt instanceof Token)) {
            return null;
        }

        try {
            return $jwt->claims()->get($name);
        } catch (Throwable $ex) {
            return null;
        }
    }
}
