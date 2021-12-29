<?php declare(strict_types=1);

namespace Dab\Weasel;

use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WeaselTestCase extends KernelTestCase {
  protected function setUp(): void {
    parent::setUp();

    if (!self::$booted) {
      self::bootKernel();
    }

    $this->beforeInjectProperties();
    $this->injectProperties();
  }

  protected function beforeInjectProperties() {
  }

  /** @throws ReflectionException */
  protected function injectProperties(): void {
    $thisReflectionClass = new ReflectionClass(self::class);
    $class = static::class;
    $reflectionClass = new ReflectionClass($class);
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
      if (static::$container->has($propertyClassName)) {
        $reflectionProperty->setAccessible(true);
        if (!$reflectionProperty->isInitialized($this) || $reflectionProperty->getValue($this) === null) {
          $propertyValue = static::$container->get($propertyClassName);
          $reflectionProperty->setValue($this, $propertyValue);
        }
      }
      else {
        self::fail(<<<MSG
Can not find the dependency ($propertyClassName) for the property '{$reflectionProperty->getName()}' of the class '$class'
MSG
        );
      }
    }
  }
}