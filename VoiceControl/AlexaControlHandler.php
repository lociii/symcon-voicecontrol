<?php

include_once(__DIR__ . '/../shared/UUID.php');
include_once(__DIR__ . '/AlexaBaseHandler.php');

class AlexaControlHandler extends AlexaBaseHandler
{
    /**
     * Perform the control operation and return a response
     *
     * @param array $event
     * @return array
     */
    public function getResponse(array $event)
    {
        $action = $event['header']['name'];

        // get target object
        $object = @IPS_GetObject((int)str_replace('device_', '', $event['payload']['appliance']['applianceId']));
        if (!$object) {
            return $this->buildControlResponse('NoSuchTargetError');
        }

        // target is a variable
        if ($object['ObjectType'] == 2) {
            $variable = @IPS_GetVariable($object['ObjectID']);
            if (!$variable) {
                return $this->buildControlResponse('NoSuchTargetError');
            }

            if ($variable['VariableCustomProfile'] != '') {
                $profileAttribute = 'VariableCustomProfile';
            }
            else {
                $profileAttribute = 'VariableProfile';
            }
            $profile = IPS_GetVariableProfile($variable[$profileAttribute]);

            // turn on request
            if ($action == 'TurnOnRequest') {
                // boolean will have it's status updated
                if ($variable['VariableType'] == 0) {
                    return $this->setBooleanValue($object, true, 'TurnOnConfirmation');
                }

                // integer and float will be set to their maximum
                if ($variable['VariableType'] == 1 || $variable['VariableType'] == 2) {
                    return $this->setNumericValue($object, $profile['MaxValue'], 'TurnOnConfirmation');
                }
            }

            // turn off request
            if ($action == 'TurnOffRequest') {
                // boolean will have it's status updated
                if ($variable['VariableType'] == 0) {
                    return $this->setBooleanValue($object, false, 'TurnOffConfirmation');
                }

                // integer and float will be set to their minimum
                if ($variable['VariableType'] == 1 || $variable['VariableType'] == 2) {
                    return $this->setNumericValue($object, $profile['MinValue'], 'TurnOffConfirmation');
                }
            }

            // set a specific percentage
            if ($action == 'SetPercentageRequest') {
                // integer values will be set to their relative value
                if ($variable['VariableType'] == 1 || $variable['VariableType'] == 2) {
                    $value = (int)$event['payload']['percentageState']['value'];
                    $value = $profile['MaxValue'] * ($value / 100);

                    return $this->setNumericValue($object, $value, 'SetPercentageConfirmation');
                }
            }

            // increment by a specific percentage
            if ($action == 'IncrementPercentageRequest') {
                $delta = (int)$event['payload']['deltaPercentage']['value'];

                // get delta relative to object value range
                $delta = $profile['MaxValue'] * ($delta / 100);

                if ($variable['VariableType'] == 1) {
                    // limit delta to maximum available value left
                    $delta = min($delta, $profile['MaxValue'] - $variable['ValueInteger']);
                    $value = $variable['ValueInteger'] + $delta;
                    return $this->setNumericValue($object, $value, 'IncrementPercentageConfirmation');
                }
                if ($variable['VariableType'] == 2) {
                    // limit delta to maximum available value left
                    $delta = min($delta, $profile['MaxValue'] - $variable['ValueFloat']);
                    $value = $variable['ValueFloat'] + $delta;
                    return $this->setNumericValue($object, $value, 'IncrementPercentageConfirmation');
                }
            }

            // decrement by a specific percentage for an integer value
            if ($action == 'DecrementPercentageRequest') {
                $delta = (int)$event['payload']['deltaPercentage']['value'];

                // get delta relative to object value range
                $delta = $profile['MaxValue'] * ($delta / 100);

                if ($variable['VariableType'] == 1) {
                    // limit value to maximum available value left
                    $delta = min($delta, $variable['ValueInteger']);
                    $value = $variable['ValueInteger'] - $delta;
                    return $this->setNumericValue($object, $value, 'DecrementPercentageConfirmation');
                }
                if ($variable['VariableType'] == 2) {
                    // limit value to maximum available value left
                    $delta = min($delta, $variable['ValueFloat']);
                    $value = $variable['ValueFloat'] - $delta;
                    return $this->setNumericValue($object, $value, 'DecrementPercentageConfirmation');
                }
            }
        }

        // target is a script
        if ($object['ObjectType'] == 3) {
            IPS_RunScript($object['ObjectID']);
            return $this->buildControlResponse('TurnOnConfirmation');
        }

        // operation not supported
        return $this->buildControlResponse('UnsupportedOperationError');
    }

    /**
     * Check if the requested object is a special digitalStrom device
     *
     * @param $object array
     * @param $deviceTypes array
     * @return bool
     */
    private function isDigitalstromDevice($object, $deviceTypes)
    {
        // get parent object
        $parentObject = @IPS_GetObject($object['ParentID']);
        // parent is an instance
        if ($parentObject && $parentObject['ObjectType'] == 1) {
            $parentInstance = @IPS_GetInstance($parentObject['ObjectID']);
            // it's a digitalstrom device
            if ($parentInstance && in_array($parentInstance['ModuleInfo']['ModuleName'], $deviceTypes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set a numeric value on an object
     *
     * @param $object array
     * @param $value integer|float
     * @param $result string
     * @return array
     */
    private function setNumericValue($object, $value, $result)
    {
        $this->module->debug('set "'.$object['ObjectName'].'" to '.$value);

        // check if we can directly write to the object
        if (!$object['ObjectIsReadOnly']) {
            IPS_RequestAction($object['ParentID'], $object['ObjectIdent'], $value);
            return $this->buildControlResponse($result);
        }

        // digitalstrom shutter will be set by a specific function
        if ($this->isDigitalstromDevice($object, array('dS Shutter'))) {
            DS_ShutterMove($object['ParentID'], $value);
            return $this->buildControlResponse($result);
        }

        // digitalstrom light will be set by a specific function
        if ($this->isDigitalstromDevice($object, array('dS Light', 'dS Joker'))) {
            DS_DimSet($object['ParentID'], $value);
            return $this->buildControlResponse($result);
        }

        return $this->buildControlResponse('UnsupportedOperationError');
    }

    /**
     * Set a boolean value on an object
     *
     * @param $object array
     * @param $value boolean
     * @param $result string
     * @return array
     */
    private function setBooleanValue($object, $value, $result)
    {
        // check if we can directly write to the object
        if (!$object['ObjectIsReadOnly']) {
            IPS_RequestAction($object['ParentID'], $object['ObjectIdent'], true);
            return $this->buildControlResponse($result);
        }

        // digitalstrom light will be set by a specific function
        if ($this->isDigitalstromDevice($object, array('dS Light', 'dS Joker'))) {
            DS_SwitchMode($object['ParentID'], $value);
            return $this->buildControlResponse($result);
        }

        return $this->buildControlResponse('UnsupportedOperationError');
    }

    /**
     * Helper to build a control response
     *
     * @param $name string
     * @return array
     */
    private function buildControlResponse($name)
    {
        return $this->buildResponse('Alexa.ConnectedHome.Control', $name);
    }
}
