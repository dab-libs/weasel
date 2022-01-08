<?php declare(strict_types=1);

namespace Dab\Weasel;

use ReflectionException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test as SymfonyTest;

class WebTestCase extends SymfonyTest\WebTestCase {
  use TestPropertyInjectTrait;

  protected ?KernelBrowser $client = null;

  /** @throws ReflectionException */
  protected function setUp(): void {
    parent::setUp();

    $this->client = static::createClient();

    $this->beforeInjectProperties();
    $this->injectProperties();
  }
}