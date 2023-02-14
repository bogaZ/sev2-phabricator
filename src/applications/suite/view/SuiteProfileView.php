<?php

final class SuiteProfileView extends AphrontView {

  private $suiteUser;

  public function setSuiteUser(PhabricatorUser $user) {
    $this->suiteUser = $user;
    return $this;
  }

  public function getSuiteUser() {
    return $this->suiteUser;
  }

  public function renderList() {
    $viewer = $this->getUser();
    $suite_user = $this->getSuiteUser();

    $no_data = pht('No profile found.');

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer)
      ->setNoDataString($no_data);

    // Get suite version
    $version = id(new PHUIObjectItemView())
      ->setHeader(pht('Version'))
      ->addAttribute(array($this->renderVersion($suite_user)));

    $list->addItem($version);

    // Get suite status
    $status = id(new PHUIObjectItemView())
      ->setHeader(pht('Status'))
      ->addAttribute($this->renderStatus($suite_user));

    $list->addItem($status);

    return $list;
  }

  public function render() {
    return $this->renderList();
  }

  private function renderVersion(PhabricatorUser $suite_user) {
    return 'v'.(string)($suite_user->getSuiteVersion()
            ? $suite_user->getSuiteVersion() : 'Unknown');
  }

  private function renderStatus(PhabricatorUser $suite_user) {
    $status = array();

    if ($suite_user->getIsForDev()) {
      $status_icon = id(new PHUIIconView())
                      ->setIcon('fa-exclamation red');
      $status[] = $status_icon;
      $status[] = ' ';
      $status[] = 'Dev';
      $status[] = ' ';
    }

    if ($suite_user->getIsSuiteDisabled()) {
      $status_icon = id(new PHUIIconView())
                      ->setIcon('fa-ban red');
      $status[] = $status_icon;
      $status[] = ' ';
      $status[] = 'Disabled';
    } else {
      $status_icon = id(new PHUIIconView())
                        ->setIcon('fa-check-circle green');
      $status[] = $status_icon;
      $status[] = ' ';
      $status[] = 'Active';
    }

    $status[] = ' ';

    if ($suite_user->getIsSuiteSubscribed()) {
      $subscription_icon = id(new PHUIIconView())
                            ->setIcon('fa-check-circle green');
      $status[] = $subscription_icon;
      $status[] = ' ';
      $status[] = 'Subscribed';
    } else {
      $subscription_icon = id(new PHUIIconView())
                            ->setIcon('fa-exclamation-circle yellow');
      $status[] = $subscription_icon;
      $status[] = ' ';
      $status[] = 'Unpaid';
    }

    $status[] = ' ';

    if ($suite_user->getIsSuiteOnline()) {
      $online_icon = id(new PHUIIconView())
                      ->setIcon('fa-check-circle green');
      $status[] = $online_icon;
      $status[] = ' ';
      $status[] = 'Online';
    } else {
      $online_icon = id(new PHUIIconView())
                      ->setIcon('fa-exclamation-circle yellow');
      $status[] = $online_icon;
      $status[] = ' ';
      $status[] = 'Away';
    }

    return $status;
  }

}
