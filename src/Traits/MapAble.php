<?php

namespace Goal\Common\Traits;

use Goal\Common\Util\ArrayUtils;
use Goal\Common\Util\StringUtils;
use ReflectionClass;
use Throwable;

trait MapAble
{
    public function fromMap(array $data, array $mapKeyMappings = []): void
    {
        if (empty($mapKeyMappings) && method_exists($this, 'getMapKeyMappings')) {
            try {
                $mapKeyMappings = $this->getMapKeyMappings();
            } catch (Throwable $ex) {
                $mapKeyMappings = [];
            }

            if (!ArrayUtils::isAssocArray($mapKeyMappings)) {
                $mapKeyMappings = [];
            }
        }

        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                unset($data[$key]);
                continue;
            }

            $pname = '';

            foreach ($mapKeyMappings as $propertyName => $mapKey) {
                if ($mapKey === $key) {
                    $pname = $propertyName;
                    break;
                }
            }

            if (empty($pname)) {
                $pname = $key;
                $needUcwords = false;

                if (strpos($pname, '-') !== false) {
                    $pname = str_replace('-', ' ', $pname);
                    $needUcwords = true;
                } else if (strpos($pname, '_') !== false) {
                    $pname = str_replace('_', ' ', $pname);
                    $needUcwords = true;
                }

                if ($needUcwords) {
                    $pname = ucwords($pname);
                    $pname = str_replace(' ', '', $pname);
                }

                $pname = lcfirst($pname);
            }

            if (empty($pname) || !property_exists($this, $pname)) {
                continue;
            }

            if (is_string($value)) {
                if (strpos($value, '@Duration:') !== false) {
                    $value = StringUtils::toDuration(str_replace('@Duration:', '', $value));
                } else if (strpos($value, '@DataSize:') !== false) {
                    $value = StringUtils::toDataSize(str_replace('@DataSize:', '', $value));
                }
            }

            try {
                $this->$pname = $value;
            } catch (Throwable $ex) {
            }
        }
    }

    public function toMap(array $mapKeyMappings = [], bool $ignoreNull = false): array
    {
        try {
            $clazz = new ReflectionClass(StringUtils::ensureLeft(get_class($this), "\\"));
        } catch (Throwable $ex) {
            $clazz = null;
        }

        if (!($clazz instanceof ReflectionClass)) {
            return [];
        }

        if (empty($mapKeyMappings) && method_exists($this, 'getMapKeyMappings')) {
            try {
                $mapKeyMappings = $this->getMapKeyMappings();
            } catch (Throwable $ex) {
                $mapKeyMappings = [];
            }

            if (!ArrayUtils::isAssocArray($mapKeyMappings)) {
                $mapKeyMappings = [];
            }
        }

        $map1 = [];

        foreach ($clazz->getProperties() as $property) {
            $pname = $property->getName();

            if ($pname === '') {
                continue;
            }

            try {
                $value = $this->$pname;
            } catch (Throwable $ex) {
                continue;
            }

            if ($ignoreNull && $value === null) {
                continue;
            }

            $key = '';

            foreach ($mapKeyMappings as $propertyName => $mapKey) {
                if ($propertyName === $pname) {
                    $key = $mapKey;
                    break;
                }
            }

            if ($key === '') {
                $key = $pname;
            }

            if ($key === '') {
                continue;
            }

            $map1[$key] = $value;
        }

        return $map1;
    }
}
