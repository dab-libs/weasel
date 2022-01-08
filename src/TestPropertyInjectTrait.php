<?php

namespace Dab\Weasel;

use ReflectionClass;
use ReflectionException;

trait TestPropertyInjectTrait {
  protected function beforeInjectProperties(): void {
  }

  protected function afterInjectProperties(array $injectedValues): void {
    foreach ($injectedValues as $value) {
      if ($value instanceof Fixture) {
        $value->createData();
      }
    }
  }

  /** @throws ReflectionException */
  protected function injectProperties(): void {
    $thisReflectionClass = new ReflectionClass(self::class);
    $class = static::class;
    $reflectionClass = new ReflectionClass($class);
    $injectedValues = [];
    $container = static::getContainer();
    foreach ($reflectionClass->getProperties() as $reflectionProperty) {
      if (!$reflectionProperty->getDeclaringClass()->isSubclassOf($thisReflectionClass)) {
        continue;
      }
      if ($reflectionProperty->isStatic()) {
        continue;
      }
      $docComment = $reflectionProperty->getDocComment();
      if ($docComment === false || !str_contains($docComment, '@RequiredForTest')) {
        continue;
      }
      if (!$reflectionProperty->hasType()) {
        continue;
      }
      $propertyClassName = $reflectionProperty->getType()->getName();
      if ($container->has($propertyClassName)) {
        $reflectionProperty->setAccessible(true);
        if (!$reflectionProperty->isInitialized($this) || $reflectionProperty->getValue($this) === null) {
          $propertyValue = $container->get($propertyClassName);
          $reflectionProperty->setValue($this, $propertyValue);
          $injectedValues[spl_object_hash($propertyValue)] = $propertyValue;
        }
      }
      else {
        self::fail(<<<MSG
Can not find the dependency ($propertyClassName) for the property '{$reflectionProperty->getName()}' of the class '$class'
MSG
        );
      }
    }
    $this->afterInjectProperties(array_values($injectedValues));
  }
}