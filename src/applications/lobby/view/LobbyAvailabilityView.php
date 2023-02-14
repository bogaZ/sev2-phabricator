<?php

final class LobbyAvailabilityView extends AphrontView {

  private $user;

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function render() {
    require_celerity_resource('phabricator-lobby-availability-css');

    Javelin::initBehavior(
      'availability',
      array('availability_uri' => '/lobby/availability/'
        .$this->getUser()->getPHID().'/')
    );

    // $last_6months = 5 * 30 * 24 * 3600;
    // $current_month = date('M', time() - $last_6months);
    $all_months = array(
      'Jan', 'Feb', 'Mar', 'Apr',
      'May', 'Jun', 'Jul', 'Aug',
      'Sep', 'Oct', 'Nov', 'Dec',
    );
    // $start_index = array_search($current_month, $all_months);

    $months = array();
    foreach($all_months as $i => $month) {
      $months[] = phutil_tag('li', array(), $month);
    }
    // $selected_months = array_slice($months, $start_index, 6);
    // if (count($selected_months) < 6) {
    //   $tail = array_slice($months, 0, (6-(count($selected_months))));
    //   $selected_months = array_merge($selected_months, $tail);
    // }
    //
    // $months = phutil_tag('ul', array('class' => 'months'), $selected_months);
    $months = phutil_tag('ul', array('class' => 'months'), $months);

    $all_days = array(
      'Sun', 'Mon', 'Tue', 'Wed',
      'Thu', 'Fri', 'Sat'
    );
    $days = array();
    foreach($all_days as $day) {
      $days[] = phutil_tag('li', array(), $day);
    }
    $days = phutil_tag('ul', array('class' => 'days'), $days);

    $squares = phutil_tag('ul', array('class' => 'squares'));
    $graph = phutil_tag(
      'div',
      array('class' => 'graph'),
      array($months, $days, $squares)
    );

    $view = phutil_tag(
      'div',
      array(
        'class' => 'lobby-availability'
      ),
      $graph
    );

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Contributions'));

    $view = id(new PHUIObjectBoxView())
      ->appendChild($view)
      ->setHeader($header)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->addClass('project-view-properties');

    return $view;
  }
}
