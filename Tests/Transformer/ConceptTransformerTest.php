<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Transformer;

use Softspring\PaymentBundle\Model\ConceptInterface;
use Softspring\PlatformBundle\Stripe\Tests\Examples\AddressExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ConceptExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\InvoiceExample;
use Softspring\PlatformBundle\Stripe\Transformer\ConceptTransformer;
use PHPUnit\Framework\TestCase;
use Stripe\InvoiceItem;

class ConceptTransformerTest extends TestCase
{
    public function testSupports()
    {
        $transformer = new ConceptTransformer();

        $this->assertFalse($transformer->supports(new \stdClass()));
        $this->assertTrue($transformer->supports($this->createMock(ConceptInterface::class)));
    }

    public function testTransform()
    {
        $transformer = new ConceptTransformer();

        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_0000000000');
        $invoice = new InvoiceExample();
        $invoice->setPlatformId('in_0000000000');

        $concept = new ConceptExample();
        $concept->setCustomer($customer);
        $concept->setConcept('Test concept');
        $concept->setCurrency('EUR');
        $concept->setPrice(10.99);
        $concept->setQuantity(5);
        $concept->setInvoice($invoice);

        $this->assertEquals([
            'concept' => [
                'customer' => 'cus_0000000000',
                'invoice' => 'in_0000000000',
                'currency' => 'eur',
                'description' => 'Test concept',
                'quantity' => 5,
                'unit_amount' => 1099,
            ],
        ], $transformer->transform($concept, 'create'));

        $concept = new ConceptExample();
        $concept->setCustomer($customer);
        $concept->setConcept('Test concept');
        $concept->setCurrency('EUR');
        $concept->setTotal(10.99 * 5);
        $concept->setInvoice($invoice);

        $this->assertEquals([
            'concept' => [
                'customer' => 'cus_0000000000',
                'invoice' => 'in_0000000000',
                'currency' => 'eur',
                'description' => 'Test concept',
                'amount' => 10.99 * 5 * 100,
            ],
        ], $transformer->transform($concept, 'create'));
    }

    public function testReverseTransform()
    {
        $transformer = new ConceptTransformer();
        $concept = new ConceptExample();

        $stripeConcept = new InvoiceItem('ii_00000000000');
        $stripeConcept->description = 'Test concept';
        $stripeConcept->amount = 1099 * 2;
        $stripeConcept->unit_amount = 1099;
        $stripeConcept->quantity = 2;
        $stripeConcept->currency = 'usd';

        $transformer->reverseTransform($stripeConcept, $concept);

        $this->assertEquals('ii_00000000000', $concept->getPlatformId());
        // assert customer
        $this->assertEquals('Test concept', $concept->getConcept());
        $this->assertEquals('USD', $concept->getCurrency());
        $this->assertEquals(10.99, $concept->getPrice());
        $this->assertEquals(2, $concept->getQuantity());
        $this->assertEquals(10.99*2, $concept->getTotal());
        // assert invoice
    }
}
