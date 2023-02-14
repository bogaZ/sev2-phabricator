<?php

final class UserCheckInListConduitAPIMethod
  extends PhabricatorSearchUserCheckInListAPIMethod {

  public function getAPIMethodName() {
    return 'user.checkin.list';
  }

  public function newSearchEngine() {
    return new PhabricatorPeopleUserCheckInSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about users checkin.');
  }

}
