<?php

final class ConduitLobbyConpherenceListParameterType
  extends ConduitListParameterType {

  protected function getParameterValue(array $request, $key, $strict) {
    $list = parent::getParameterValue($request, $key, $strict);
    $list = $this->parseStringList($request, $key, $list, $strict);
    return id(new PhabricatorLobbyConpherencePHIDResolver())
      ->setViewer($this->getViewer())
      ->resolvePHIDs($list);
  }

  protected function getParameterTypeName() {
    return 'list<channel>';
  }

  protected function getParameterFormatDescriptions() {
    return array(
      pht('List of channel PHIDs.'),
      pht('List of channel Monogram.'),
      pht('List with a mixture of PHIDs and monograms.'),
    );
  }

  protected function getParameterExamples() {
    return array(
      '["PHID-CONP-2"]',
      '["Z3"]',
      '["PHID-CONP-2", "Z3"]',
    );
  }

}
