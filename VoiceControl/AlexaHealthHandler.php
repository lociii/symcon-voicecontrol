<?php

include_once(__DIR__ . '/../shared/UUID.php');
include_once(__DIR__ . '/AlexaBaseHandler.php');

class AlexaHealthHandler extends AlexaBaseHandler
{
    /**
     * Build and return a health check response
     * To be honest, I've never seen such a request in realitvy ;-)
     *
     * @param array $event
     * @return array
     */
    public function getResponse(array $event)
    {
        return $this->buildResponse('Alexa.ConnectedHome.System', 'HealthCheckResponse', array(
            'description' => 'All fine', 'isHealthy' => true));
    }
}
