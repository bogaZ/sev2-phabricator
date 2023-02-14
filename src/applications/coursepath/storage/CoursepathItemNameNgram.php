<?php

final class CoursepathItemNameNgrams
  extends PhabricatorSearchNgrams {

  public function getNgramKey() {
    return 'itemname';
  }

  public function getColumnName() {
    return 'name';
  }

  public function getApplicationName() {
    return 'coursepath';
  }

}
