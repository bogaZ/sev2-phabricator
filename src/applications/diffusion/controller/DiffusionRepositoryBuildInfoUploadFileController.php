<?php

final
  class DiffusionRepositoryBuildInfoUploadFileController
    extends DiffusionController {

  public function isGlobalDragAndDropUploadEnabled() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getUser();

    $build_id = $request->getURIData('build_id');
    $reponame = $request->getURIData('repositoryShortName');

    if (!$build_id) {
      return new Aphront404Response();
    }

    $build = id(new PhabricatorRepositoryBuildInfoQuery())
      ->setViewer($viewer)
      ->withIDs(array($build_id))
      ->executeOne();

    if (!$build) {
      return new Aphront404Response();
    }

    $build_file = new PhabricatorRepositoryBuildInfoFile();

    $e_file = true;
    $errors = array();
    if ($request->isFormPost()) {
      $view_policy = PhabricatorPolicies::POLICY_USER;
      if (!$request->getFileExists('file')) {
        $e_file = pht('Required');
        $errors[] = pht('You must select a file to upload.');
      } else {
        $filename = $request->getStr('name');
        $file = PhabricatorFile::newFromPHPUpload(
          idx($_FILES, 'file'),
          array(
            'name'        => $filename,
            'authorPHID'  => $viewer->getPHID(),
            'viewPolicy'  => $view_policy,
            'isExplicitUpload' => true,
          ));
      }

      $build_file->setBuildPHID($build->getPHID());
      $build_file->setFilename($filename);
      $build_file->setViewPolicy($view_policy);
      $build_file->setEditPolicy($view_policy);
      $build_file->setFilePHID($file->getPHID());
      $build_file->save();

      if (!$errors) {
        return id(new AphrontRedirectResponse())->setURI($file->getInfoURI());
      }
    }

    $support_id = celerity_generate_unique_node_id();
    $instructions = id(new AphrontFormMarkupControl())
      ->setControlID($support_id)
      ->setControlStyle('display: none')
      ->setValue(hsprintf(
        '<br /><br /><strong>%s</strong> %s<br /><br />',
        pht('Drag and Drop:'),
        pht(
          'You can also upload files by dragging and dropping them from your '.
          'desktop onto this page or the Phabricator home page.')));

    $cancel_uri = "/source/$reponame/edit/buildinfo/$build_id/file/";

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->setEncType('multipart/form-data')
      ->appendChild(
        id(new AphrontFormFileControl())
          ->setLabel(pht('File'))
          ->setName('file')
          ->setError($e_file))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Name'))
          ->setName('name')
          ->setValue($request->getStr('name')))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue(pht('Upload'))
          ->addCancelButton($cancel_uri))
      ->appendChild($instructions);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Upload'), $request->getRequestURI());
    $crumbs->setBorder(true);

    $title = pht('Upload File');

    $global_upload = id(new PhabricatorGlobalUploadTargetView())
      ->setUser($viewer)
      ->setShowIfSupportedID($support_id);

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText($title)
      ->setFormErrors($errors)
      ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
      ->setForm($form);

    $view = id(new PHUITwoColumnView())
      ->setFooter(array(
        $form_box,
        $global_upload,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);
  }

}
