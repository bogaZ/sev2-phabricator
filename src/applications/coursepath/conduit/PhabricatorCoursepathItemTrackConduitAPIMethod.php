<?php

abstract class PhabricatorCoursepathItemTrackConduitAPIMethod
  extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorCoursepathApplication');
  }

  protected function buildProjectInfoDictionary(CoursepathItemTrack $track) {
    $results = $this->buildProjectInfoDictionaries(array($track));
    return idx($results, $track->getPHID());
  }

  protected function buildProjectInfoDictionaries(array $tracks) {
    assert_instances_of($tracks, 'CoursepathItemTrack');
    if (!$tracks) {
      return array();
    }

    $result = array();
    foreach ($tracks as $track) {
      $result[$track->getPHID()] = array(
        'id'                    => $track->getID(),
        'phid'                  => $track->getPHID(),
        'itemPHID'              => $track->getItemPHID(),
        'name'                  => $track->getName(),
        'description'           => $track->getDescription(),
        'lectures'              => $track->getLecture(),
      );
    }

    return $result;
  }
}
