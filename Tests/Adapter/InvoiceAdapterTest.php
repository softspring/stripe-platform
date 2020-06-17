<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use Softspring\PaymentBundle\Model\InvoiceInterface;
use Softspring\PlatformBundle\Stripe\Adapter\ConceptAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\InvoiceAdapter;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ConceptExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\InvoiceExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Transformer\InvoiceTransformer;
use Stripe\Customer;
use Stripe\Invoice;

class InvoiceAdapterTest extends AbstractStripeAdapterTest
{
    /**
     * @var InvoiceAdapter
     */
    protected $adapter;

    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var StripeClient
     */
    protected $stripeClient;

    protected function setUp(): void
    {
        $this->stripeClient = $this->createMock(StripeClient::class);

        $this->stripeClientProvider = $this->createMock(StripeClientProvider::class);
        $this->stripeClientProvider->method('getClient')->willReturn($this->stripeClient);

        $conceptAdapter = $this->createMock(ConceptAdapter::class);
        $this->adapter = new InvoiceAdapter($this->stripeClientProvider, new InvoiceTransformer(),  $conceptAdapter, null);
    }

    public function testGetExisting()
    {
        $invoice = new InvoiceExample();
        $invoice->setPlatformId('in_test');

        $this->stripeClient->method('invoiceRetrieve')->will($this->returnValue($this->createStripeInvoiceObject([
            'id' => 'in_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'status' => 'open',
            'total' => 1099,
            'currency' => 'usd',
        ])));

        $stripeInvoice = $this->adapter->get($invoice);
        $this->assertInstanceOf(Invoice::class, $stripeInvoice);
        $this->assertEquals('in_test', $invoice->getPlatformId());
        $this->assertEquals(true, $invoice->isPlatformTestMode());
        $this->assertEquals(false, $invoice->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $invoice->getPlatformLastSync()->format('Y-m-d H:i:s'));
        $this->assertEquals(10.99, $invoice->getTotal());
        $this->assertEquals('USD', $invoice->getCurrency());
        $this->assertEquals(InvoiceInterface::STATUS_PENDING, $invoice->getStatus());
        $this->assertEquals('pending', $invoice->getStatusString());
    }

    public function testCreate()
    {
        $invoice = new InvoiceExample();
        $invoice->setDate(new \DateTime('now'));
        $invoice->addConcept(new ConceptExample());
        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_test');
        $invoice->setCustomer($customer);

        $this->stripeClient->method('invoiceCreate')->will($this->returnValue($this->createStripeInvoiceObject([
            'id' => 'in_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'status' => 'open',
            'total' => 1099,
            'currency' => 'usd',
        ])));

        $this->adapter->create($invoice);
        $this->assertEquals('in_test', $invoice->getPlatformId());
        $this->assertEquals(true, $invoice->isTestMode());
        $this->assertEquals(false, $invoice->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $invoice->getPlatformLastSync()->format('Y-m-d H:i:s'));
        $this->assertEquals(10.99, $invoice->getTotal());
        $this->assertEquals('USD', $invoice->getCurrency());
        $this->assertEquals(InvoiceInterface::STATUS_PENDING, $invoice->getStatus());
        $this->assertEquals('pending', $invoice->getStatusString());
    }
}
