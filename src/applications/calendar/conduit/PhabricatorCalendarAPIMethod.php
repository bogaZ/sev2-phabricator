<?php

abstract class PhabricatorCalendarAPIMethod
  extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorCalendarApplication');
  }
}
