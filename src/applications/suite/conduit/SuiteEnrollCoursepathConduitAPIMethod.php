<?php

final class SuiteEnrollCoursepathConduitAPIMethod extends
  SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.enroll.coursepath';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Set primary coursepath.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID' => 'required user phid',
      'coursepathItemPHID' => 'required coursepath item phid',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();

    $user_phid = $request->getValue('userPHID');
    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($user_phid))
      ->executeOne();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $course_phid = $request->getValue('coursepathItemPHID');
    $course = id(new CoursepathItemQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($course_phid))
      ->executeOne();

    if (!$course) {
      throw new ConduitException('ERR_COURSE_NOT_FOUND');
    }

    // Get coursepath status
    $selected_coursepath = id(new CoursepathItemQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs(array($course_phid))
        ->executeOne();
    $has_coursepath = id(new CoursepathItemEnrollmentQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withRegistrarPHIDs(array($user_phid))
                      ->withItemPHIDs(array($course_phid))
                      ->executeOne();

    if (!$has_coursepath) {
      // Enroll
      $enroll_phids = array($user_phid);
      $xactions = array();

      $xactions[] = id(new CoursepathTransaction())
        ->setTransactionType(
          CoursepathItemEnrollTransaction::TRANSACTIONTYPE)
        ->setNewValue($enroll_phids);

      $editor = id(new PhabricatorCoursepathItemEditor())
        ->setActor($viewer)
        ->setContentSource($request->newContentSource())
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($selected_coursepath, $xactions);
    }

    $result = array(
      'selectedCoursePath'     => array(
                        'phid' => $selected_coursepath->getPHID(),
                        'name' => $selected_coursepath->getName(),
                      ),
    );

    return $result;
  }

}
