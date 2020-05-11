<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\CustomerBundle\Model\Customer;
use Softspring\CustomerBundle\Model\CustomerAddressesInterface;
use Softspring\CustomerBundle\Model\CustomerAddressesTrait;
use Softspring\CustomerBundle\Model\PlatformObjectTrait;

class CustomerWithAddressesExample extends Customer implements CustomerAddressesInterface
{
    use PlatformObjectTrait;
    use CustomerAddressesTrait;
}