<?php

final class DiffusionRepositoryBuildInfoController
  extends DiffusionRepositoryManageController {

  public function handleRequest(AphrontRequest $request) {
    $response = $this->loadDiffusionContextForEdit();
    if ($response) {
      return $response;
    }

    $viewer = $this->getViewer();
    $drequest = $this->getDiffusionRequest();
    $repository = $drequest->getRepository();

    $v_configuration = null;
    $mysql_cdn = null;
    $docker_compose_cdn = null;

    $build = id(new PhabricatorRepositoryBuildInfoQuery())
      ->setViewer($viewer)
      ->withRepositoryPHIDs(array($repository->getPHID()))
      ->executeOne();
    if (!$build) {
      $build = PhabricatorRepositoryBuildInfo::initializeNewBuildInfo($viewer);
      $is_new = true;
    } else {
      $v_configuration = $build->getConfiguration();
      $is_new = false;
    }

    $slug = $repository->getRepositorySlug();
    $view_uri = "/source/$slug/manage/build/";

    $config_instructions = pht(
      'json example
      https://gist.github.com/aditiapratama1231
      /d9b5e8de34c5d484d4e87134fd785c6a');

    if ($request->isFormPost()) {
      $v_configuration = $request->getStr('configuration');

      $template = id(new PhabricatorRepositoryBuildInfoTransaction());
      $xactions = array();

      $xactions[] = id(new PhabricatorRepositoryBuildInfoTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);

      $xactions[] = id(clone $template)
        ->setTransactionType(
      PhabricatorRepositoryBuildInfoConfigurationTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_configuration);

      $xactions[] = id(clone $template)
        ->setTransactionType(
      PhabricatorRepositoryBuildInfoRepositoryPHIDTransaction::TRANSACTIONTYPE)
        ->setNewValue($repository->getPHID());

      $editor = id(new DiffusionRepositoryBuildInfoEditor())
        ->setActor($viewer)
        ->setContinueOnNoEffect(true)
        ->setContentSourceFromRequest($request)
        ->applyTransactions($build, $xactions);

      return id(new AphrontRedirectResponse())->setURI($view_uri);
    }

    $form = null;

    if ($is_new) {
      $title = pht('Set Build Configuration');
      $button = pht('Set Configuration');
    } else {
      $title = pht('Update Build Configuration');
      $button = pht('Update Configuration');
    }

    $form = id(new AphrontFormView())
      ->setViewer($viewer)
      ->setEncType('multipart/form-data')
      ->appendChild(
        id(new AphrontFormTextAreaControl())
          ->setCustomClass('PhabricatorMonospaced')
          ->setLabel(pht('build.json'))
          ->setName('configuration')
          ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_TALL)
          ->setValue($v_configuration)
          ->setCaption($config_instructions));

    $form
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue($button));

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText($title)
      ->setForm($form)
      ->setBackground(PHUIObjectBoxView::WHITE_CONFIG);

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Edit Build Configuration'))
      ->setHeaderIcon('fa-play');

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $form_box,
      ));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Build Configuration'));
    $crumbs->setBorder(true);

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild(
        array(
          $view,
      ));
  }
}
