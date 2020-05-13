<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PaymentBundle\Model\ConceptInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Adapter\ConceptAdapterInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\ConceptTransformer;
use Stripe\InvoiceItem;

class ConceptAdapter implements ConceptAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var ConceptTransformer
     */
    protected $conceptTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * ConceptAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param ConceptTransformer   $conceptTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, ConceptTransformer $conceptTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->conceptTransformer = $conceptTransformer;
        $this->logger = $logger;
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     *
     * @return InvoiceItem
     * @throws PlatformException
     */
    public function create(ConceptInterface $concept)
    {
        $data = $this->conceptTransformer->transform($concept, 'create');

        $conceptStripe = $this->stripeClientProvider->getClient($concept)->invoiceItemCreate($data['concept']);

        $this->logger && $this->logger->info(sprintf('Stripe created concept %s', $conceptStripe->id));

        $this->conceptTransformer->reverseTransform($conceptStripe, $concept);

        return $conceptStripe;
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     *
     * @return InvoiceItem
     * @throws PlatformException
     */
    public function get(ConceptInterface $concept)
    {
        $conceptStripe = $this->stripeClientProvider->getClient($concept)->invoiceItemRetrieve($concept->getPlatformId());

        $this->conceptTransformer->reverseTransform($conceptStripe, $concept);

        return $conceptStripe;
    }
}