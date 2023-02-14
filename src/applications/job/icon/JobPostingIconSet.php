<?php

final class JobPostingIconSet
  extends PhabricatorIconSet {

  const ICONSETKEY = 'job.posting';

  public function getSelectIconTitleText() {
    return pht('Choose Job Icon');
  }

  protected function newIcons() {
    $map = array(
      'fa-file-code-o' => pht('Software Engineer'),
      'fa-css3' => pht('UI/UX'),
      'fa-terminal' => pht('Devops'),
      'fa-ticket' => pht('Q/A'),
    );

    $icons = array();
    foreach ($map as $key => $label) {
      $icons[] = id(new PhabricatorIconSetIcon())
        ->setKey($key)
        ->setLabel($label);
    }

    return $icons;
  }

}
