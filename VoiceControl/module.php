<?php

include_once(__DIR__ . '/../shared/BaseModule.php');
include_once(__DIR__ . '/AlexaDiscoveryHandler.php');
include_once(__DIR__ . '/AlexaControlHandler.php');
include_once(__DIR__ . '/AlexaHealthHandler.php');
include_once(__DIR__ . '/AlexaErrorHandler.php');

class VoiceControlService extends IPSBaseModule
{
    /**
     * Register module for OAuth client id
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->RegisterOAuth('loci_smarthome');
    }

    /**
     * Debug the discovery response - use module actions to do so
     */
    public function DebugDiscoveryResponse()
    {
        $handler = new AlexaDiscoveryHandler($this);
        $json = json_encode($handler->getResponse(array()));
        $this->SendDebug('Voice response', $json, 0);
    }

    /**
     * Make the module ID available for the AlexaHandlers
     *
     * @return int
     */
    public function getModuleId()
    {
        return $this->InstanceID;
    }

    /**
     * Make debug logging available for the AlexaHandlers
     *
     * @param $message string
     */
    public function debug($message)
    {
        $this->SendDebug('Voice service debug', $message, 0);
    }

    /**
     * Register/update the module reference for OAuth handling
     *
     * @param $identifier string
     */
    private function RegisterOAuth($identifier)
    {
        // get all oauth instances
        $ids = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}');
        if (sizeof($ids) == 0) {
            return;
        }
        $oAuthInstanceId = $ids[0];

        $clientIDs = json_decode(IPS_GetProperty($oAuthInstanceId, 'ClientIDs'), true);
        $found = false;
        foreach ($clientIDs as $index => $clientID) {
            // client id already registered
            if ($clientID['ClientID'] == $identifier) {
                // already registered to the right instance
                if ($clientID['TargetID'] == $this->InstanceID) {
                    return;
                }
                // update client id to instance relation
                $clientIDs[$index]['TargetID'] = $this->InstanceID;
                $found = true;
                break;
            }
        }
        // register client id
        if (!$found) {
            $clientIDs[] = array('ClientID' => $identifier, 'TargetID' => $this->InstanceID);
        }

        // update list
        IPS_SetProperty($oAuthInstanceId, 'ClientIDs', json_encode($clientIDs));
        IPS_ApplyChanges($oAuthInstanceId);
    }

    /**
     * Handle a forwarded request and echo the result
     */
    protected function ProcessOAuthData()
    {
        // get all incoming data
        $jsonRequest = file_get_contents('php://input');

        $this->SendDebug('Voice request', $jsonRequest, 0);

        // extract smarthome meta data
        $event = json_decode($jsonRequest, true);
        $namespace = $event['header']['namespace'];
        $name = $event['header']['name'];

        $handler = null;

        // discover request
        if ($namespace == 'Alexa.ConnectedHome.Discovery' && $name == 'DiscoverAppliancesRequest') {
            $handler = new AlexaDiscoveryHandler($this);
        }
        // control request
        elseif ($namespace == 'Alexa.ConnectedHome.Control') {
            $handler = new AlexaControlHandler($this);
        }
        // healthcheck request
        elseif ($namespace == 'Alexa.ConnectedHome.System' && $name == 'HealthCheckRequest') {
            $handler = new AlexaHealthHandler($this);
        }

        // error handler
        if (!$handler) {
            $handler = new AlexaErrorHandler($this);
        }

        $json = json_encode($handler->getResponse($event));
        $this->SendDebug('Voice response', $json, 0);
        echo $json;
    }
}
