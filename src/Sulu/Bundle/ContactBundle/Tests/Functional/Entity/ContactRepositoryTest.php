<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Contact[]
     */
    private $contacts = [];

    /**
     * @var Tag[]
     */
    private $tags = [];

    /**
     * @var array
     */
    private $tagData = [
        'Tag-0',
        'Tag-1',
        'Tag-2',
        'Tag-3',
    ];

    /**
     * @var array
     */
    private $contactData = [
        ['Max', 'Mustermann', [0, 1, 2]],
        ['Anne', 'Mustermann', [0, 1, 3]],
        ['Georg', 'Mustermann', [0, 1]],
        ['Marianne', 'Mustermann', [0, 1, 2]],
        ['Franz-Xaver', 'Gabler', [0]],
        ['Markus', 'Mustermann', [0]],
        ['Erika', 'Mustermann', [0]],
        ['Leon', 'Mustermann', []],
    ];

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();

        foreach ($this->tagData as $data) {
            $this->tags[] = $this->createTag($data);
        }

        foreach ($this->contactData as $data) {
            $this->contacts[] = $this->createContact($data[0], $data[1], $data[2]);
        }

        $this->em->flush();
    }

    private function createTag($name)
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    private function createContact($firstName, $lastName, $tags = [])
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setFormOfAddress(0);

        foreach ($tags as $tag) {
            $contact->addTag($this->tags[$tag]);
        }

        $this->em->persist($contact);

        return $contact;
    }

    public function dataProvider()
    {
        // when pagination is active the result count is pageSize + 1 to determine has next page

        return [
            // no pagination
            [[], null, 0, null, $this->contactData],
            // page 1, no limit
            [[], 1, 3, null, array_slice($this->contactData, 0, 4)],
            // page 2, no limit
            [[], 2, 3, null, array_slice($this->contactData, 3, 4)],
            // page 3, no limit
            [[], 3, 3, null, array_slice($this->contactData, 6, 2)],
            // no pagination, limit 3
            [[], null, 0, 3, array_slice($this->contactData, 0, 3)],
            // page 1, limit 5
            [[], 1, 3, 5, array_slice($this->contactData, 0, 4)],
            // page 2, limit 5
            [[], 2, 3, 5, array_slice($this->contactData, 3, 2)],
            // page 3, limit 5
            [[], 3, 3, 5, []],
            // no pagination, tag 0
            [['tags' => [0], 'tagOperator' => 'or'], null, 0, null, array_slice($this->contactData, 0, 7), [0]],
            // no pagination, tag 0 or 1
            [['tags' => [0, 1], 'tagOperator' => 'or'], null, 0, null, array_slice($this->contactData, 0, 7)],
            // no pagination, tag 0 and 1
            [['tags' => [0, 1], 'tagOperator' => 'and'], null, 0, null, array_slice($this->contactData, 0, 4), [0, 1]],
            // no pagination, tag 0 and 3
            [['tags' => [0, 3], 'tagOperator' => 'and'], null, 0, null, [$this->contactData[1]], [0, 3]],
            // page 1, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                1,
                3,
                null,
                array_slice($this->contactData, 0, 4),
                [0],
            ],
            // page 2, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                2,
                3,
                null,
                array_slice($this->contactData, 3, 4),
                [0],
            ],
            // page 3, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                3,
                3,
                null,
                array_slice($this->contactData, 6, 1),
                [0],
            ],
            // no pagination, website-tag 0
            [
                ['websiteTags' => [0], 'websiteTagOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->contactData, 0, 7),
                [0],
            ],
            // no pagination, website-tag 0 or 1
            [
                ['websiteTags' => [0, 1], 'websiteTagOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->contactData, 0, 7),
            ],
            // no pagination, website-tag 0 and 1
            [
                ['websiteTags' => [0, 1], 'websiteTagOperator' => 'and'],
                null,
                0,
                null,
                array_slice($this->contactData, 0, 4),
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 3
            [
                ['websiteTags' => [1], 'websiteTagOperator' => 'or', 'tags' => [3], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->contactData[1]],
                [0, 3],
            ],
            // no pagination, website-tag 2 or 3, tags 1
            [
                ['websiteTags' => [2, 3], 'websiteTagOperator' => 'or', 'tags' => [1], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->contactData[0], $this->contactData[1], $this->contactData[3]],
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 2 or 3
            [
                ['websiteTags' => [1], 'websiteTagOperator' => 'or', 'tags' => [2, 3], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->contactData[0], $this->contactData[1], $this->contactData[3]],
                [0, 1],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @param int $limit
     * @param array $expected
     * @param int[] $tags
     */
    public function testFindBy($filters, $page, $pageSize, $limit, $expected, $tags = [])
    {
        $repository = $this->em->getRepository(Contact::class);

        // if tags isset replace the array indexes with database id
        if (array_key_exists('tags', $filters)) {
            $filters['tags'] = array_map(
                function ($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['tags']
            );
        }

        // if tags isset replace the array indexes with database id
        if (array_key_exists('websiteTags', $filters)) {
            $filters['websiteTags'] = array_map(
                function ($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['websiteTags']
            );
        }

        $result = $repository->findByFilters($filters, $page, $pageSize, $limit);

        $length = count($expected);
        $this->assertCount($length, $result);

        for ($i = 0; $i < $length; ++$i) {
            $this->assertEquals($expected[$i][0], $result[$i]->getFirstName(), $i);
            $this->assertEquals($expected[$i][1], $result[$i]->getLastName(), $i);

            foreach ($tags as $tag) {
                $this->assertTrue($result[$i]->getTags()->contains($this->tags[$tag]));
            }
        }
    }
}
