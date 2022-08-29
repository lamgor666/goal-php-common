<?php

namespace Goal\Common\Util;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;

final class ReflectUtils
{
    private function __construct()
    {
    }

    public static function getClassAnnotation(
        ReflectionClass $refClazz,
        string $annoClass,
        ?array $annotations = null): ?object
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getClassAnnotationPhp7($refClazz, $annoClass, $annotations);
        }

        try {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $annotations = $refClazz->getAttributes();
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    private static function getClassAnnotationPhp7(
        ReflectionClass $refClazz,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (!is_array($annotations)) {
            try {
                $reader = new AnnotationReader();
                $annotations = $reader->getClassAnnotations($refClazz);
            } catch (Throwable $ex) {
                $annotations = [];
            }
        }

        if (!is_array($annotations) || empty($annotations)) {
            return null;
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft(get_class($anno), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return $anno;
            }
        }

        return null;
    }

    public static function getMethodAnnotation(
        ReflectionMethod $method,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getMethodAnnotationPhp7($method, $annoClass, $annotations);
        }

        try {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $annotations = $method->getAttributes();
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    private static function getMethodAnnotationPhp7(
        ReflectionMethod $method,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (!is_array($annotations)) {
            try {
                $reader = new AnnotationReader();
                $annotations = $reader->getMethodAnnotations($method);
            } catch (Throwable $ex) {
                $annotations = [];
            }
        }

        if (!is_array($annotations) || empty($annotations)) {
            return null;
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft(get_class($anno), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return $anno;
            }
        }

        return null;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @param bool $strictMode
     * @return ReflectionMethod|null
     */
    public static function getGetter(
        ReflectionProperty $property,
        array $methods = [],
        bool $strictMode = false
    ): ?ReflectionMethod
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getGetterPhp7($property, $methods);
        }

        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        $fieldType = $property->getType();
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $getter = null;

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();

            if ($strictMode) {
                if (!($fieldType instanceof ReflectionNamedType) ||
                    !($returnType instanceof ReflectionNamedType) ||
                    $returnType->getName() !== $fieldType->getName()) {
                    continue;
                }
            }

            if (strtolower($method->getName()) === "get$fieldName") {
                $getter = $method;
                break;
            }

            $s1 = StringUtils::ensureLeft($fieldName, 'is');
            $s2 = StringUtils::ensureLeft(strtolower($method->getName()), 'is');

            if ($s1 === $s2) {
                $getter = $method;
                break;
            }
        }

        return $getter;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @return ReflectionMethod|null
     */
    public static function getGetterPhp7(ReflectionProperty $property, array $methods = []): ?ReflectionMethod
    {
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $getter = null;

        foreach ($methods as $method) {
            if (strtolower($method->getName()) === "get$fieldName") {
                $getter = $method;
                break;
            }

            $s1 = StringUtils::ensureLeft($fieldName, 'is');
            $s2 = StringUtils::ensureLeft(strtolower($method->getName()), 'is');

            if ($s1 === $s2) {
                $getter = $method;
                break;
            }
        }

        return $getter;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @param bool $strictMode
     * @return ReflectionMethod|null
     */
    public static function getSetter(
        ReflectionProperty $property,
        array $methods = [],
        bool $strictMode = false
    ): ?ReflectionMethod
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getSetterPhp7($property, $methods);
        }

        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        $fieldType = $property->getType();
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $setter = null;

        foreach ($methods as $method) {
            try {
                $args = $method->getParameters();
            } catch (Throwable $ex) {
                $args = [];
            }

            if (count($args) !== 1) {
                continue;
            }

            $argType = $args[0]->getType();

            if ($strictMode) {
                if (!($fieldType instanceof ReflectionNamedType) ||
                    !($argType instanceof ReflectionNamedType) ||
                    $argType->getName() !== $fieldType->getName()) {
                    continue;
                }
            }

            if (strtolower($method->getName()) === "set$fieldName") {
                $setter = $method;
                break;
            }
        }

        return $setter;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @return ReflectionMethod|null
     */
    private static function getSetterPhp7(ReflectionProperty $property, array $methods = []): ?ReflectionMethod
    {
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $setter = null;

        foreach ($methods as $method) {
            try {
                $args = $method->getParameters();
            } catch (Throwable $ex) {
                $args = [];
            }

            if (count($args) !== 1) {
                continue;
            }

            if (strtolower($method->getName()) === "set$fieldName") {
                $setter = $method;
                break;
            }
        }

        return $setter;
    }

    public static function getPropertyAnnotation(
        ReflectionProperty $property,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getPropertyAnnotationPhp7($property, $annoClass, $annotations);
        }

        try {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $annotations = $property->getAttributes();
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    private static function getPropertyAnnotationPhp7(
        ReflectionProperty $property,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (!is_array($annotations)) {
            try {
                $reader = new AnnotationReader();
                $annotations = $reader->getPropertyAnnotations($property);
            } catch (Throwable $ex) {
                $annotations = [];
            }
        }

        if (!is_array($annotations) || empty($annotations)) {
            return null;
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft(get_class($anno), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return $anno;
            }
        }

        return null;
    }

    public static function getParameterAnnotation(
        ReflectionParameter $param,
        string $annoClass,
        ?array $annotations = null
    ): ?object
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return null;
        }

        if (!is_array($annotations)) {
            try {
                /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                $annotations = $param->getAttributes();
            } catch (Throwable $ex) {
                $annotations = [];
            }
        }

        foreach ($annotations as $anno) {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (strpos($clazz, $annoClass) !== false) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    private static function buildAnno($arg0): ?object
    {
        if (!is_object($arg0) || !method_exists($arg0, 'getName') || !method_exists($arg0, 'getArguments')) {
            return null;
        }

        try {
            $className = StringUtils::ensureLeft($arg0->getName(), "\\");
            $clazz = new ReflectionClass($className);
            $arguments = $arg0->getArguments();

            if (is_array($arguments) && !empty($arguments)) {
                $anno = $clazz->newInstance(...$arguments);
            } else {
                $anno = $clazz->newInstance();
            }
        } catch (Throwable $ex) {
            $anno = null;
        }

        return is_object($anno) ? $anno : null;
    }
}
