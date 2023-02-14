<?php

final class PhabricatorMoodListController
  extends PhabricatorMoodController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    return id(new PhabricatorMoodSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

}
