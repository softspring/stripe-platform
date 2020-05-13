<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformByObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\StripeObject;

abstract class AbstractPlatformTransformer implements PlatformTransformerInterface
{
    /**
     * @param object $dbObject
     *
     * @throws TransformException
     */
    protected function checkSupports($dbObject)
    {
        if (!$this->supports($dbObject)) {
            throw new TransformException('stripe', sprintf('%s object is not supported by %s', get_class($dbObject), self::class));
        }

        if (!$dbObject instanceof PlatformObjectInterface) {
            throw new TransformException('stripe', sprintf('%s object must be a %s', get_class($dbObject), PlatformObjectInterface::class));
        }
    }

    protected function reverseTransformPlatformObject(PlatformObjectInterface $platformObject, StripeObject $stripeObject)
    {
        if ($platformObject instanceof PlatformByObjectInterface) {
            $platformObject->setPlatform('stripe');
        }

        $platformObject->setPlatformId($stripeObject->id);

        if (isset($stripeObject->livemode)) {
            $platformObject->setTestMode(!$stripeObject->livemode);
        }

        if (isset($stripeObject->created)) {
            $platformObject->setPlatformLastSync(\DateTime::createFromFormat('U', $stripeObject->created)); // TODO update last sync date
        }
        $platformObject->setPlatformConflict(false);
        $platformObject->setPlatformData($stripeObject->toArray());
    }
}