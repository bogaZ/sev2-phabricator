<?php

final class DiffusionRepositoryBuildInfoFileRemoveController
  extends DiffusionController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $build_id = $request->getURIData('build_id');
    $reponame = $request->getURIData('repositoryShortName');
    $file_phid = $request->getStr('phid');

    $build_file = id(new PhabricatorRepositoryBuildInfoFileQuery())
      ->setViewer($viewer)
      ->withFilePHIDs(array($file_phid))
      ->executeOne();
    if (!$build_file) {
      return new Aphront404Response();
    }

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($build_id))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $view_uri = "/source/$reponame/edit/buildinfo/$build_id/file/";

    if ($request->isFormPost()) {
      $file = id(new PhabricatorFileQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($file_phid))
        ->executeOne();
      if ($file) {
        $file->delete();
      }

      $build_file->delete();

      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Confirm Removal'))
      ->appendParagraph(
        pht(
          'Really remove "%s" ?',
          phutil_tag('strong', array(), $build_file->getFilename())))
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Remove'));

    return $dialog;
  }

}
