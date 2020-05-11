<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformByObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Source;

class SourceTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    public function supports($source): bool
    {
        return $source instanceof SourceInterface;
    }

    /**
     * @param SourceInterface|PlatformObjectInterface $source
     * @param string                                  $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($source, string $action = ''): array
    {
        $this->checkSupports($source);

        $data = [
            'source' => [],
        ];

        if ($action == 'create') {
            $data['source'] = $source->getPlatformToken();
        }

        return $data;
    }

    /**
     * @param Source                                       $stripeSource
     * @param SourceInterface|PlatformObjectInterface|null $source
     * @param string                                       $action
     *
     * @return SourceInterface
     * @throws TransformException
     */
    public function reverseTransform($stripeSource, $source = null, string $action = ''): SourceInterface
    {
        if (null === $source) {
            // TODO CALL MANAGER TO CREATE ONE CUSTOMER OBJECT
        }

        $this->checkSupports($source);

        if ($source instanceof PlatformByObjectInterface) {
            $source->setPlatform('stripe');
        }

        $source->setPlatformId($stripeSource->id);
        $source->setTestMode(!$stripeSource->livemode);
        $source->setPlatformLastSync(\DateTime::createFromFormat('U', $stripeSource->created)); // TODO update last sync date
        $source->setPlatformConflict(false);
        $source->setPlatformData($stripeSource->toArray());

        return $source;
    }
}