<?php

final class LobbyEdgeController
  extends LobbyController {

  private $thread;

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {


    $user = $request->getUser();
    $conpherence_id = $request->getURIData('id');
    $edgetype = $request->getURIData('edgetype');

    $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withIDs(array($conpherence_id))
        ->needProfileImage(true)
        ->execute();

    $current_thread = head($conpherences);

    if (!$current_thread) {
      return new Aphront404Response();
    }

    $this->thread = $current_thread;

    $view = $this->buildEdgeList($edgetype);

    if ($request->isAjax()) {
      return id(new AphrontAjaxResponse())->setContent(
        array(
          'content' => $view->render()
      ));
    }

    require_celerity_resource('phabricator-lobby-conpherence-css');
    require_celerity_resource('phabricator-drag-and-drop-file-upload');

    $back_btn = new PHUIInfoView();
    $back_btn->setSeverity(PHUIInfoView::SEVERITY_NOTICE);
    $back_btn->appendChild(pht('%s of %s',
      ucfirst($edgetype), $current_thread->getTitle()));
    $back_btn->addButton(id(new PHUIButtonView())
        ->setTag('a')
        ->setIcon('fa-comments')
        ->setText('Back')
        ->setHref('/'.$current_thread->getMonogram()));

    return $this->newPage()
              ->appendChild($back_btn)
              ->appendChild(phutil_tag(
                'div',
                  array(
                    'id' => 'lobby-edge',
                    'class' => 'lobby-edge',
                  ), $view));
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }


  private function buildEdgeList($type) {
    $user = $this->getViewer();

    $edge = null;
    $add_uri = null;
    $find_uri = null;

    $list = new PHUIObjectItemListView();
    $items = array();

    switch ($type) {
      case 'stickit':
        $assoc_uri = sprintf('/lobby/conph/assoc/%s/%s/',
          $type, $this->thread->getID());
        $add_uri = '/lobby/stickit/edit/form/default/'
          .'?responseType='.urlencode($assoc_uri);
        $find_uri = '/search/rel/conpherence.has-stickit/'
          .$this->thread->getPHID().'/';

        $items = id(new LobbyEdge())
                  ->setViewer($user)
                  ->setThread($this->thread)
                  ->getStickits();
        if (!empty($items)) {
          $list = id(new LobbyStickitResultListView())
                    ->setUser($user)
                    ->setItems($items);
        }
        break;

      case 'tasks':
        $assoc_uri = sprintf('/lobby/conph/assoc/%s/%s/',
          $type, $this->thread->getID());
        $add_uri = '/maniphest/task/edit/form/default/'
          .'?responseType='.urlencode($assoc_uri);
        $find_uri = '/search/rel/conpherence.has-task/'
          .$this->thread->getPHID().'/';
        $items = id(new LobbyEdge())
                  ->setViewer($user)
                  ->setThread($this->thread)
                  ->getTasks();

        if (!empty($items)) {
          $list = id(new ManiphestTaskResultListView())
                    ->setUser($user)
                    ->setSavedQuery(new PhabricatorSavedQuery())
                    ->setTasks($items);
        }
        break;

      case 'files':
        $assoc_uri = sprintf('/lobby/conph/assoc/%s/%s/',
          $type, $this->thread->getID());
        $add_uri = '/file/uploaddialog/single/'
          .'?responseType='.urlencode($assoc_uri);
        $find_uri = '/search/rel/conpherence.has-file/'
          .$this->thread->getPHID().'/';
        $items = id(new LobbyEdge())
                  ->setViewer($user)
                  ->setThread($this->thread)
                  ->getFiles();

        if (!empty($items)) {
          $list = id(new LobbyFileResultListView())
                    ->setUser($user)
                    ->setFiles($items);
        }
        break;

      case 'calendar':
        $assoc_uri = sprintf('/lobby/conph/assoc/%s/%s/',
          $type, $this->thread->getID());
        $add_uri = '/calendar/event/edit/form/default/'
          .'?responseType='.urlencode($assoc_uri);
        $find_uri = '/search/rel/conpherence.has-calendar/'
          .$this->thread->getPHID().'/';
        $items = id(new LobbyEdge())
                  ->setViewer($user)
                  ->setThread($this->thread)
                  ->getCalendars();

        if (!empty($items)) {
          $list = id(new LobbyEventResultListView())
                    ->setUser($user)
                    ->setEvents($items);
        }
        break;

        case 'goals':
          $assoc_uri = sprintf('/lobby/conph/assoc/%s/%s/',
            $type, $this->thread->getID());
          $add_uri = '/lobby/goals/edit/form/default/'
            .'?responseType='.urlencode($assoc_uri);
          $find_uri = '/search/rel/conpherence.has-goals/'
            .$this->thread->getPHID().'/';

          $items = id(new LobbyEdge())
                    ->setViewer($user)
                    ->setThread($this->thread)
                    ->getGoals();
          if (!empty($items)) {
            $list = id(new LobbyGoalsResultListView())
                      ->setUser($user)
                      ->setItems($items);
          }
          break;
    }

    $header = id(new PHUIHeaderView())
      ->setHeader(ucfirst($type));


    if ($find_uri) {
      $find = id(new PHUIButtonView())
            ->setTag('a')
            ->setHref($find_uri)
            ->setText('Find')
            ->setIcon('fa-search')
            ->setColor(PHUIButtonView::GREY)
            ->setSize(PHUIButtonView::SMALL)
            ->setWorkflow(true);

      $header->addActionLink($find);
    }

    if ($add_uri) {
      $add = id(new PHUIButtonView())
        ->setTag('a')
        ->setText(pht('Add New'))
        ->setIcon('fa-plus')
        ->setSize(PHUIButtonView::SMALL)
        ->setWorkflow(true)
        ->setHref($add_uri);

      $header->addActionLink($add);
    }
    return id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setObjectList($list);
  }
}
