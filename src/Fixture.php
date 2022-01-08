<?php

namespace Dab\Weasel;

interface Fixture {
  public function createData(): void;
}