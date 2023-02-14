<?php

final class DiffusionRepositoryNomadJobController
  extends DiffusionRepositoryManageController {

  public function handleRequest(AphrontRequest $request) {
    $response = $this->loadDiffusionContextForEdit();
    if ($response) {
      return $response;
    }

    $viewer = $this->getViewer();
    $drequest = $this->getDiffusionRequest();
    $repository = $drequest->getRepository();

    $panel_uri = id(new DiffusionRepositoryPreviewEnvManagementPanel())
      ->setRepository($repository)
      ->getPanelURI();

    if (!$repository->canPerformAutomation()) {
      return $this->newDialog()
        ->setTitle(pht('Automation Not Configured'))
        ->appendParagraph(
          pht(
            'You can not setup Nomad Job for this repository '.
            'because you have not configured repository automation yet. '.
            'Configure it first, then setup Preview Environment.'))
        ->addCancelButton($panel_uri);
    }

    if ($request->isFormPost()) {
      $can_edit = PhabricatorPolicyFilter::hasCapability(
        $viewer,
        $repository,
        PhabricatorPolicyCapability::CAN_EDIT);

      if ($can_edit) {
        $r_id = $repository->getId();
        $nomad = $repository->getNomad();
        if (empty($nomad)) {
          $nomad = array();
        }

        $nomad['job'] = base64_encode($request->getStr('job'));

        $conn_r = $repository->establishConnection('w');

        $count = queryfx(
          $conn_r,
          'UPDATE %T SET nomad=%s WHERE id=%d',
          $repository->getTableName(),
          json_encode($nomad),
          $r_id);
      }

      return id(new AphrontRedirectResponse())
        ->setURI($panel_uri);
    }

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(pht(''))
      ->appendControl(
        id(new AphrontFormTextAreaControl())
          ->setName('job')
          ->setLabel(pht('Job'))
          ->setValue($repository->getNomadJobDecoded()));

    return $this->newDialog()
      ->setTitle(pht('Nomad Job'))
      ->appendParagraph(
        pht(
          'This configuration will be used to setup a Preview Environment'.
          ' when a revision has been accepted and landed.'))
      ->appendForm($form)
      ->addCancelButton($panel_uri)
      ->addSubmitButton(pht('Save'));
  }

}
