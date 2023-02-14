<?php

final class CoursepathItemTestSubmissionNameNgrams
  extends PhabricatorSearchNgrams {

  public function getNgramKey() {
    return 'itemtestsubmission';
  }

  public function getColumnName() {
    return 'name';
  }

  public function getApplicationName() {
    return 'coursepath';
  }

}
