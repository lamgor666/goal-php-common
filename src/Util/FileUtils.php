<?php

namespace Goal\Common\Util;

use Dflydev\ApacheMimeTypes\Parser;
use RuntimeException;

final class FileUtils
{
    private function __construct()
    {
    }

    public static function scanFiles(string $dir, array &$list): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $dir = str_replace("\\", '/', $dir);
        }

        $entries = scandir($dir);

        if (!is_array($entries) || empty($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fpath = "$dir/$entry";

            if (is_dir($fpath)) {
                self::scanFiles($fpath, $list);
                continue;
            }

            array_push($list, $fpath);
        }
    }

    public static function getExtension(string $filepath): string
    {
        if (strpos($filepath, '.') === false) {
            return '';
        }

        return strtolower(StringUtils::substringAfterLast($filepath, '.'));
    }

    public static function getMimeType(string $filepath, bool $strictMode = false): string
    {
        if (!$strictMode) {
            return self::getMimeTypeByExtension(self::getExtension($filepath));
        }

        if (!extension_loaded('fileinfo')) {
            return '';
        }

        if (!is_file($filepath)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME);

        if ($finfo === false) {
            return '';
        }

        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        if (empty($mimeType)) {
            return '';
        }

        return strpos($mimeType, ';') !== false ? StringUtils::substringBefore($mimeType, ';') : $mimeType;
    }

    public static function getRealpath(string $path): string
    {
        if (StringUtils::startsWith($path, '/')) {
            return $path;
        }

        if (StringUtils::startsWith($path, '@base')) {
            if (function_exists('base_path')) {
                $path = str_replace('@base', '', $path);
                $path = ltrim($path, '/');
                return base_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@storage')) {
            if (function_exists('storage_path')) {
                $path = str_replace('@storage', '', $path);
                $path = ltrim($path, '/');
                return storage_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@data')) {
            if (function_exists('data_path')) {
                $path = str_replace('@data', '', $path);
                $path = ltrim($path, '/');
                return data_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@cache')) {
            if (function_exists('cache_path')) {
                $path = str_replace('@cache', '', $path);
                $path = ltrim($path, '/');
                return cache_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@logs')) {
            if (function_exists('logs_path')) {
                $path = str_replace('@logs', '', $path);
                $path = ltrim($path, '/');
                return logs_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@resources')) {
            if (function_exists('resources_path')) {
                $path = str_replace('@resources', '', $path);
                $path = ltrim($path, '/');
                return resources_path($path);
            }

            return $path;
        }

        if (StringUtils::startsWith($path, '@')) {
            throw new RuntimeException('unsupported path alias');
        }

        return $path;
    }

    private static function getMimeTypeByExtension(string $fileExt): string
    {
        if (empty($fileExt)) {
            return '';
        }

        $parser = new Parser();
        $mineTypesFile = __DIR__ . '/mime.types';
        $map1 = $parser->parse($mineTypesFile);

        foreach ($map1 as $mimeType => $extensions) {
            if (in_array($fileExt, $extensions)) {
                return $mimeType;
            }
        }

        return '';
    }
}
