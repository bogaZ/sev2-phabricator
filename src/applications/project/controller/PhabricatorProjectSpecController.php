<?php

final class PhabricatorProjectSpecController
  extends PhabricatorProjectController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $action = $request->getURIData('action');

    $project = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needRspSpec(true)
      ->executeOne();

    if (!$project) {
      return new Aphront404Response();
    }

    $via = $request->getStr('via');
    if ($via == 'profile') {
      $done_uri = "/project/profile/{$id}/";
    } else if ($via == 'suite') {
      $done_uri = '/suite/projects/';
    } else {
      $done_uri = "/project/manage/{$id}/";
    }


    if ($request->isDialogFormPost()) {
      $editor = id(new PhabricatorProjectRspSpecEditor())
        ->setActor($viewer)
        ->setRequest($request)
        ->setProject($project)
        ->setContinueOnMissingFields(false)
        ->apply();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $course_datasource = id(new CoursepathItemDatasource());
    $people_datasource = id(new PhabricatorPeopleDatasource());

    $current_spec = $this->loadCurrentSpec($project, $viewer);

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setLabel(pht('Billing User'))
          ->setName('billingUserPHID')
          ->setLimit(1)
          ->setValue(array($current_spec->getBillingUserPHID()))
          ->setDatasource($people_datasource))
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setLabel(pht('Coursepath'))
          ->setName('coursepathItemPHID')
          ->setLimit(1)
          ->setValue(array($current_spec->getCoursepathItemPHID()))
          ->setDatasource($course_datasource))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setName('stack')
          ->setLabel(pht('Stack'))
          ->setValue($current_spec->getStack()))
      ->appendChild(
        id(new AphrontFormSelectControl())
          ->setName('storyPointCurrency')
          ->setLabel(pht('SP Currency'))
          ->setOptions(PhabricatorProjectRspSpec::getCurrencyMap())
          ->setValue($current_spec->getStoryPointCurrency()))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setName('storyPointValue')
          ->setLabel(pht('Money Given/SP'))
          ->setValue($current_spec->getStoryPointValue()))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setName('storyPointBilledValue')
          ->setLabel(pht('Money Billed/SP'))
          ->setValue($current_spec->getStoryPointBilledValue()));

    $dialog = $this->newDialog()
      ->setTitle(pht('%s RSP Spec', $project->getName()))
      ->addHiddenInput('via', $via)
      ->appendForm($form)
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Save'));

    return $dialog;
  }

  private function loadCurrentSpec(PhabricatorProject $project, $viewer) {
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
                        $viewer,
                        $project);
    }

    return $current_spec;
  }

}
