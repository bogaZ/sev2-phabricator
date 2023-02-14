<?php

final class PhabricatorFileUploadDialogController
  extends PhabricatorFileController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $response_type = $request->getStr('responseType');

    $e_file = true;
    $errors = array();
    if ($request->isDialogFormPost()) {
      $file_phids = $request->getStrList('filePHIDs');
      if ($file_phids) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs($file_phids)
          ->setRaisePolicyExceptions(true)
          ->execute();
      } else {
        $files = array();
      }

      if ($files) {
        $results = array();
        foreach ($files as $file) {
          $results[] = $file->getDragAndDropDictionary();
        }

        $content = array(
          'files' => $results,
        );

        if (substr( $response_type, 0, 6 ) === "/lobby") {
          $file = head($files);
          return id(new AphrontAjaxResponse())->setContent(
            array(
              'redirect' => $response_type.$file->getID().'/'
          ));
        }

        return id(new AphrontAjaxResponse())->setContent($content);
      } else {
        $e_file = pht('Required');
        $errors[] = pht('You must choose a file to upload.');
      }
    }

    if ($request->getURIData('single')) {
      $allow_multiple = false;
    } else {
      $allow_multiple = true;
    }

    $form = id(new AphrontFormView())
      ->appendChild(
        id(new PHUIFormFileControl())
          ->setName('filePHIDs')
          ->setLabel(pht('Upload File'))
          ->setAllowMultiple($allow_multiple)
          ->setError($e_file));

    $dialog = $this->newDialog()
      ->setTitle(pht('File'))
      ->setErrors($errors)
      ->appendForm($form);

    if ($response_type) {
      $dialog->addHiddenInput('responseType', $response_type);
    }

    return $dialog
      ->addSubmitButton(pht('Upload'))
      ->addCancelButton('/');
  }

}
