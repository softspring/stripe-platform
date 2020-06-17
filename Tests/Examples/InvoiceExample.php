<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\PaymentBundle\Model\Invoice;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;

class InvoiceExample extends Invoice implements PlatformObjectInterface
{
    use PlatformObjectTrait;
}