<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader\Exception;

/**
 * Thrown when the xml definition of a template contains an error.
 */
class InvalidXmlException extends TemplateException
{
    public function __construct($template, $message = 'The given XML is invalid.')
    {
        parent::__construct($template, $message);
    }
}
