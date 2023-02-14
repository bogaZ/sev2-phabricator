<?php

final class LobbyTokenGiveController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $viewer = $request->getViewer();
    $phid = $request->getURIData('phid');
    $conpherence_id = $request->getInt('thread_id');

    $conpherences = id(new ConpherenceThreadQuery())
      ->setViewer($viewer)
      ->withIDs(array($conpherence_id))
      ->needProfileImage(true)
      ->needTransactions(true)
      ->execute();

    $conpherence = head($conpherences);
    if (!$conpherence) {
      return new Aphront404Response();
    }

    $handle = id(new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();
    if (!$handle->isComplete()) {
      return new Aphront404Response();
    }

    $object = id(new PhabricatorObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    if (!($object instanceof PhabricatorTokenReceiverInterface)) {
      return new Aphront400Response();
    }

    if (!PhabricatorPolicyFilter::canInteract($viewer, $object)) {
      $lock = PhabricatorEditEngineLock::newForObject($viewer, $object);

      $dialog = $this->newDialog()
        ->addCancelButton($handle->getURI());

      return $lock->willBlockUserInteractionWithDialog($dialog);
    }

    $current = id(new PhabricatorTokenGivenQuery())
      ->setViewer($viewer)
      ->withAuthorPHIDs(array($viewer->getPHID()))
      ->withObjectPHIDs(array($handle->getPHID()))
      ->execute();

    if ($current) {
      $is_give = false;
      $title = pht('Rescind Token');
    } else {
      $is_give = true;
      $title = pht('Give Token');
    }

    $done_uri = '/Z'.$conpherence_id;
    if ($request->isFormOrHisecPost()) {
      $content_source = PhabricatorContentSource::newFromRequest($request);

      $editor = id(new PhabricatorTokenGivenEditor())
        ->setActor($viewer)
        ->setRequest($request)
        ->setCancelURI($handle->getURI())
        ->setContentSource($content_source);
      if ($is_give) {
        $token_phid = $request->getStr('tokenPHID');
        $editor->addToken($handle->getPHID(), $token_phid);
      } else {
        $editor->deleteToken($handle->getPHID());
      }

      $data = ConpherenceTransactionRenderer::renderTransactions(
        $viewer,
        $conpherence,
        'older',
        array($object));

      return id(new AphrontAjaxResponse())->setContent(
        array(
          'phid' => $phid,
          'transaction_id' => $object->getId(),
          'transaction_element_id' => 'anchor-'.$object->getId(),
          'transactions' => $data['transactions']
        ));
    }

    if ($is_give) {
      $dialog = $this->buildGiveTokenDialog($conpherence_id);
    } else {
      $dialog = $this->buildRescindTokenDialog(head($current), $conpherence_id);
    }

    $dialog->setUser($viewer);
    $dialog->addCancelButton($done_uri);

    return id(new AphrontDialogResponse())->setDialog($dialog);
  }

  private function buildGiveTokenDialog($thread_id) {
    $viewer = $this->getViewer();

    $tokens = id(new PhabricatorTokenQuery())
      ->setViewer($viewer)
      ->execute();

    $buttons = array();
    $ii = 0;
    foreach ($tokens as $token) {
      $aural = javelin_tag(
        'span',
        array(
          'aural' => true,
        ),
        pht('Award "%s" Token', $token->getName()));

      $buttons[] = javelin_tag(
        'button',
        array(
          'class' => 'token-button',
          'name' => 'tokenPHID',
          'value' => $token->getPHID(),
          'type' => 'submit',
          'sigil' => 'has-tooltip',
          'meta' => array(
            'tip' => $token->getName(),
          ),
        ),
        array(
          $aural,
          $token->renderIcon(),
        ));
      if ((++$ii % 6) == 0) {
        $buttons[] = phutil_tag('br');
      }
    }

    $buttons = phutil_tag(
      'div',
      array(
        'class' => 'token-grid',
      ),
      $buttons);

    $dialog = new AphrontDialogView();
    $dialog->setTitle(pht('Give Token'))
            ->addHiddenInput('thread_id', $thread_id);
    $dialog->appendChild($buttons);

    return $dialog;
  }

  private function buildRescindTokenDialog(PhabricatorTokenGiven $token_given,
    $thread_id) {
    $dialog = new AphrontDialogView();
    $dialog->setTitle(pht('Rescind Token'))
            ->addHiddenInput('thread_id', $thread_id);

    $dialog->appendChild(
      pht('Really rescind this lovely token?'));

    $dialog->addSubmitButton(pht('Rescind Token'));

    return $dialog;
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
