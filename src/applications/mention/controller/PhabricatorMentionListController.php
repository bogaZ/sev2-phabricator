<?php

final class PhabricatorMentionListController
  extends PhabricatorMentionController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    return id(new PhabricatorMentionSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

}
