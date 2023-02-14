<?php

final class PhabricatorCoursepathItemTrackDetailConduitAPIMethod
  extends PhabricatorCoursepathItemTrackConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.teachable.search';
  }

  public function getMethodDescription() {
    return pht('Teachable Course search');
  }

  public function getMethodSummary() {
    return pht('Teachable Course search.');
  }

  protected function defineParamTypes() {
    return array(
      'trackPHID'   => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $track_phid = $request->getValue('trackPHID');
    $tests = array();

    $result = array();

    if ($track_phid) {
      $tracks = id(new CoursepathItemTrackQuery())
        ->setViewer($viewer)
        ->needItems(true)
        ->withPHIDs(array($track_phid))
        ->execute();

      if ($tracks) {
        $result = $this->constructResponse($tracks, $viewer);
      }
    }

    return array(
      'data' => $result,
    );
  }

  private function constructResponse(array $tracks, $viewer) {
    $responses = array();
    $response = array();

    $total_videos = 0;
    foreach ($tracks as $track) {
      $item = id(new CoursepathItemQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($track->getItemPHID()))
        ->executeOne();

      $response['id'] = $track->getID();
      $response['track'] = $track->getPHID();
      $response['name'] = $track->getName();
      $response['descritpion'] = $track->getDescription();
      $response['coursepath'] = $item->getName();

      $lectures = phutil_json_decode($track->getLecture());
      $response['image'] = $track->getImage();
      foreach ($lectures as $lecture) {
        $total_videos += count($lecture['lectures']);
      }
      $response['total_videos'] = $total_videos;
      $response['materials'] = phutil_json_decode($track->getLecture());
      $responses[] = $response;
    }

    return $responses;
  }
}
