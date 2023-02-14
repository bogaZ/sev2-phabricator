<?php

final class PhabricatorJobPostingEditController extends
  PhabricatorJobPostingController {
  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $engine = id(new PhabricatorJobPostingEditEngine())
      ->setController($this);

    return $engine->buildResponse();
  }

}
