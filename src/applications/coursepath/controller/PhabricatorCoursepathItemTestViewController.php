<?php

final class PhabricatorCoursepathItemTestViewController
  extends PhabricatorCoursepathItemTestDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $item_id = $request->getURIData('id');
    $test_id = $request->getURIData('test_id');
    $type = $request->getStr('type');

    $item_test = id(new CoursepathItemTestQuery())
      ->setViewer($viewer)
      ->withIDs(array($test_id))
      ->needOptions(true)
      ->executeOne();
    if (!$item_test) {
      return new Aphront404Response();
    }

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($item_id))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $this->setItem($item);
    $this->setItemTest($item_test);

    $crumbs = $this->buildApplicationCrumbs();
    $title = $item_test->getTitle();

    $header = $this->buildHeaderView();
    $curtain = $this->buildCurtain($item_test);
    $question = $this->buildQuestionView($item_test);
    $answer = $this->buildAnswerView($item_test);

    $timeline = $this->buildTransactionTimeline(
      $item_test,
      new CoursepathItemTestTransactionQuery());

    $comment_view = id(new PhabricatorCoursepathItemTestEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($item_test);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setCurtain($curtain)
      ->setMainColumn(array(
          $timeline,
          $comment_view,
        ))
      ->addPropertySection(pht('Question'), $question);

    if ($item_test->getType()) {
      $view->addPropertySection(pht('Correct Answer'), $answer);
    }

    $navigation = $this->buildSideNavView('view');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item_test->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

  private function buildQuestionView(
    CoursepathItemTest $item_test) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $question = $item_test->getQuestion();
    if (strlen($question)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $question));

      $options = $item_test->getOptions();

      $index = 0;
      foreach ($options as $option) {
        $alphabet = range('A', 'Z');
        if (strpos($option->getName(), '```') !== false) {
          $view->addTextContent(
            new PHUIRemarkupView($viewer, pht(
              '**%s**',
              $alphabet[$index++])));

          $view->addTextContent(
            new PHUIRemarkupView($viewer, pht(
              '%s',
              $option->getName())));
        } else {
          $view->addTextContent(
            new PHUIRemarkupView($viewer, pht(
              '**%s** - %s',
              $alphabet[$index++],
              $option->getName())));
        }
      }
    }

    return $view;
  }

  private function buildAnswerView(
    CoursepathItemTest $item_test) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $answer = $item_test->getAnswer();
    if (strlen($answer)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $answer));
    }

    return $view;
  }

  private function buildCurtain(CoursepathItemTest $item_test) {
    $viewer = $this->getViewer();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item_test,
      PhabricatorPolicyCapability::CAN_EDIT);

    $id = $item_test->getID();
    $item_phid = $item_test->getItemPHID();
    $type = $item_test->getType();
    $edit_uri = $this->getApplicationURI(
      "/item/tests/edit/{$id}/?itemPHID=$item_phid&type=$type");
    $archive_uri = $this->getApplicationURI("/item/archive/{$id}/");

    $curtain = $this->newCurtainView($item_test);

    $curtain->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit Skill Test'))
        ->setIcon('fa-pencil')
        ->setDisabled(!$can_edit)
        ->setHref($edit_uri));

    if ($item_test->getStatus() == 'draft') {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Activate Skill Test'))
          ->setIcon('fa-check')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    } else {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Archive Skill Test'))
          ->setIcon('fa-ban')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    }

    return $curtain;
  }

}
