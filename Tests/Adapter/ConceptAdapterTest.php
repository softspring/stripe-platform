<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use Softspring\PlatformBundle\Stripe\Adapter\ConceptAdapter;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ConceptExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Transformer\ConceptTransformer;
use Stripe\Customer;
use Stripe\InvoiceItem;

class ConceptAdapterTest extends AbstractStripeAdapterTest
{
    /**
     * @var ConceptAdapter
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

        $this->adapter = new ConceptAdapter($this->stripeClientProvider, new ConceptTransformer(), null);
    }

    public function testGetExisting()
    {
        $concept = new ConceptExample();
        $concept->setPlatformId('ii_test');

        $this->stripeClient->method('invoiceItemRetrieve')->will($this->returnValue($this->createStripeInvoiceItemObject([
            'id' => 'ii_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'description' => 'Test concept',
            'amount' => 1099 * 2,
            'unit_amount' => 1099,
            'quantity' => 2,
            'currency' => 'usd',
        ])));

        $stripeConcept = $this->adapter->get($concept);
        $this->assertInstanceOf(InvoiceItem::class, $stripeConcept);
        $this->assertEquals('ii_test', $concept->getPlatformId());
        $this->assertEquals(true, $concept->isPlatformTestMode());
        $this->assertEquals(false, $concept->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $concept->getPlatformLastSync()->format('Y-m-d H:i:s'));
        $this->assertEquals('Test concept', $concept->getConcept());
        $this->assertEquals(10.99 * 2, $concept->getTotal());
        $this->assertEquals(10.99, $concept->getPrice());
        $this->assertEquals(2, $concept->getQuantity());
        $this->assertEquals('USD', $concept->getCurrency());
    }

    public function testCreate()
    {
        $concept = new ConceptExample();
        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_test');
        $concept->setCustomer($customer);

        $this->stripeClient->method('invoiceItemCreate')->will($this->returnValue($this->createStripeInvoiceItemObject([
            'id' => 'ii_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'description' => 'Test concept',
            'amount' => 1099 * 2,
            'unit_amount' => 1099,
            'quantity' => 2,
            'currency' => 'usd',
            'customer' => $this->createStripeObject(Customer::class, ['id' => 'cus_test']),
        ])));

        $this->adapter->create($concept);
        $this->assertEquals('ii_test', $concept->getPlatformId());
        $this->assertEquals(true, $concept->isTestMode());
        $this->assertEquals(false, $concept->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $concept->getPlatformLastSync()->format('Y-m-d H:i:s'));
    }
}
