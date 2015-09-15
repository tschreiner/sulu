<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * ContentType for Snippets.
 */
class SnippetContent extends ComplexContentType
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var StructureResolverInterface
     */
    protected $structureResolver;

    /**
     * @var array
     */
    private $snippetCache = [];

    /**
     * Constructor.
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        $template
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set data to given property.
     *
     * @param array             $data
     * @param PropertyInterface $property
     */
    protected function setData($data, PropertyInterface $property)
    {
        $refs = isset($data) ? $data : [];
        $property->setValue($refs);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $refs = [];
        if ($node->hasProperty($property->getName())) {
            $refs = $node->getProperty($property->getName())->getString();
        }
        $this->setData($refs, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $this->setData($data, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $snippetReferences = [];
        $values = $property->getValue();

        $values = is_array($values) ? $values : [];

        foreach ($values as $value) {
            if ($value instanceof SnippetBridge) {
                $snippetReferences[] = $value->getUuid();
            } elseif (is_array($value) && array_key_exists('uuid', $value) && UUIDHelper::isUUID($value['uuid'])) {
                $snippetReferences[] = $value['uuid'];
            } elseif (UUIDHelper::isUUID($value)) {
                $snippetReferences[] = $value;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property value must either be a UUID or a Snippet, "%s" given.',
                        gettype($value)
                    )
                );
            }
        }

        $node->setProperty($property->getName(), $snippetReferences, PropertyType::REFERENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        /** @var PageBridge $page */
        $page = $property->getStructure();
        $webspaceKey = $page->getWebspaceKey();
        $locale = $page->getLanguageCode();
        $shadowLocale = null;
        if ($page->getIsShadow()) {
            $shadowLocale = $page->getShadowBaseLanguage();
        }

        $refs = $property->getValue();

        $contentData = [];

        $ids = $this->getUuids($refs);

        foreach ($this->loadSnippets($ids, $webspaceKey, $locale, $shadowLocale) as $snippet) {
            $contentData[] = $snippet['view'];
        }

        return $contentData;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        /** @var PageBridge $page */
        $page = $property->getStructure();
        $webspaceKey = $page->getWebspaceKey();
        $locale = $page->getLanguageCode();
        $shadowLocale = null;
        if ($page->getIsShadow()) {
            $shadowLocale = $page->getShadowBaseLanguage();
        }

        $refs = $property->getValue();
        $ids = $this->getUuids($refs);

        $contentData = [];
        foreach ($this->loadSnippets($ids, $webspaceKey, $locale, $shadowLocale) as $snippet) {
            $contentData[] = $snippet['content'];
        }

        return $contentData;
    }

    /**
     * load snippet and serialize them.
     *
     * additionally cache it by id in this class
     */
    private function loadSnippets($ids, $webspaceKey, $locale, $shadowLocale = null)
    {
        $snippets = [];
        foreach ($ids as $i => $ref) {
            if (!array_key_exists($ref, $this->snippetCache)) {
                $snippet = $this->contentMapper->load($ref, $webspaceKey, $locale);

                if (!$snippet->getHasTranslation() && $shadowLocale !== null) {
                    $snippet = $this->contentMapper->load($ref, $webspaceKey, $shadowLocale);
                }

                $resolved = $this->structureResolver->resolve($snippet);
                $resolved['view']['template'] = $snippet->getKey();

                $this->snippetCache[$ref] = $resolved;
            }

            $snippets[] = $this->snippetCache[$ref];
        }

        return $snippets;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $data = $property->getValue();

        return $this->getUuids($data);
    }

    /**
     * The data is not always normalized, so we normalize the data here.
     */
    private function getUuids($data)
    {
        $ids = is_array($data) ? $data : [];

        return $ids;
    }
}
