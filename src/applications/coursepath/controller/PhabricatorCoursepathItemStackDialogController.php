<?php

final class PhabricatorCoursepathItemStackDialogController
  extends PhabricatorJobController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $item_id = $request->getURIData('id');

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($item_id))
      ->executeOne();

    if (!$item) {
      return new Aphront404Response();
    }

    $done_uri = "/coursepath/item/view/{$item_id}/tracks";
    $teachable = $this->loadTeachableResources();

    if ($request->isDialogFormPost()) {
      $track = CoursepathItemTrack::initializeNewTrack(
        $viewer,
        $item->getPHID());

      $course_index = $request->getStr('courseIdx');
      $teachable_id = $teachable[$course_index]['id'];
      $v_description = $request->getStr('description');
      $v_name = @$teachable[$course_index]['name'];
      $v_image = @$teachable[$course_index]['image_url'];
      $v_lecture = $this->loadTeachableLectures($teachable_id);

      $template = id(new CoursepathItemTrackTransaction());
      $xactions = array();


      $xactions[] = id(clone $template)
      ->setTransactionType(
        CoursepathItemTrackNameTransaction::TRANSACTIONTYPE)
      ->setNewValue($v_name);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTrackDescriptionTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_description);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTrackImageTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_image);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTrackLectureTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_lecture);

      $editor = id(new PhabricatorCoursepathItemStackEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($request);

      $xactions = $editor->applyTransactions($track, $xactions);
      $track->save();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendChild(
        id(new AphrontFormSelectControl())
        ->setLabel(pht('Course'))
        ->setName('courseIdx')
        ->setValue('')
        ->setOptions($this->loadTeachableNameOptions($teachable)))
      ->appendChild(
        id(new PhabricatorRemarkupControl())
        ->setUser($viewer)
        ->setLabel(pht('Description'))
        ->setName('description')
        ->setValue(''));

    $dialog = $this->newDialog()
      ->setTitle(pht('%s Teachable Course', $item->getName()))
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
