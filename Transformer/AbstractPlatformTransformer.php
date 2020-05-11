<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\PlatformInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;

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
            throw new TransformException(PlatformInterface::PLATFORM_STRIPE, sprintf('%s object is not supported by %s', get_class($dbObject), self::class));
        }
    }
}