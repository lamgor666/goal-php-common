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
            return function_exists('base_path') ? base_path($path) : $path;
        }

        if (StringUtils::startsWith($path, '@storage')) {
            return function_exists('storage_path') ? storage_path($path) : $path;
        }

        if (StringUtils::startsWith($path, '@data')) {
            return function_exists('data_path') ? data_path($path) : $path;
        }

        if (StringUtils::startsWith($path, '@cache')) {
            return function_exists('cache_path') ? cache_path($path) : $path;
        }

        if (StringUtils::startsWith($path, '@logs')) {
            return function_exists('logs_path') ? logs_path($path) : $path;
        }

        if (StringUtils::startsWith($path, '@resources')) {
            return function_exists('resources_path') ? resources_path($path) : $path;
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
