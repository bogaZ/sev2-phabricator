<?php

final class SuiteEnrollCoursepathV2ConduitAPIMethod extends
  SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.enroll.coursepath.v2';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Set primary coursepath v2.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID'            => 'required user phid',
      'coursepathItemPHIDs' => 'required list<map<string>>',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();
    $results = array();

    $user_phid = $request->getValue('userPHID');
    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($user_phid))
      ->executeOne();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $course_phids = array_values(
      $request->getValue('coursepathItemPHIDs'));

    $courses = id(new CoursepathItemQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($course_phids)
      ->execute();

    if (!$courses) {
      throw new ConduitException('ERR_COURSE_NOT_FOUND');
    }

    // Get coursepath status
    $selected_coursepaths = id(new CoursepathItemQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs($course_phids)
        ->execute();

    $has_coursepath = id(new CoursepathItemEnrollmentQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withRegistrarPHIDs(array($user_phid))
        ->withItemPHIDs($course_phids)
        ->execute();

    if (!empty($has_coursepath)) {
      throw new ConduitException('ERR_COURSE_SUITE_EXIST');
    }

    $result = array();
    // can enroll multiple coursepaths
    foreach ($selected_coursepaths as $coursepath) {
      $enroll = new CoursepathItemEnrollment();
      $enroll->setregistrarPHID($user_phid);
      $enroll->setTutorPHID($viewer->getPHID());
      $enroll->setitemPHID($coursepath->getPHID());
      $enroll->save();

      $result['phid'] = $coursepath->getPHID();
      $result['name'] = $coursepath->getName();
      $results[] = $result;
    }

    $result = array(
      'selectedCoursePath'  => $results,
    );

    return $result;
  }

}
