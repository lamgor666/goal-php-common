<?php

namespace Goal\Common\Util;

use Goal\Common\Cast;
use Goal\Common\Constants\Regexp;
use Goal\Common\Constants\ReqParamSecurityMode as SecurityMode;
use Goal\Common\HtmlPurifier;
use Throwable;

final class ArrayUtils
{
    private function __construct()
    {
    }

    public static function first(array $arr, ?callable $callback = null)
    {
        if (empty($arr) || !self::isList($arr)) {
            return null;
        }

        if (!is_callable($callback)) {
            return $arr[0];
        }

        $matched = null;

        foreach ($arr as $it) {
            try {
                $flag = $callback($it);
            } catch (Throwable $ex) {
                $flag = false;
            }

            if ($flag === true) {
                $matched = $it;
                break;
            }
        }

        return $matched;
    }

    public static function last(array $arr, ?callable $callback = null)
    {
        if (empty($arr) || !self::isList($arr)) {
            return null;
        }

        $n1 = count($arr) - 1;

        if (!is_callable($callback)) {
            return $arr[$n1];
        }

        $matched = null;

        for ($i = $n1; $i <= 0; $i--) {
            $it = $arr[$i];

            try {
                $flag = $callback($it);
            } catch (Throwable $ex) {
                $flag = false;
            }

            if ($flag === true) {
                $matched = $it;
                break;
            }
        }

        return $matched;
    }

    public static function sortAsc(array $arr, callable $callback, int $options = SORT_REGULAR): array
    {
        if (!self::isList($arr) || count($arr) < 2) {
            return $arr;
        }

        $list = collect($arr)->sortBy($callback, $options)->toArray();
        return array_values($list);
    }

    public static function sortDesc(array $arr, callable $callback, int $options = SORT_REGULAR): array
    {
        if (!self::isList($arr) || count($arr) < 2) {
            return $arr;
        }

        $list = collect($arr)->sortByDesc($callback, $options)->toArray();
        return array_values($list);
    }

    public static function camelCaseKeys(array $arr): array
    {
        if (empty($arr)) {
            return [];
        }

        foreach ($arr as $key => $value) {
            if (!is_string($key)) {
                unset($arr[$key]);
                continue;
            }

            $newKey = $key;
            $needUcwords = false;

            if (strpos($newKey, '-') !== false) {
                $newKey = str_replace('-', ' ', $newKey);
                $needUcwords = true;
            } else if (strpos($newKey, '_') !== false) {
                $newKey = str_replace('_', ' ', $newKey);
                $needUcwords = true;
            }

            if ($needUcwords) {
                $newKey = str_replace(' ', '', ucwords($newKey));
            }

            if ($newKey === $key) {
                continue;
            }

            $arr[$newKey] = $value;
            unset($key);
        }

        return $arr;
    }

    /**
     * @param array $arr
     * @param string|array $keys
     * @return array
     */
    public static function removeKeys(array $arr, $keys): array
    {
        if (is_string($keys) && $keys !== '') {
            $keys = preg_split('/[\x20\t]*,[\x20\t]*/', $keys);
        }

        if (!is_array($keys) || empty($keys)) {
            return $arr;
        }

        if (!self::isAssocArray($arr)) {
            foreach ($arr as $key => $val) {
                $arr[$key] = self::removeKeys($val, $keys);
            }

            return $arr;
        }

        foreach ($arr as $key => $val) {
            if (!is_string($key) || !in_array($key, $keys)) {
                continue;
            }

            unset($arr[$key]);
        }

        return $arr;
    }

    public static function removeEmptyFields(array $arr): array
    {
        if (empty($arr)) {
            return [];
        }

        foreach ($arr as $key => $value) {
            if ($value === null) {
                unset($arr[$key]);
                continue;
            }

            if ($value === '') {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    public static function isAssocArray($arg0): bool
    {
        if (!is_array($arg0) || empty($arg0)) {
            return false;
        }

        $keys = array_keys($arg0);

        foreach ($keys as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    public static function isList($arg0): bool
    {
        if (!is_array($arg0) || empty($arg0)) {
            return false;
        }

        $keys = array_keys($arg0);
        $n1 = count($keys);

        for ($i = 0; $i < $n1; $i++) {
            if (!is_int($keys[$i]) || $keys[$i] < 0) {
                return false;
            }

            if ($i > 0 && $keys[$i] - 1 !== $keys[$i - 1]) {
                return false;
            }
        }

        return true;
    }

    public static function isIntArray($arg0): bool
    {
        if (!self::isList($arg0)) {
            return false;
        }

        foreach ($arg0 as $val) {
            if (!is_int($val)) {
                return false;
            }
        }

        return true;
    }

    public static function isStringArray($arg0): bool
    {
        if (!self::isList($arg0)) {
            return false;
        }

        foreach ($arg0 as $val) {
            if (!is_string($val)) {
                return false;
            }
        }

        return true;
    }

    public static function toxml(array $arr, array $cdataKeys = []): string
    {
        $sb = [str_replace('/', '', '<xml/>')];

        foreach ($arr as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            if (is_int($val) || is_numeric($val) || !in_array($key, $cdataKeys)) {
                $sb[] = "<$key>$val</$key>";
            } else {
                $sb[] = "<$key><![CDATA[$val]]></$key>";
            }
        }

        $sb[] = '</xml>';
        return implode('', $sb);
    }

    /**
     * @param array $arr
     * @param string[]|string $rules
     * @return array
     */
    public static function requestParams(array $arr, $rules): array
    {
        if (is_string($rules) && $rules !== '') {
            $rules = preg_split('/[\x20\t]*,[\x20\t]*/', $rules);
        }
        
        if (!self::isStringArray($rules) || empty($rules)) {
            return $arr;
        }

        $map1 = [];

        foreach ($rules as $rule) {
            $type = 1;
            $securityMode = SecurityMode::STRIP_TAGS;
            $defaultValue = null;

            if (strpos($rule, '@default:') !== false) {
                $defaultValue = StringUtils::substringAfterLast($rule, '@');
                $defaultValue = str_replace('default:', '', $defaultValue);
                $rule = StringUtils::substringBeforeLast($rule, '@');
            }

            if (StringUtils::startsWith($rule, 'i:')) {
                $type = 2;
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 'd:')) {
                $type = 3;
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 's:')) {
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 'a:')) {
                $type = 4;
                $rule = StringUtils::substringAfter($rule, ':');
            }

            $s1 = '';
            
            switch ($type) {
                case 1:
                    if (StringUtils::endsWith($rule, ':0')) {
                        $s1 = StringUtils::substringBeforeLast($rule, ':');
                        $securityMode = SecurityMode::NONE;
                    } else if (StringUtils::endsWith($rule, ':1')) {
                        $s1 = StringUtils::substringBeforeLast($rule, ':');
                        $securityMode = SecurityMode::HTML_PURIFY;
                    } else if (StringUtils::endsWith($rule, ':2')) {
                        $s1 = StringUtils::substringBeforeLast($rule, ':');
                    } else {
                        $s1 = $rule;
                    }

                    break;
                case 2:
                    $s1 = $rule;

                    $defaultValue = is_string($defaultValue) && is_numeric($defaultValue) ?
                        Cast::toInt($defaultValue) : PHP_INT_MIN;

                    break;
                case 3:
                    $s1 = $rule;

                    $defaultValue = is_string($defaultValue) && $defaultValue !== '' ?
                        bcadd($defaultValue, 0, 2) : '0.00';

                    break;
            }

            if (empty($s1)) {
                continue;
            }

            if (strpos($s1, '#') !== false) {
                $mapKey = StringUtils::substringBefore($s1, '#');
                $dstKey = StringUtils::substringAfter($s1, '#');
            } else {
                $mapKey = $s1;
                $dstKey = $s1;
            }

            switch ($type) {
                case 2:
                    $value = Cast::toInt($arr[$mapKey], is_int($defaultValue) ? $defaultValue : PHP_INT_MIN);
                    break;
                case 3:
                    $value = Cast::toString($arr[$mapKey]);

                    if (StringUtils::isFloat($value)) {
                        $value = bcadd($value, 0, 2);
                    } else if (is_string($defaultValue) && StringUtils::isFloat($value)) {
                        $value = bcadd($defaultValue, 0, 2);
                    } else {
                        $value = '0.00';
                    }

                    break;
                case 4:
                    $value = json_decode(Cast::toString($arr[$mapKey]), true);
                    $value = is_array($value) ? $value : [];
                    break;
                default:
                    $value = self::getStringWithSecurityMode($arr, $mapKey, $securityMode);
                    break;
            }

            $map1[$dstKey] = $value;
        }

        return $map1;
    }

    /**
     * @param $arr
     * @param string[]|string $keys
     * @return array
     */
    public static function copyFields($arr, $keys): array
    {
        if (is_string($keys) && $keys !== '') {
            $keys = preg_split(Regexp::COMMA_SEP, $keys);
        }

        if (empty($keys) || !self::isStringArray($keys)) {
            return [];
        }

        $map1 = [];

        foreach ($arr as $key => $val) {
            if (!in_array($key, $keys)) {
                continue;
            }

            $map1[$key] = $val;
        }

        return $map1;
    }

    public static function fromBean($obj, array $propertyNameToMapKey = [], bool $ignoreNull = false): array
    {
        if (is_object($obj) && method_exists($obj, 'toMap')) {
            return $obj->toMap($propertyNameToMapKey, $ignoreNull);
        }

        return [];
    }

    private static function getStringWithSecurityMode(
        array $arr,
        string $key,
        int $securityMode = SecurityMode::STRIP_TAGS
    ): string
    {
        $value = $arr[$key];

        if (is_int($value) || is_float($value)) {
            return "$value";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (!is_string($value)) {
            return '';
        }

        if ($value === '') {
            return $value;
        }

        switch ($securityMode) {
            case SecurityMode::HTML_PURIFY:
                return HtmlPurifier::purify($value);
            case SecurityMode::STRIP_TAGS:
                return strip_tags($value);
            default:
                return $value;
        }
    }
}
