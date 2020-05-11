<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\CustomerBundle\Model\Customer;
use Softspring\CustomerBundle\Model\PlatformObjectTrait;

class CustomerBaseExample extends Customer
{
    use PlatformObjectTrait;
}