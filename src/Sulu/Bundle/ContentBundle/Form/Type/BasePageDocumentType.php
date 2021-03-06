<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Form\Type;

use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

abstract class BasePageDocumentType extends AbstractStructureBehaviorType
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param SessionManagerInterface $sessionManager
     * @param DocumentManager         $documentManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        DocumentManager $documentManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        parent::setDefaultOptions($options);
        $options->setRequired([
            'webspace_key',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('parent', 'document_object');
        $builder->add('extensions', 'text', ['property_path' => 'extensionsData']);
        $builder->add('resourceSegment', 'text');
        $builder->add('navigationContexts', 'collection', [
            'type' => 'text',
            'allow_add' => true,
            'allow_delete' => true,
        ]);
        $builder->add('redirectType', 'integer');
        $builder->add('redirectTarget', 'document_object');
        $builder->add('redirectExternal', 'text');
        $builder->add('workflowStage', 'integer');
        $builder->add('shadowLocaleEnabled', 'checkbox');
        $builder->add('shadowLocale', 'text'); // TODO: Should be choice of available shadow locales
        $builder->setAttribute('webspace_key', $options['webspace_key']);
        $builder->setAttribute('clear_missing_content', $options['clear_missing_content']);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitDocumentParent']);
    }

    /**
     * Set the document parent to be the webspace content path
     * when the document has no parent.
     *
     * @param FormEvent $event
     */
    public function postSubmitDocumentParent(FormEvent $event)
    {
        $document = $event->getData();

        if ($document->getParent()) {
            return;
        }

        $form = $event->getForm();
        $webspaceKey = $form->getConfig()->getAttribute('webspace_key');
        $parent = $this->documentManager->find($this->sessionManager->getContentPath($webspaceKey));

        if (null === $parent) {
            throw new \InvalidArgumentException(sprintf(
                'Could not determine parent for document with title "%s" in webspace "%s"',
                $document->getTitle(),
                $webspaceKey
            ));
        }

        $document->setParent($parent);
    }
}
