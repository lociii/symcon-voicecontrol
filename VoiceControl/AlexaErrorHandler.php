<?php

include_once(__DIR__ . '/../shared/UUID.php');
include_once(__DIR__ . '/AlexaBaseHandler.php');

class AlexaErrorHandler extends AlexaBaseHandler
{
    /**
     * Build and return an error response
     *
     * @param array $event
     * @return array
     */
    public function getResponse(array $event)
    {
        return $this->buildResponse('Alexa.ConnectedHome.System', 'UnsupportedOperationError');
    }
}
