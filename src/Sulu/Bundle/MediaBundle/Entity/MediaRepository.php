<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityRepositoryTrait;

/**
 * MediaRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MediaRepository extends EntityRepository implements MediaRepositoryInterface
{
    use SecuredEntityRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function findMediaById($id, $asArray = false)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('media')
                ->leftJoin('media.type', 'type')
                ->leftJoin('media.collection', 'collection')
                ->leftJoin('media.files', 'file')
                ->leftJoin('file.fileVersions', 'fileVersion')
                ->leftJoin('fileVersion.tags', 'tag')
                ->leftJoin('fileVersion.meta', 'fileVersionMeta')
                ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
                ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
                ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
                ->leftJoin('media.creator', 'creator')
                ->leftJoin('creator.contact', 'creatorContact')
                ->leftJoin('media.changer', 'changer')
                ->leftJoin('changer.contact', 'changerContact')
                ->addSelect('type')
                ->addSelect('collection')
                ->addSelect('file')
                ->addSelect('tag')
                ->addSelect('fileVersion')
                ->addSelect('fileVersionMeta')
                ->addSelect('fileVersionDefaultMeta')
                ->addSelect('fileVersionContentLanguage')
                ->addSelect('fileVersionPublishLanguage')
                ->addSelect('creator')
                ->addSelect('changer')
                ->addSelect('creatorContact')
                ->addSelect('changerContact')
                ->where('media.id = :mediaId');

            $query = $queryBuilder->getQuery();
            $query->setParameter('mediaId', $id);

            if ($asArray) {
                if (isset($query->getArrayResult()[0])) {
                    return $query->getArrayResult()[0];
                } else {
                    return;
                }
            } else {
                return $query->getSingleResult();
            }
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMedia(
        $filter = [],
        $limit = null,
        $offset = null,
        UserInterface $user = null,
        $permission = null
    ) {
        try {
            list($collection, $types, $search, $orderBy, $orderSort, $ids) = $this->extractFilterVars($filter);

            // if empty array of ids is requested return empty array of medias
            if ($ids !== null && count($ids) === 0) {
                return [];
            }

            if (!$ids) {
                $ids = $this->getIds($collection, $types, $search, $orderBy, $orderSort, $limit, $offset);
            }

            $queryBuilder = $this->createQueryBuilder('media')
                ->leftJoin('media.type', 'type')
                ->leftJoin('media.collection', 'collection')
                ->innerJoin('media.files', 'file')
                ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
                ->leftJoin('fileVersion.tags', 'tag')
                ->leftJoin('fileVersion.meta', 'fileVersionMeta')
                ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
                ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
                ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
                ->leftJoin('media.creator', 'creator')
                ->leftJoin('creator.contact', 'creatorContact')
                ->leftJoin('media.changer', 'changer')
                ->leftJoin('changer.contact', 'changerContact')
                ->addSelect('type')
                ->addSelect('collection')
                ->addSelect('file')
                ->addSelect('tag')
                ->addSelect('fileVersion')
                ->addSelect('fileVersionMeta')
                ->addSelect('fileVersionDefaultMeta')
                ->addSelect('fileVersionContentLanguage')
                ->addSelect('fileVersionPublishLanguage')
                ->addSelect('creator')
                ->addSelect('changer')
                ->addSelect('creatorContact')
                ->addSelect('changerContact');

            if ($ids !== null) {
                $queryBuilder->andWhere('media.id IN (:mediaIds)');
            }

            if ($orderBy !== null) {
                $queryBuilder->addOrderBy($orderBy, $orderSort);
            }

            if ($user !== null && $permission !== null) {
                $this->addAccessControl($queryBuilder, $user, $permission, Collection::class, 'collection');
            }

            $query = $queryBuilder->getQuery();
            if ($ids !== null) {
                $query->setParameter('mediaIds', $ids);
            }

            return $query->getResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $filter)
    {
        list($collection, $types, $search, $orderBy, $orderSort, $ids) = $this->extractFilterVars($filter);

        $query = $this->getIdsQuery($collection, $types, $search, $orderBy, $orderSort, null, null, 'COUNT(media)');
        $result = $query->getSingleResult()[1];

        return intval($result);
    }

    /**
     * Extracts filter vars.
     *
     * @param array $filter
     *
     * @return array
     */
    private function extractFilterVars(array $filter)
    {
        $collection = array_key_exists('collection', $filter) ? $filter['collection'] : null;
        $types = array_key_exists('types', $filter) ? $filter['types'] : null;
        $search = array_key_exists('search', $filter) ? $filter['search'] : null;
        $orderBy = array_key_exists('orderBy', $filter) ? $filter['orderBy'] : null;
        $orderSort = array_key_exists('orderSort', $filter) ? $filter['orderSort'] : null;
        $ids = array_key_exists('ids', $filter) ? $filter['ids'] : null;

        return [$collection, $types, $search, $orderBy, $orderSort, $ids];
    }

    /**
     * Returns the most recent version of a media for the specified
     * filename within a collection.
     *
     * @param String $filename
     * @param int $collectionId
     *
     * @return Media
     */
    public function findMediaWithFilenameInCollectionWithId($filename, $collectionId)
    {
        $queryBuilder = $this->createQueryBuilder('media')
            ->innerJoin('media.files', 'files')
            ->innerJoin('files.fileVersions', 'versions', 'WITH', 'versions.version = files.version')
            ->join('media.collection', 'collection')
            ->where('collection.id = :collectionId')
            ->andWhere('versions.name = :filename')
            ->orderBy('versions.created')
            ->setMaxResults(1)
            ->setParameter('filename', $filename)
            ->setParameter('collectionId', $collectionId);
        $result = $queryBuilder->getQuery()->getResult();

        if (count($result) > 0) {
            return $result[0];
        }

        return;
    }

    /**
     * @param $collectionId
     * @param $limit
     * @param $offset
     *
     * @return array
     */
    public function findMediaByCollectionId($collectionId, $limit, $offset)
    {
        $queryBuilder = $this->createQueryBuilder('media')
            ->select('count(media.id) as counter')
            ->join('media.collection', 'collection')
            ->where('collection.id = :collectionId')
            ->setParameter('collectionId', $collectionId);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder = $this->createQueryBuilder('media')
            ->innerJoin('media.files', 'files')
            ->innerJoin('files.fileVersions', 'versions', 'WITH', 'versions.version = files.version')
            ->join('media.collection', 'collection')
            ->where('collection.id = :collectionId')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('collectionId', $collectionId);

        $query = $queryBuilder->getQuery();
        $paginator = new Paginator($query);

        return ['media' => $paginator, 'count' => $count];
    }

    /**
     * create a query for ids with given filter.
     *
     * @param string $collection
     * @param array $types
     * @param string $search
     * @param string $orderBy
     * @param string $orderSort
     * @param int $limit
     * @param int $offset
     * @param string $select
     *
     * @return Query
     */
    private function getIdsQuery(
        $collection = null,
        $types = null,
        $search = null,
        $orderBy = null,
        $orderSort = null,
        $limit = null,
        $offset = null,
        $select = 'media.id'
    ) {
        $subQueryBuilder = $this->createQueryBuilder('media')->select($select);

        if ($collection !== null) {
            $subQueryBuilder->leftJoin('media.collection', 'collection');
            $subQueryBuilder->andWhere('collection.id = :collection');
        }
        if ($types !== null) {
            $subQueryBuilder->leftJoin('media.type', 'type');
            $subQueryBuilder->andWhere('type.name IN (:types)');
        }
        if ($search !== null) {
            $subQueryBuilder
                ->innerJoin('media.files', 'file')
                ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
                ->leftJoin('fileVersion.meta', 'fileVersionMeta');

            $subQueryBuilder->andWhere('fileVersionMeta.title LIKE :search');
        }
        if ($offset) {
            $subQueryBuilder->setFirstResult($offset);
        }
        if ($limit) {
            $subQueryBuilder->setMaxResults($limit);
        }
        if ($orderBy !== null) {
            $subQueryBuilder->addOrderBy($orderBy, $orderSort);
        }

        $subQuery = $subQueryBuilder->getQuery();

        if ($collection !== null) {
            $subQuery->setParameter('collection', $collection);
        }
        if ($types !== null) {
            $subQuery->setParameter('types', $types);
        }
        if ($search !== null) {
            $subQuery->setParameter('search', '%' . $search . '%');
        }

        return $subQuery;
    }

    /**
     * returns ids with given filters.
     *
     * @param string $collection
     * @param array $types
     * @param string $search
     * @param string $orderBy
     * @param string $orderSort
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    private function getIds(
        $collection = null,
        $types = null,
        $search = null,
        $orderBy = null,
        $orderSort = null,
        $limit = null,
        $offset = null
    ) {
        $subQuery = $this->getIdsQuery($collection, $types, $search, $orderBy, $orderSort, $limit, $offset);

        return $subQuery->getScalarResult();
    }
}
