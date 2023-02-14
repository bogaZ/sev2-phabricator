<?php

final class PhabricatorUserPhonePropertyField
  extends PhabricatorUserCustomField {

  private $value;

  public function getFieldKey() {
    return 'user:phone';
  }

  public function getFieldName() {
    return pht('Phone Number');
  }

  public function getFieldDescription() {
    return pht('Shows phone number of user.');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function isFieldEnabled() {
    return PhabricatorApplication::isClassInstalled(
      'PhabricatorCalendarApplication');
  }

  public function renderPropertyViewValue(array $handles) {
    $phone_number = $this->getObject()->loadUserProfile()->getPhoneNumber();
    if (!strlen($phone_number)) {
      return null;
    }

    $viewer = $this->getViewer();
    $view = new PHUIRemarkupView($viewer, $phone_number);

    return $view;
  }

}
