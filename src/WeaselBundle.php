<?php declare(strict_types=1);

namespace Dab\Weasel;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WeaselBundle extends Bundle {
//    public function getContainerExtension() {
//        return new SprutCatalogApiV3Extension();
//    }

  public function getPath() {
    return realpath(parent::getPath());
  }

}