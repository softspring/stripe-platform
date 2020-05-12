<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\CustomerBundle\Model\Source;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;

class SourceExample extends Source implements PlatformObjectInterface
{
    use PlatformObjectTrait;
}