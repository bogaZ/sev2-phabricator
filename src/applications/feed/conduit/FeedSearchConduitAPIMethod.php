<?php

final class FeedSearchConduitAPIMethod extends FeedConduitAPIMethod {

  private function htmlMarkup($text) {
    $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    $parsed_description = $engine->markupText($text);
    if ($parsed_description instanceof PhutilSafeHTML) {
      $parsed_description = $parsed_description->getHTMLContent();
    }
    return $parsed_description;
  }

  public function getAPIMethodName() {
    return 'feed.search';
  }

  public function getMethodDescription() {
    return 'Search Feed';
  }

  private function getDefaultLimit() {
    return 100;
  }

  protected function defineParamTypes() {
    return array(
      'filterPHIDs' => 'optional list <phid>',
      'limit' => 'optional int (default '.$this->getDefaultLimit().')',
      'after' => 'optional int',
      'before' => 'optional int',
    );
  }

  protected function defineReturnType() {
    return 'nonempty array';
  }

  private function getRoomPHID($phid) {
    $edge = id(new PhabricatorEdgeQuery())
      ->withEdgeTypes(
        array(
          LobbyStickitHasRoomEdgeType::EDGECONST,
        ))
      ->withSourcePHIDs(array($phid))
      ->execute();

    return array_keys($edge[$phid][LobbyStickitHasRoomEdgeType::EDGECONST])[0];
  }

  private function getRoom(string $phid) {
    return id(new ConpherenceThreadQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($phid))
      ->executeOne();
  }

  protected function execute(ConduitAPIRequest $request) {
    $results = array();
    $user = $request->getUser();

    $view_type = $request->getValue('view');
    if (!$view_type) {
      $view_type = 'data';
    }

    $query = id(new PhabricatorFeedQuery())
      ->setViewer($user);

    $filter_phids = $request->getValue('filterPHIDs');
    if ($filter_phids) {
      $query->withFilterPHIDs($filter_phids);
    }

    $limit = $request->getValue('limit');
    if (!$limit) {
      $limit = $this->getDefaultLimit();
    }

    $pager = id(new AphrontCursorPagerView())
      ->setPageSize($limit);

    $after = $request->getValue('after');
    if (strlen($after)) {
      $pager->setAfterID($after);
    }

    $before = $request->getValue('before');
    if (strlen($before)) {
      $pager->setBeforeID($before);
    }

    $stories = $query->executeWithCursorPager($pager);
    if ($stories) {
      foreach ($stories as $story) {

        $story_data = $story->getStoryData();

        $author_phid = $story_data->getAuthorPHID();

        $author = id(new PhabricatorPeopleQuery())
          ->setViewer($user)
          ->withPHIDs(array($author_phid))
          ->needProfileImage(true)
          ->executeOne();
        if ($author === null) {
          // When Author not found, just fail that story
          continue;
        }
        $data = null;

        try {
          $view = $story->renderView();
        } catch (Exception $ex) {
          // When stories fail to render, just fail that story.
          phlog($ex);
          continue;
        } catch (Throwable $t) {
          phlog($t);
        }

        $view->setEpoch($story->getEpoch());
        $view->setUser($user);

        $transaction_phid = $story->getPrimaryObjectPHID();
        $object_phid = $story->getPrimaryObjectPHID();

        $text = strip_tags($story->renderText());

        $type = $this->getType($story->getPrimaryObjectPHID());
        $target_data = array(
          'title' => null,
          'type' => null,
          'status' => null,
          'content' => null,
        );

        if ($type == 'LBYT') {
          $conph = $this->getRoom($this->getRoomPHID($transaction_phid));
          $object_phid = $conph->getPHID();
          $stickit = id(new LobbyStickitQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($story->getPrimaryObjectPHID()))
            ->executeOne();
          if ($stickit) {
            $target_data['title'] = $stickit->getTitle();
            $target_data['type'] = $stickit->getNoteType();
            $target_data['content'] = substr($stickit->getContent(), 0, 30);
          }
        }

        if ($type == 'FILE') {
          $file = id(new PhabricatorFileQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($story->getPrimaryObjectPHID()))
            ->executeOne();
          if ($file) {
            $target_data['title'] = $file->getName();
            $target_data['type'] = $file->getMimeType();
            $target_data['content'] = $file->getCDNURI('data');
          }
        }

        if ($type == 'TASK') {
          $task = id(new ManiphestTaskQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($story->getPrimaryObjectPHID()))
            ->executeOne();
          if ($task) {
            $target_data['title'] = $task->getTitle();
            $target_data['type'] = idx(
              (array)ManiphestTaskPriority::getTaskPriorityMap(),
              $task->getPriority());
            $target_data['status'] = $task->getStatus();
          }
        }

        if ($type == 'PROJ') {
          $proj = id(new PhabricatorProjectQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($story->getPrimaryObjectPHID()))
            ->executeOne();
            if ($proj) {
              $target_data['title'] = $proj->getName();
              $target_data['status'] = $proj->getStatus();
            }
        }

        if ($type == 'XACT') {
          $feed_transaction = id(new PhabricatorFeedTransactionQuery())
            ->setViewer($user)
            ->withPHIDs([$story->getPrimaryObjectPHID()])
            ->executeOne();
          if ($feed_transaction) {
            $transaction_phid = $story->getPrimaryObjectPHID();
            $object_phid = $feed_transaction->getObjectPHID();
            $room = $this->getRoom($object_phid);
            if ($room) {
              $target_data['title'] = $room->getTitle();
              $target_data['content'] = $room->getTopic();
              $target_data['status'] = $room->getIsDeleted();
            }
          }
        }

        if ($object_phid == null) {
          phlog('this object phid is null '.$object_phid);
          continue;
        }
        $data = array(
          'phid' => $story_data->getPHID(),
          'feedType' => $type,
          'objectPHID' => $object_phid,
          'targetPHID' => $transaction_phid,
          'author' => array(
            'phid' => $author->getPHID(),
            'userName' => $author->getUserName(),
            'realName' => $author->getRealName(),
            'profileImageURI' => $author->getProfileImageURI(),
          ),
          'message' => array(
            'html' => $this->htmlMarkup($text),
            'text' => $text,
          ),
          'detail' => $target_data,
          'dateCreated' => $story_data->getEpoch(),
          'chronologicalKey' => $story_data->getChronologicalKey(),
        );
        array_push($results, $data);
      }

    }

    return $results;
  }

  private function getType($phid) {
    if (!is_null($phid)) {
      $exploder = explode('-', $phid);
      if (count($exploder) > 1) {
        return $exploder[1];
      }
    }
    return '';
  }
}
