<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LocalStorage extends AbstractStorage
{
    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var int
     */
    private $segments;

    /**
     * @var NullLogger|LoggerInterface
     */
    protected $logger;

    /**
     * @param string $uploadPath
     * @param int $segments
     * @param null $logger
     */
    public function __construct($uploadPath, $segments, $logger = null)
    {
        $this->uploadPath = $uploadPath;
        $this->segments = $segments;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $preferredStorageOptions = null)
    {
        $this->storageOptions = new \stdClass();

        $segment = '01';
        if ($preferredStorageOptions) {
            $preferredStorageOptions = json_decode($preferredStorageOptions);
            if (!empty($preferredStorageOptions->segment)) {
                $segment = $preferredStorageOptions->segment;
            }
        } else {
            $segment = sprintf('%0' . strlen($this->segments) . 'd', rand(1, $this->segments));
        }

        $segmentPath = $this->uploadPath . '/' . $segment;
        $fileName = $this->getUniqueFileName($segmentPath, $fileName);
        $filePath = $this->getPathByFolderAndFileName($segmentPath, $fileName);
        $this->logger->debug('Check FilePath: ' . $filePath);

        if (!$this->exists($segmentPath)) {
            $this->logger->debug('Try Create Folder: ' . $segmentPath);
            mkdir($segmentPath, 0777, true);
        }

        $this->logger->debug('Try to copy File "' . $tempPath . '" to "' . $filePath . '"');
        if ($this->exists($filePath)) {
            throw new FilenameAlreadyExistsException($filePath);
        }
        copy($tempPath, $filePath);

        $this->addStorageOption('segment', $segment);
        $this->addStorageOption('fileName', $fileName);

        return json_encode($this->storageOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function load($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if ($segment && $fileName) {
            return fopen($this->uploadPath . '/' . $segment . '/' . $fileName, 'r');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadUrl($storageOptions)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if ($segment && $fileName) {
            @unlink($this->uploadPath . '/' . $segment . '/' . $fileName);

            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function exists($filePath)
    {
        return file_exists($filePath);
    }
}
