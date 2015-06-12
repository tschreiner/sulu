<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Collaboration;

use Ratchet\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;

/**
 * Handles messages for collaboration.
 *
 * @example {cmd: enter, webspaceKey: sulu_io, user: 1, content: 123-123-123}
 *
 * The example calls the enter action and passes the current page and the user id 
 * as message parameter
 */
class CollaborationMessageHandler implements MessageHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * An array which contains all the users for a certain identifier (representing a page, product, ...)
     * @var array
     */
    private $users = array();

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context)
    {
        try {
            return $this->execute($conn, $context, $message);
        } catch (\Exception $ex) {
            // TODO Christian Bader: create exception class
            throw new MessageHandlerException($ex);
        }
    }

    /**
     * Executes command.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     *
     * @return mixed|null
     *
     * @throws MissingParameterException
     */
    private function execute(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        if (!array_key_exists('command', $msg)) {
            throw new MissingParameterException('command');
        }
        $command = $msg['command'];
        $result = null;

        switch ($command) {
            case 'enter':
                $result = $this->enter($conn, $context, $msg);
                break;
            case 'leave':
                $result = $this->leave($conn, $context, $msg);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Command "%s" not known', $command));
                break;
        }

        return $result;
    }

    /**
     * Called when the user has entered the page.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     *
     * @return array
     */
    private function enter(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        // TODO check msg keys
        $this->addUser($msg['type'] . $msg['id'], $msg['userId']);

        return array(
            'type' => $msg['type'],
            'id' => $msg['id'],
            'users' => $this->users[$msg['type'] . $msg['id']]
        );
    }

    /**
     * Called when the user has left the page.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param $msg
     *
     * @return array
     */
    private function leave(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        $this->removeUser($msg['type'] . $msg['id'], $msg['userId']);
    }

    private function addUser($id, $userId)
    {
        if (!array_key_exists($id, $this->users)) {
            $this->users[$id] = array();
        }

        if (!in_array($userId, $this->users[$id])) {
            $this->users[$id][] = array('id' => $userId);
        }
    }

    private function removeUser($id, $userId)
    {
        if ($key = array_search(array('id' => $userId), $this->users[$id])) {
            unset($this->users[$id][$key]);
        }
    }
}
