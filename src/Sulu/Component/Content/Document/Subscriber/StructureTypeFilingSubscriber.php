<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\StructureTypeFilingBehavior;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AbstractFilingSubscriber;

/**
 * Automatically set the parnet at a pre-determined location.
 */
class StructureTypeFilingSubscriber extends AbstractFilingSubscriber
{
    /**
     * {@inheritdoc}
     */
    protected function supports($document)
    {
        return $document instanceof StructureTypeFilingBehavior;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentName($document)
    {
        return $document->getStructureType();
    }
}
