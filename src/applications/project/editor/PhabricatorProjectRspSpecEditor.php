<?php

final class PhabricatorProjectRspSpecEditor
  extends PhabricatorEditor {

  private $request;
  private $project;
  private $continueOnMissingFields = false;

  public function setRequest(AphrontRequest $request) {
    $this->request = $request;
    return $this;
  }

  public function getRequest() {
    return $this->request;
  }

  public function setProject(PhabricatorProject $project) {
    $this->project = $project;
    return $this;
  }

  public function getProject() {
    return $this->project;
  }

  public function setContinueOnMissingFields($continue) {
    $this->continueOnMissingFields = $continue;
    return $this;
  }

  public function apply() {
    $request = $this->requireRequest();
    $actor = $this->requireActor();
    $project = $this->requireProject();
    $coursepath_item = $this->requireCoursepathItem();
    $billing_user = $this->requireBillingUser();

    $spec = $this->loadCurrentSpec($project);
    $spec->setAuthorPHID($actor->getPHID());
    $spec->setCoursepathItemPHID($coursepath_item->getPHID());
    $spec->setBillingUserPHID($billing_user->getPHID());

    $spec->openTransaction();

      if ($request->getStr('storyPointCurrency')) {
        $spec->setStoryPointCurrency($request->getStr('storyPointCurrency'));
      }

      if ($request->getStr('storyPointValue')) {
        $spec->setStoryPointValue(
          $request->getStr('storyPointValue'));
      }

      if ($request->getStr('storyPointBilledValue')) {
        $spec->setStoryPointBilledValue(
          $request->getStr('storyPointBilledValue'));
      }

      if ($request->getStr('stack')) {
        $spec->setStack($request->getStr('stack'));
      }

      $spec->save();

    $spec->saveTransaction();

    // $subscribed_phids = $object->getUsersToNotifyOfTokenGiven();
    // if ($subscribed_phids) {
    //   $related_phids = $subscribed_phids;
    //   $related_phids[] = $actor->getPHID();
    //
    //   $story_type = 'PhabricatorTokenGivenFeedStory';
    //   $story_data = array(
    //     'authorPHID' => $actor->getPHID(),
    //     'tokenPHID' => $token->getPHID(),
    //     'objectPHID' => $object->getPHID(),
    //   );
    //
    //   id(new PhabricatorFeedStoryPublisher())
    //     ->setStoryType($story_type)
    //     ->setStoryData($story_data)
    //     ->setStoryTime(time())
    //     ->setStoryAuthorPHID($actor->getPHID())
    //     ->setRelatedPHIDs($related_phids)
    //     ->setPrimaryObjectPHID($object->getPHID())
    //     ->setSubscribedPHIDs($subscribed_phids)
    //     ->publish();
    // }
  }

  private function requireRequest() {
    if (!$this->request) {
      throw new Exception(pht('No request attached!'));
    }

    return $this->request;
  }

  private function requireProject() {
    if (!$this->project) {
      throw new Exception(pht('No project selected!'));
    }

    if (!$this->project->getIsForRsp()) {
      throw new Exception(pht('Project is not RSP enabled!'));
    }

    return $this->project;
  }

  private function requireCoursepathItem() {
    if (!$this->request->getArr('coursepathItemPHID')) {
      throw new Exception(pht('No course path item selected!'));
    }

    return id(new CoursepathItemQuery())
            ->withPHIDs($this->request->getArr('coursepathItemPHID'))
            ->setViewer($this->requireActor())
            ->executeOne();
  }

  private function requireBillingUser() {
    if (!$this->request->getArr('billingUserPHID')) {
      throw new Exception(pht('No billing user assigned!'));
    }

    return id(new PhabricatorPeopleQuery())
            ->withPHIDs($this->request->getArr('billingUserPHID'))
            ->setViewer($this->requireActor())
            ->executeOne();
  }

  private function loadCurrentSpec(PhabricatorProject $project) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withClasses(array('PhabricatorProjectApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      ProjectDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      ProjectDefaultEditCapability::CAPABILITY);

    $current_spec = id(new PhabricatorProjectRspSpec())->loadOneWhere(
      'projectPHID = %s',
      $project->getPHID());

    if ($current_spec) {
      $current_spec->setViewPolicy($view_policy);
      $current_spec->setEditPolicy($edit_policy);
    } else {
      $current_spec = PhabricatorProjectRspSpec::initializeNewRspSpec(
                      $this->requireActor(), $project);
    }

    return $current_spec;
  }
}
