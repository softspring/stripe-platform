<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\PaymentBundle\Model\Payment;
use Softspring\PaymentBundle\Model\PaymentRefersInvoiceInterface;
use Softspring\PaymentBundle\Model\PaymentRefersInvoiceTrait;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;

class PaymentExample extends Payment implements PlatformObjectInterface, PaymentRefersInvoiceInterface
{
    use PlatformObjectTrait;
    use PaymentRefersInvoiceTrait;
}