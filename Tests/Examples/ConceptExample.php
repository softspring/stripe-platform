<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\PaymentBundle\Model\Concept;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;

class ConceptExample extends Concept implements PlatformObjectInterface
{
    use PlatformObjectTrait;
}