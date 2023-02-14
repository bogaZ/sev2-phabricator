<?php

final class PhabricatorLobbyConpherencePHIDResolver
  extends PhabricatorPHIDResolver {

  protected function getResolutionMap(array $names) {
    // Pick up the normalization and case rules from the PHID type query.

    foreach ($names as $key => $name) {
      $names[$key] = 'Z'.$name;
    }

    $query = id(new PhabricatorObjectQuery())
      ->setViewer($this->getViewer());

    $channels = id(new PhabricatorConpherenceThreadPHIDType())
      ->loadNamedObjects($query, $names);

    $results = array();
    foreach ($channels as $monogram => $channel) {
      $results[substr($monogram, 1)] = $channel->getPHID();
    }

    return $results;
  }

}
