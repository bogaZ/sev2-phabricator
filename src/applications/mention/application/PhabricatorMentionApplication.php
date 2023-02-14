<?php

final class PhabricatorMentionApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Mention');
  }

  public function getShortDescription() {
    return pht('Mention User.');
  }

  public function getBaseURI() {
    return '/mention/';
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
      '/mention/' => array(
        '(?:query/(?P<queryKey>[^/]+)/)?' => 'PhabricatorMentionListController',
      ),
    );
  }
}
