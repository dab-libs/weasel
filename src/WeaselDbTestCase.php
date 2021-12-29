<?php declare(strict_types=1);

namespace Dab\Weasel;

use Doctrine\ORM\EntityManagerInterface;

class WeaselDbTestCase extends WeaselTestCase {
  /** @RequiredForTest */
  protected ?EntityManagerInterface $entityManager = null;

  protected function setUp(): void {
    parent::setUp();

    $this->entityManager->beginTransaction();
  }

  public function tearDown(): void {
    $this->entityManager->rollback();
    $this->entityManager->close();
    parent::tearDown();
  }
}