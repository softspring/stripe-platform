<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\CustomerBundle\Model\Customer;
use Softspring\CustomerBundle\Model\CustomerBillingAddressInterface;
use Softspring\CustomerBundle\Model\CustomerBillingAddressTrait;
use Softspring\CustomerBundle\Model\PlatformObjectTrait;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;

class CustomerFullExample extends Customer implements CustomerBillingAddressInterface, PlatformObjectInterface
{
    use PlatformObjectTrait;
    use CustomerBillingAddressTrait;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}