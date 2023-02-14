<?php

final class ConpherenceTransactionView extends AphrontView {

  private $conpherenceThread;
  private $conpherenceTransaction;
  private $handles;
  private $markupEngine;
  private $classes = array();
  private $searchResult;
  private $timeOnly;

  public function setConpherenceThread(ConpherenceThread $t) {
    $this->conpherenceThread = $t;
    return $this;
  }

  private function getConpherenceThread() {
    return $this->conpherenceThread;
  }

  public function setConpherenceTransaction(ConpherenceTransaction $tx) {
    $this->conpherenceTransaction = $tx;
    return $this;
  }

  private function getConpherenceTransaction() {
    return $this->conpherenceTransaction;
  }

  public function setHandles(array $handles) {
    assert_instances_of($handles, 'PhabricatorObjectHandle');
    $this->handles = $handles;
    return $this;
  }

  public function getHandles() {
    return $this->handles;
  }

  public function setMarkupEngine(PhabricatorMarkupEngine $markup_engine) {
    $this->markupEngine = $markup_engine;
    return $this;
  }

  private function getMarkupEngine() {
    return $this->markupEngine;
  }

  public function addClass($class) {
    $this->classes[] = $class;
    return $this;
  }

  public function setSearchResult($result) {
    $this->searchResult = $result;
    return $this;
  }

  public function render() {
    $viewer = $this->getUser();
    if (!$viewer) {
      throw new PhutilInvalidStateException('setUser');
    }

    require_celerity_resource('conpherence-transaction-css');

    $transaction = $this->getConpherenceTransaction();
    switch ($transaction->getTransactionType()) {
      case ConpherenceThreadDateMarkerTransaction::TRANSACTIONTYPE:
        return javelin_tag(
          'div',
          array(
            'class' => 'conpherence-transaction-view date-marker',
            'sigil' => 'conpherence-transaction-view',
            'meta' => array(
              'id' => $transaction->getID() + 0.5,
            ),
          ),
          array(
            phutil_tag(
              'span',
              array(
                'class' => 'date',
              ),
              phabricator_format_local_time(
                $transaction->getDateCreated(),
                $viewer,
              'M jS, Y')),
          ));
        break;
    }

    $info = $this->renderTransactionInfo();
    $actions = $this->renderTransactionActions();
    $image = $this->renderTransactionImage();
    $content = $this->renderTransactionContent();
    $footer = $this->renderTransactionFooter();
    $classes = implode(' ', $this->classes);
    $transaction_dom_id = 'anchor-'.$transaction->getID();

    $header = phutil_tag_div(
      'conpherence-transaction-header grouped',
      array($actions, $info));

    return javelin_tag(
      'div',
      array(
        'class' => 'conpherence-transaction-view '.$classes,
        'id'    => $transaction_dom_id,
        'sigil' => 'conpherence-transaction-view',
        'meta' => array(
          'id' => $transaction->getID(),
        ),
      ),
      array(
        $image,
        phutil_tag_div('conpherence-transaction-detail grouped',
          array($header, $content, $footer)),
      ));
  }

  private function renderTransactionInfo() {
    $viewer = $this->getUser();
    $thread = $this->getConpherenceThread();
    $transaction = $this->getConpherenceTransaction();
    $info = array();

    Javelin::initBehavior('phabricator-tooltips');
    $tip = phabricator_datetime($transaction->getDateCreated(), $viewer);
    $label = phabricator_time($transaction->getDateCreated(), $viewer);
    $width = 360;

    Javelin::initBehavior('phabricator-watch-anchor');
    $anchor = id(new PhabricatorAnchorView())
      ->setAnchorName($transaction->getID())
      ->render();

    if ($this->searchResult) {
      $uri = $thread->getMonogram();
      $info[] = hsprintf(
        '%s',
        javelin_tag(
          'a',
          array(
            'href'  => '/'.$uri.'#'.$transaction->getID(),
            'class' => 'transaction-date',
            'sigil' => 'conpherence-search-result-jump',
          ),
          $tip));
    } else {
      $info[] = hsprintf(
        '%s%s',
        $anchor,
        javelin_tag(
          'a',
          array(
            'href'  => '#'.$transaction->getID(),
            'class' => 'transaction-date anchor-link',
            'sigil' => 'has-tooltip',
            'meta' => array(
              'tip' => $tip,
              'size' => $width,
            ),
          ),
          $label));
    }

    return phutil_tag(
      'span',
      array(
        'class' => 'conpherence-transaction-info',
      ),
      $info);
  }

  private function renderTransactionActions() {
    $transaction = $this->getConpherenceTransaction();

    switch ($transaction->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $handles = $this->getHandles();
        $author = $handles[$transaction->getAuthorPHID()];
        $actions = array($author->renderLink());
        break;
      default:
        $actions = null;
        break;
    }

    return $actions;
  }

  private function renderTransactionImage() {
    $image = null;
    $transaction = $this->getConpherenceTransaction();
    switch ($transaction->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $handles = $this->getHandles();
        $author = $handles[$transaction->getAuthorPHID()];
        $image_uri = $author->getImageURI();
        $image = phutil_tag(
          'span',
          array(
            'class' => 'conpherence-transaction-image',
            'style' => 'background-image: url('.$image_uri.');',
          ));
        break;
    }
    return $image;
  }

  private function renderTransactionContent() {
    $transaction = $this->getConpherenceTransaction();
    $content = null;
    $content_class = null;
    $content = null;
    $handles = $this->getHandles();
    switch ($transaction->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $this->addClass('conpherence-comment');
        $author = $handles[$transaction->getAuthorPHID()];
        $comment = $transaction->getComment();
        $content = $this->getMarkupEngine()->getOutput(
          $comment,
          PhabricatorApplicationTransactionComment::MARKUP_FIELD_COMMENT);
        $content_class = 'conpherence-message';
        break;
      default:
        $content = $transaction->getTitle();
        $this->addClass('conpherence-edited');
        break;
    }

    $view = phutil_tag(
      'div',
      array(
        'class' => $content_class,
      ),
      $content);

    return phutil_tag_div('conpherence-transaction-content', $view);
  }

  private function renderTransactionFooter() {
    $transaction = $this->getConpherenceTransaction();
    switch ($transaction->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:

        $icon = id(new PHUIIconView())
          ->setIcon('fa-plus-circle')
          ->addClass('lightbluetext');
        $reaction_btn = javelin_tag(
          'a',
          array(
            'class' => 'reaction',
            'sigil' => 'reaction',
            'meta' => array(
              'trans_id' => $transaction->getPHID(),
              'thread_id' => $this->getConpherenceThread()->getID(),
              'action' => 'reaction',
            ),
          ),
          $icon);

        $reactions = array($reaction_btn);

        $viewer = $this->getViewer();
        $tokens_given = id(new PhabricatorTokenGivenQuery())
          ->withObjectPHIDs(array($transaction->getPHID()))
          ->setViewer($viewer)
          ->execute();


        $handles = array();
        if ($tokens_given) {
          $object_phids = mpull($tokens_given, 'getObjectPHID');
          $viewer_phids = mpull($tokens_given, 'getAuthorPHID');
          $handle_phids = array_merge($object_phids, $viewer_phids);
          $handles = id(new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs($handle_phids)
            ->execute();
        }

        $tokens = array();
        if ($tokens_given) {
          $token_phids = mpull($tokens_given, 'getTokenPHID');
          $tokens = id(new PhabricatorTokenQuery())
            ->setViewer($viewer)
            ->withPHIDs($token_phids)
            ->execute();
          $tokens = mpull($tokens, null, 'getPHID');
        }

        $tokenview = array();
        foreach ($tokens_given as $token_given) {
          $token = idx($tokens, $token_given->getTokenPHID());

          $giver = $handles[$token_given->getAuthorPHID()]->getName();
          $sprite = substr($token->getPHID(), 10);

          if (isset($tokenview[$sprite])) {
            $tokenview[$sprite][] = $giver;
          } else {
            $tokenview[$sprite] = array($giver);
          }
        }

        if (!empty($tokenview)) {
          foreach($tokenview as $sprite => $givers) {
            $reactions[] =  id(new PHUIIconView())
                ->addClass('phui-timeline-token mml')
                ->setTooltip(implode(",", $givers))
                ->setSpriteSheet(PHUIIconView::SPRITE_TOKENS)
                ->setSpriteIcon($sprite);
            $reactions[] = phutil_tag(
              'span',
              array(),
              count($givers));
          }
        }

        return javelin_tag(
          'div',
          array(
            'class' => 'conpherence-transaction-content grouped',
            'id' => 'widgets-transaction',
            'sigil' => 'widgets-transaction',
          ),
          $reactions
        );

        // return phutil_tag_div('conpherence-transaction-content grouped', $reaction);
        break;
      default:
        // We don't want to allow reaction on edit action
        return null;
        break;
    }
  }

}
