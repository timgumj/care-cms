<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function initializeContainer(): void
    {
        $maintenance=new \App\Service\CacheMaintenance($this->getProjectDir(),$this->environment);
        $lock=$maintenance->begin($this->getCacheDir(),$this->getBuildDir(),$this->getContainerClass());
        try {
            parent::initializeContainer();
            if ($lock) $maintenance->complete($lock);
        } finally { $maintenance->release($lock); }
    }
}
