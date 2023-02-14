<?php

final class LobbyCurrentTaskController
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
    $phid = $request->getURIData('phid');
    $adhoc_task = $request->getStr('adhoc', LobbyState::DEFAULT_TASK);

    $content_source = PhabricatorContentSource::newFromRequest($request);

    $lobby = id(new LobbyStateQuery())
              ->setViewer($user)
              ->withPHIDs(array($phid))
              ->executeOne();

    if (!$lobby) {
      return new Aphront404Response();
    }

    if ($request->isFormPost()) {
      try {
        $lobby->resetTaskEdge();
        $lobby = id(new Lobby())
                  ->setViewer($user)
                  ->workOnTask(
          $user, $content_source,
          $adhoc_task);

      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }

      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $title = 'What are you working on?';
    $content = pht('Hi %s. You can work on adhoc task'.
      ' such as meeting, discussion or assisting your'.
      ' team if you are manager.',
      $user->getRealname());

    $select_task_btn = id(new PHUIButtonView())
          ->setTag('a')
          ->setHref('/search/rel/lobby.has-task/'.$phid.'/')
          ->setText('Select Ticket')
          ->setColor(PHUIButtonView::GREEN)
          ->setWorkflow(true);

    $adhoc_or_select_form = id(new AphrontFormView())
      ->setUser($user)
      ->appendControl(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Adhoc Task'))
          ->setName('adhoc'))
      ->appendRemarkupInstructions(
        pht(
          'or you can select from the task list.' ))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->addButton($select_task_btn));

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($title)
      ->appendParagraph($content)
      ->appendForm($adhoc_or_select_form)
      ->addCancelButton('/')
      ->addSubmitButton('Start Adhoc');
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
