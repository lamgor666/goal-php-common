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
    public static function asHttpInput(array $arr, $rules): array
    {
        if (is_string($rules) && $rules !== '') {
            $rules = preg_split(Regexp::COMMA_SEP, $rules);
        }
        
        if (!self::isStringArray($rules) || empty($rules)) {
            return $arr;
        }

        $map1 = [];

        foreach ($rules as $rule) {
            $type = 1;
            $securityMode = SecurityMode::STRIP_TAGS;
            $defaultValue = '';

            if (strpos($rule, '@default:') !== false) {
                $defaultValue = StringUtils::substringAfterLast($rule, '@');
                $defaultValue = str_replace('default:', '', $defaultValue);
                $rule = StringUtils::substringBeforeLast($rule, '@');
            }

            if (StringUtils::startsWith($rule, 'i:')) {
                $type = 2;
                $rule = StringUtils::substringAfter($rule, ':');
                $defaultValue = $defaultValue === '' ? PHP_INT_MIN : Cast::toInt($defaultValue);
            } else if (StringUtils::startsWith($rule, 'f:')) {
                $type = 3;
                $rule = StringUtils::substringAfter($rule, ':');
                $defaultValue = $defaultValue === '' ? PHP_FLOAT_MIN : Cast::toFloat($defaultValue);
            } else if (StringUtils::startsWith($rule, 's:')) {
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 'a:')) {
                $type = 4;
                $rule = StringUtils::substringAfter($rule, ':');
            }

            $mapKey = $rule;

            if ($type === 1) {
                if (StringUtils::endsWith($rule, ':0')) {
                    $mapKey = StringUtils::substringBeforeLast($mapKey, ':');
                    $securityMode = SecurityMode::NONE;
                } else if (StringUtils::endsWith($rule, ':1')) {
                    $mapKey = StringUtils::substringBeforeLast($mapKey, ':');
                    $securityMode = SecurityMode::HTML_PURIFY;
                } else if (StringUtils::endsWith($rule, ':2')) {
                    $mapKey = StringUtils::substringBeforeLast($mapKey, ':');
                }
            }

            if (empty($mapKey)) {
                continue;
            }

            $value = isset($arr[$mapKey]) && $arr[$mapKey] !== null ? "$arr[$mapKey]" : '';

            if ($type === 1 && $value !== '' && !is_numeric($value)) {
                switch ($securityMode) {
                    case SecurityMode::STRIP_TAGS:
                        $value = strip_tags($value);
                        break;
                    case SecurityMode::HTML_PURIFY:
                        $value = HtmlPurifier::purify($value);
                        break;
                }
            }

            switch ($type) {
                case 2:
                    $map1[$mapKey] = Cast::toInt($value, $defaultValue);
                    break;
                case 3:
                    $map1[$mapKey] = Cast::toFloat($value, $defaultValue);
                    break;
                case 4:
                    if (StringUtils::startsWith($value, '{') && StringUtils::endsWith($value, '}')) {
                        $value = JsonUtils::mapFrom($value);

                        if (!is_array($value)) {
                            $value = [];
                        }
                    } else if (StringUtils::startsWith($value, '[') && StringUtils::endsWith($value, ']')) {
                        $value = JsonUtils::arrayFrom($value);
                    } else {
                        $value = [];
                    }

                    $map1[$mapKey] = $value;
                    break;
                default:
                    $map1[$mapKey] = $value;
                    break;
            }
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
}
