<?php

namespace App;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

/**
 * Class Kernel
 *
 * Kernel init class
 *
 * @package App
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
