<?php

final class UserWhoAmIConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.whoami';
  }

  public function getMethodDescription() {
    return pht('Retrieve information about the logged-in user.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict<string, wild>';
  }

  public function getRequiredScope() {
    return self::SCOPE_ALWAYS;
  }

  protected function execute(ConduitAPIRequest $request) {
    $projects = id(new PhabricatorProjectQuery())
      ->setViewer($request->getUser())
      ->withMemberPHIDs(array($request->getUser()->getPHID()))
      ->withIcons(['group'])
      ->execute();
    $project_phids = [];

    foreach ($projects as $project) {
        if ($project) {
          $project_phids[] = $project->getPHID();
        }
      }

    $person = id(new PhabricatorPeopleQuery())
      ->setViewer($request->getUser())
      ->needProfileImage(true)
      ->withPHIDs(array($request->getUser()->getPHID()))
      ->executeOne();

    $item_phid = null;
    $enrolls = id(new CoursepathItemEnrollmentQuery())
      ->setViewer($request->getUser())
      ->withRegistrarPHIDs(array($person->getPHID()))
      ->execute();

    foreach ($enrolls as $enroll) {
      if ($enroll) {
        $item_phid = $enroll->getItemPHID();
      }
    }

    return $this->buildUserInformationDictionary(
      $person,
      $with_email = true,
      $with_availability = false,
      $with_phone_number = true,
      $item_phid,
      $with_lobby_state = true,
      $project_phids);
  }

}
