<?php

final class PhabricatorEditEngineTextCommentAction
  extends PhabricatorEditEngineCommentAction {

  public function getPHUIXControlType() {
    return 'text';
  }

  public function getPHUIXControlSpecification() {
    return array(
      'value' => $this->getValue(),
    );
  }

}
