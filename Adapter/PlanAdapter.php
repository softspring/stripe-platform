<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Adapter\PlanAdapterInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\PlanTransformer;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Stripe\Plan;

class PlanAdapter implements PlanAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var PlanTransformer
     */
    protected $planTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * PlanAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param PlanTransformer      $planTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, PlanTransformer $planTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->planTransformer = $planTransformer;
        $this->logger = $logger;
    }

    /**
     * @return PlatformTransformerInterface
     */
    public function getTransformer(): ?PlatformTransformerInterface
    {
        return $this->planTransformer;
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     *
     * @return Plan
     * @throws PlatformException
     */
    public function create(PlanInterface $plan)
    {
        $data = $this->planTransformer->transform($plan, 'create');

        $planStripe = $this->stripeClientProvider->getClient($plan)->planCreate($data['plan']);

        $this->logger && $this->logger->info(sprintf('Stripe created plan %s', $planStripe->id));

        $this->planTransformer->reverseTransform($planStripe, $plan);

        return $planStripe;
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     *
     * @return Plan
     * @throws PlatformException
     */
    public function get(PlanInterface $plan)
    {
        $planStripe = $this->stripeClientProvider->getClient($plan)->planRetrieve([
            'id' => $plan->getPlatformId(),
        ]);

        $this->planTransformer->reverseTransform($planStripe, $plan);

        return $planStripe;
    }

    public function update(PlanInterface $plan): void
    {
        // TODO: Implement update() method.
    }

    public function delete(PlanInterface $plan): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return Collection|Plan[]
     * @throws PlatformException
     */
    public function list(): Collection
    {
        $plans = $this->stripeClientProvider->getClient(null)->planList();

        return new ArrayCollection($plans->getIterator()->getArrayCopy());
    }
}