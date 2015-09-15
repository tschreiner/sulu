<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes a combination of accounts and contacts available through a REST API.
 */
class CustomerController extends RestController implements ClassResourceInterface
{
    protected static $entityKey = 'customers';

    /**
     * Returns list of contacts and organizations.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $result = $this->getCustomerManager()->findByIds($ids);

        $list = new ListRepresentation(
            $result,
            self::$entityKey,
            'get_customers',
            $request->query->all(),
            1,
            999,
            count($result)
        );
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    private function getCustomerManager()
    {
        return $this->get('sulu_contact.customer_manager');
    }
}
