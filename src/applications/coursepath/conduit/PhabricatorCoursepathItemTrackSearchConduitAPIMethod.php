<?php

final class PhabricatorCoursepathItemTrackSearchConduitAPIMethod
  extends PhabricatorCoursepathItemTrackConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.items.teachable.search';
  }

  public function getMethodDescription() {
    return pht('Course based on coursepath');
  }

  public function getMethodSummary() {
    return pht('Course based on coursepath.');
  }

  protected function defineParamTypes() {
    return array(
      'itemPHID'                => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $item_phid = $request->getValue('itemPHID');
    $tests = array();

    $result = array();

    if ($item_phid) {
      $items = id(new CoursepathItemQuery())
        ->setViewer($viewer)
        ->needEnrollments(true)
        ->needTracks(true)
        ->withPHIDs(array($item_phid))
        ->execute();

      if ($items) {
        $result = $this->constructResponse($items);
      }
    }

    return array(
      'data' => $result,
    );
  }

  private function constructResponse(array $items) {
    $responses = array();
    $response = array();
    $tracks = array();
    $track = array();
    $total_videos = 0;

    foreach ($items as $item) {
      $engine = PhabricatorMarkupEngine::getEngine()
                    ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
      $parsed_description = $engine->markupText($item->getDescription());
      if ($parsed_description instanceof PhutilSafeHTML) {
        $parsed_description = $parsed_description->getHTMLContent();
      }

      $response['id'] = $item->getID();
      $response['phid'] = $item->getPHID();
      $response['name'] = $item->getName();
      $response['description'] = $item->getDescription();
      $response['htmlDescription'] = $parsed_description;
      $response['total_courses'] = count($items);
      $response['total_enrollments'] = count($item->getEnrollments());
      foreach ($item->getTracks() as $course) {
        $lectures = phutil_json_decode($course->getLecture());
        foreach ($lectures as $lecture) {
          $total_videos += count($lecture['lectures']);
        }
        $track['trackPHID'] = $course->getPHID();
        $track['name'] = $course->getName();
        $track['description'] = $course->getDescription();
        $track['total_videos'] = $total_videos;
        $track['image'] = $course->getImage();
        $tracks[] = $track;
      }
      $response['courses'] = $tracks;
      $responses[] = $response;
    }

    return $responses;
  }
}
