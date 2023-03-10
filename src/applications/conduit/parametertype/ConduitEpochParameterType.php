<?php

final class ConduitEpochParameterType
  extends ConduitParameterType {

  private $allowNull;

  public function setAllowNull($allow_null) {
    $this->allowNull = $allow_null;
    return $this;
  }

  public function getAllowNull() {
    return $this->allowNull;
  }

  protected function getParameterValue(array $request, $key, $strict) {
    $value = parent::getParameterValue($request, $key, $strict);

    if ($this->allowNull && ($value === null)) {
      return $value;
    }

    $value = $this->parseIntValue($request, $key, $value, $strict);

    if ($value <= 0) {
      $this->raiseValidationException(
        $request,
        $key,
        pht('Epoch timestamp must be larger than 0, got %d.', $value));
    }

    // we have an issue with epoc paramater that used on conduit
    // https://discourse.phabricator-community.org/t/setting-start-enddate-in-calender-api-calls/845/5
    if (isset($request['type'])) {
      if (in_array($request['type'], ['start', 'end', 'epoch'])) {
        return AphrontFormDateControlValue::newFromEpoch($this->getViewer(), $value);
      }
    }

    return $value;
  }

  protected function getParameterTypeName() {
    return 'epoch';
  }

  protected function getParameterFormatDescriptions() {
    return array(
      pht('Epoch timestamp, as an integer.'),
    );
  }

  protected function getParameterExamples() {
    return array(
      '1450019509',
    );
  }

}
