<?php

final class PhabricatorUserEmailProperyField
  extends PhabricatorUserCustomField {

  private $value;

  public function getFieldKey() {
    return 'user:email';
  }

  public function getFieldName() {
    return pht('Email');
  }

  public function getFieldDescription() {
    return pht('Shows email of user.');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function isFieldEnabled() {
    return PhabricatorApplication::isClassInstalled(
      'PhabricatorCalendarApplication');
  }

  public function renderPropertyViewValue(array $handles) {
    $email = $this->getObject()->loadPrimaryEmailAddress();
    if (!strlen($email)) {
      return null;
    }

    $viewer = $this->getViewer();
    $view = new PHUIRemarkupView($viewer, $email);

    return $view;
  }

}
