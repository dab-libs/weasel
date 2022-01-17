<?php

namespace Dab\Weasel;

use Doctrine\ORM\EntityManagerInterface;

trait TestTransactionTrait {
  /** @RequiredForTest */
  protected ?EntityManagerInterface $entityManager = null;

  public function tearDown(): void {
    $this->entityManager->rollback();
    $this->entityManager->close();
    parent::tearDown();
  }

  protected function afterInjectProperties(array $injectedValues): void {
    $this->entityManager->beginTransaction();
    parent::afterInjectProperties($injectedValues);
    $this->entityManager->flush();
  }
}