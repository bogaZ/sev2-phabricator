<?php

final class PhabricatorMoodApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Mood');
  }

  public function getShortDescription() {
    return pht('Mood User.');
  }

  public function getBaseURI() {
    return '/mood/';
  }

  public function getIcon() {
    return 'fa-smile-o';
  }

  public function getTitleGlyph() {
    return "\xF0\x9F\x98\x83";
  }

  public function getApplicationGroup() {
    return self::GROUP_UTILITIES;
  }

  public function getRoutes() {
    return array(
      '/mood/' => array(
        '(?:query/(?P<queryKey>[^/]+)/)?' => 'PhabricatorMoodListController',
      ),
    );
  }
}
