<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use ReflectionClass;
use ReflectionException;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

/**
 * This class handles the permission information for PHPCR nodes.
 */
class PhpcrAccessControlProvider implements AccessControlProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param array $permissions
     */
    public function __construct(DocumentManagerInterface $documentManager, array $permissions)
    {
        $this->documentManager = $documentManager;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $roleId => $rolePermissions) {
            $allowedPermissions[$roleId] = $this->getAllowedPermissions($rolePermissions);
        }

        $document = $this->documentManager->find($identifier);
        $document->setPermissions($allowedPermissions);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($type, $identifier)
    {
        $permissions = [];

        try {
            $document = $this->documentManager->find($identifier);
        } catch (DocumentNotFoundException $e) {
            return $permissions;
        }

        $allowedPermissions = $document->getPermissions();

        foreach ($allowedPermissions as $roleId => $rolePermissions) {
            $permissions[$roleId] = [];
            foreach ($this->permissions as $permission => $value) {
                $permissions[$roleId][$permission] = in_array($permission, $rolePermissions);
            }
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        try {
            $class = new ReflectionClass($type);
        } catch (ReflectionException $e) {
            // in case the class does not exist there is no support
            return false;
        }

        return $class->implementsInterface(SecurityBehavior::class);
    }

    /**
     * Extracts the keys of the allowed permissions into an own array.
     *
     * @param $permissions
     *
     * @return array
     */
    private function getAllowedPermissions($permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $permission => $allowed) {
            if ($allowed) {
                $allowedPermissions[] = $permission;
            }
        }

        return $allowedPermissions;
    }
}
