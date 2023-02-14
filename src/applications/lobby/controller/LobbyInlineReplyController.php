<?php

final class LobbyInlineReplyController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $user = $request->getUser();
    $conpherence_id = $request->getURIData('const');
    $current_path = $request->getPath();

    $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withIDs(array($conpherence_id))
        ->needProfileImage(true)
        ->needTransactions(true)
        ->setTransactionLimit(20)
        ->execute();

    $replied_thread = head($conpherences);

    if (!$replied_thread) {
      return new Aphront404Response();
    }

    $d_data = $replied_thread->getDisplayData($user);
    $unread_count = $d_data['unread_count'];

    // Mark all as read
    $participant = $replied_thread->getParticipant($user->getPHID());
    $participant->markUpToDate($replied_thread);

    if ($request->isFormPost()) {
      $error = null;
      try {
        $latest_transaction_id = $request->getInt('last_transaction_id');
        $editor = id(new ConpherenceEditor())
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($request)
          ->setActor($user);

        $message = $request->getStr('message');
        if (strlen($message)) {
          $xactions = $editor->generateTransactionsFromText(
            $user,
            $replied_thread,
            $message);
          $xactions = $editor->applyTransactions($replied_thread, $xactions);

          // Delete draft
          $draft = PhabricatorDraft::newFromUserAndKey(
            $user,
            $replied_thread->getPHID());
          $draft->delete();
        }

      } catch (PhabricatorApplicationTransactionNoEffectException $exc) {
        $error = $exc;
      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }

      return id(new AphrontAjaxResponse())->setContent(
        array(
          'error' => $error ? $error->getMessage() : null,
        ));
    }


    require_celerity_resource('phabricator-lobby-inline-reply-dialog-css');

    Javelin::initBehavior(
      'reaction',
      array());

    if ($unread_count > 20) {
      $unread_count = 20;
    }

    $all_transactions = $replied_thread->getTransactions();
    $unread_transactions = array_slice($all_transactions, 0, $unread_count);
    $last_transaction = head(array_reverse($unread_transactions));
    $last_transaction_id = 0;
    if ($last_transaction) {
      $last_transaction_id = $last_transaction->getID();
    }

    $data = ConpherenceTransactionRenderer::renderTransactions(
      $user,
      $replied_thread,
      'older',
      $unread_transactions);

    $unread_view = phutil_tag('div',
      array(
        'class' => 'lobby-dialog-unread-messages',
        'sigil' => 'lobby-dialog-unread-messages',
        'id' => 'lobby-dialog-unread-messages'
      ),
      $data['transactions']
    );

    $reply_form = id(new AphrontFormView())
      ->setUser($user)
      ->appendControl(
        id(new AphrontFormTextAreaControl())
          ->setName('message')
          ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
          ->setPlaceholder('Type a message...'));

    $header_reply_text = phutil_tag(
      'span',
      array(
        'title' => $replied_thread->getTitle(),
      ),
      pht('Unread messages in '));

    $header_reply_anchor = phutil_tag(
      'a',
      array(
        'href' => pht('/Z%s', $conpherence_id),
        'class' => 'phui-oi-link',
        'title' => $replied_thread->getTitle(),
        'style' => 'text-decoration: underline; color:#136CB2',
      ),
      $replied_thread->getTitle());

   $header_reply = phutil_tag_div(
      'header-reply',
      array(
        $header_reply_text,
        $header_reply_anchor,
      ));

    return $this->newDialog()
      ->setTitle($header_reply)
      ->setShortTitle(pht('Unread messages in %s',$replied_thread->getTitle()))
      ->appendChild($unread_view)
      ->appendForm($reply_form)
      ->setClass('lobby-inline-reply-dialog')
      ->addHiddenInput('last_transaction_id', $last_transaction_id)
      ->addSubmitButton('Send')
      ->addCancelButton($current_path, 'Close');
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
