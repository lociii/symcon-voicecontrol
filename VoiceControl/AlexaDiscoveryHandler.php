<?php

include_once(__DIR__ . '/../shared/UUID.php');
include_once(__DIR__ . '/AlexaBaseHandler.php');

class AlexaDiscoveryHandler extends AlexaBaseHandler
{
    private $children = array();

    /**
     * Update our child tree on construction
     *
     * @param $module
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->updateChildren($this->module->getModuleId());
    }

    /**
     * * Build the discovery response
     *
     * @param $event array
     * @return array
     */
    public function getResponse(array $event)
    {
        return $this->buildResponse('Alexa.ConnectedHome.Discovery', 'DiscoverAppliancesResponse', array(
            'discoveredAppliances' => $this->getPayload()));
    }

    /**
     * Get links from all children of our module root
     *
     * @param $rootId
     */
    private function updateChildren($rootId)
    {
        $object = IPS_GetObject($rootId);
        if (!$object['HasChildren']) {
            return;
        }

        foreach ($object['ChildrenIDs'] as $childId) {
            $child = IPS_GetObject($childId);
            // subcategory
            if ($child['ObjectType'] == 0) {
                $this->updateChildren($child['ObjectID']);
            } // link to be added to device list
            elseif ($child['ObjectType'] == 6) {
                if (!in_array($child['ObjectID'], $this->children)) {
                    array_push($this->children, $child['ObjectID']);
                }
            }
        }
    }

    /**
     * Get discovery payload containing all linked devices
     *
     * @return array
     */
    public function getPayload()
    {
        $payload = array();

        foreach ($this->children as $child) {
            $obj = IPS_GetObject($child);

            // only handle links
            if ($obj['ObjectType'] != 6) {
                continue;
            }

            $target = IPS_GetObject(IPS_GetLink($child)['TargetID']);

            // variable
            if ($target['ObjectType'] == 2) {
                $variable = IPS_GetVariable($target['ObjectID']);

                // get parent device if any
                $parentDeviceId = IPS_GetParent($target['ObjectID']);
                $parentDevice = null;
                if ($parentDeviceId != 0) {
                    $parentDevice = IPS_GetInstance($parentDeviceId);
                }

                $actions = [];
                // boolean, integer and float can be switched on/off
                if (in_array($variable['VariableType'], array(0, 1, 2))) {
                    array_push($actions, 'turnOn');
                    array_push($actions, 'turnOff');
                }
                // integer and float can be value changed
                if (in_array($variable['VariableType'], array(1, 2))) {
                    array_push($actions, 'setPercentage');
                    array_push($actions, 'incrementPercentage');
                    array_push($actions, 'decrementPercentage');
                }

                // get device vendor
                $deviceVendor = '';
                if ($parentDevice) {
                    $parentDeviceModule = @IPS_GetModule($parentDevice['ModuleInfo']['ModuleID']);
                    if ($parentDeviceModule) {
                        $deviceVendor = $parentDeviceModule['Vendor'];
                    }
                }

                // get device model
                $deviceModel = '';
                if ($parentDevice) {
                    $deviceModel = @$parentDevice['ModuleInfo']['ModuleName'];
                }

                $devicePayload = $this->getPayloadForDevice($target['ObjectID'], $deviceVendor, $deviceModel,
                    IPS_GetKernelVersion(), $obj['ObjectName'], $actions);

                array_push($payload, $devicePayload);
            }

            // script
            elseif ($target['ObjectType'] == 3) {
                $devicePayload = $this->getPayloadForDevice($target['ObjectID'], '', 'Symcon script',
                    IPS_GetKernelVersion(), $obj['ObjectName'], array('turnOn'));
                array_push($payload, $devicePayload);
            }
        }

        return $payload;
    }

    /**
     * Get discovery payload for a single devices with all it's functionalities
     *
     * @param $id integer
     * @param $manufacturer string
     * @param $model string
     * @param $version string
     * @param $name string
     * @param $actions array
     * @return array
     */
    protected function getPayloadForDevice($id, $manufacturer, $model, $version, $name, $actions)
    {
        if (!$manufacturer) {
            $manufacturer = 'Generic vendor';
        }
        if (!$model) {
            $model = 'Generic device';
        }
        $description = $model . ' by ' . $manufacturer;

        $payload = array(
            'applianceId' => 'device_' . (string)$id,
            'manufacturerName' => $manufacturer,
            'modelName' => $model,
            'version' => $version,
            'friendlyName' => $name,
            'friendlyDescription' => $description,
            'isReachable' => true,
            'actions' => $actions,
        );

        // possible actions
        // incrementTargetTemperature, decrementTargetTemperature, setPercentage, incrementPercentage, decrementPercentage, turnOff, turnOn

        return $payload;
    }
}
