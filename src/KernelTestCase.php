<?php declare(strict_types=1);

namespace Dab\Weasel;

use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test as SymfonyTest;

class KernelTestCase extends SymfonyTest\KernelTestCase {
  use TestPropertyInjectTrait;

  /** @throws ReflectionException */
  protected function setUp(): void {
    parent::setUp();

    if (!self::$booted) {
      self::bootKernel();
    }

    $this->beforeInjectProperties();
    $this->injectProperties();
  }
}