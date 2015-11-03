<?php

namespace Raulr\GooglePlayScraper\Exception;

/**
 * @author Raul Rodriguez <raul@raulr.net>
 */
class NotFoundException extends RequestException
{
    public function __construct($message = '', $code = 404, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
