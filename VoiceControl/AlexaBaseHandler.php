<?php

abstract class AlexaBaseHandler {
    protected $module;

    /**
     * Every AlexHandler will get access to the voice service module
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * Must be implemented by every AlexaHandler and return the full response
     *
     * @abstract
     * @param array $event
     * @return mixed
     */
    abstract public function getResponse(array $event);

    /**
     * Helper function to build a full response
     *
     * @param $namespace string
     * @param $name string
     * @param null $payload array
     * @return array
     */
    protected function buildResponse($namespace, $name, $payload=null)
    {
        if ($payload === null) {
            $payload = new StdClass();
        }
        return array(
            'header' => array(
                'namespace' => $namespace, 'name' => $name,
                'payloadVersion' => '2', 'messageId' => UUID::generate()),
            'payload' => $payload);
    }
}
