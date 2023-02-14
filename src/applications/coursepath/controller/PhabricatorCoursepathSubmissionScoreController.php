<?php

final class PhabricatorCoursepathSubmissionScoreController
  extends PhabricatorCoursepathItemController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $item_id = $request->getURIData('id');
    $submission_id = $request->getURIData('submit_id');

    if (!$submission_id) {
      return new Aphront404Response();
    }

    $submission = id(new CoursepathItemTestSubmissionQuery())
        ->setViewer($viewer)
        ->withIDs(array($submission_id))
        ->executeOne();

    if (!$submission) {
      return new Aphront404Response();
    }

    $test = id(new CoursepathItemTestQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($submission->getTestPHID()))
        ->executeOne();

    $test_type = $test->getType();
    $done_uri = "/coursepath/item/view/{$item_id}/submissions/query/$test_type";
    $v_score = $submission->getScore();

    if ($request->isDialogFormPost()) {
      $v_score = $request->getStr('score');

      $template = id(new CoursepathItemTestSubmissionTransaction());
      $xactions = array();

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemSubmissionScoreTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_score);

      $editor = id(new PhabricatorCoursepathItemSubmissionEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($request);

      $xactions = $editor->applyTransactions($submission, $xactions);
      $submission->save();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendChild(
        id(new AphrontFormTextControl())
        ->setLabel(pht('Score'))
        ->setName('score')
        ->setValue($v_score));

    $question = pht('Question : %s', $test->getQuestion());
    $answer = pht('Answer : %s', $submission->getAnswer());
    $correct_answer = pht('Correct Answer : %s', $test->getAnswer());
    $dialog = $this->newDialog()
      ->setTitle(pht('Add / Update Score'))
      ->appendChild(new PHUIRemarkupView($viewer, $question))
      ->appendChild(new PHUIRemarkupView($viewer, $answer))
      ->appendChild(new PHUIRemarkupView($viewer, $correct_answer))
      ->appendForm($form)
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Save'));

    return $dialog;
  }

  private function loadTeachableResources() {
    $teachable = id(new TeachableFuture())
        ->setRawTeachableQuery('courses', array());

    $results = array();
    $resources = $teachable->resolve();
    foreach ($resources as $resource) {
      foreach ($resource as $key => $data) {
        if (isset($data['name'])) {
          $results[] = $data;
        }
      }
    }

    return $results;
  }

  private function loadTeachableNameOptions($resources) {
    $results = array();

    foreach ($resources as $resource) {
      if (isset($resource['name'])) {
        $results[] = $resource['name'];
      }
    }

    return $results;
  }


  private function loadTeachableLectures($course_id) {
    $results = array();
    $teachable = id(new TeachableFuture())
        ->setRawTeachableQuery(
        pht('courses/%d/lecture_sections', (int)$course_id),
        array());

    $resources = $teachable->resolve();
    foreach ($resources as $resource) {
      foreach ($resources as $data) {
        return $data;
      }
    }
  }
}
