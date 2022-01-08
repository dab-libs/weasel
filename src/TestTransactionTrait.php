<?php

namespace Dab\Weasel;

use Doctrine\ORM\EntityManagerInterface;

trait TestTransactionTrait {
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

  protected function afterInjectProperties(array $injectedValues): void {
    parent::afterInjectProperties($injectedValues);
    $this->entityManager->flush();
  }
}