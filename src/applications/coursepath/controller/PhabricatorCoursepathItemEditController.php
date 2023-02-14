<?php

final class PhabricatorCoursepathItemEditController extends
  PhabricatorCoursepathItemController {
  public function handleRequest(AphrontRequest $request) {
    return id(new PhabricatorCoursepathItemEditEngine())
      ->setController($this)
      ->buildResponse();
  }

}
