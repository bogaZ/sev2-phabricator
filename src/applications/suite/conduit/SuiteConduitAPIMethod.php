<?php

abstract class SuiteConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorSuiteApplication');
  }

  protected function enforceSuiteOnly(PhabricatorUser $user) {
    if (!$user->getIsSuite()) {
      throw new ConduitException('ERR_USER_NOT_SUITE');
    }
  }

  protected function defineErrorTypes() {
    return array(
      'ERR_FAILED_OPERATION' => pht('Failed operation.'),
      'ERR_USER_NOT_SUITE' => pht('User found, but not registered via Suite.'),
      'ERR_USER_NOT_FOUND' => pht('No such user exists.'),
      'ERR_COURSE_NOT_FOUND' => pht('No such course exists.'),
      'ERR_COURSE_SUITE_EXIST' => pht('Suite already on this coursepath'),
      'ERR_DOCTYPE_NOT_FOUND' => pht('Invalid docType.'),
      'ERR_EMPTY_DATA' => pht('Empty json data.'),
      'ERR_INVALID_OBJECT' => pht('Invalid key owner.'),
      'ERR_INVALID_STATUS' => pht('Invalid status, status must be boolean.'),
      'ERR_REPOSITORY_NOT_FOUND' => pht('Repsository not found'),
      'ERR_BUILD_INFO_NOT_FOUND' =>
         pht('This repository does not have build info'),
    );
  }

}
