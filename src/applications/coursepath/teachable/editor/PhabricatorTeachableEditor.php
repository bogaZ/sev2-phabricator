<?php

final class PhabricatorTeachableEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  public function getEditorObjectsDescription() {
    return pht('Teachable Configuration Proxy');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this configuration.', $author);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s created %s.', $author, $object);
  }

  protected function supportsSearch() {
    return true;
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();
    $types[] = PhabricatorTransactions::TYPE_COMMENT;
    $types[] = PhabricatorTransactions::TYPE_EDGE;
    $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

    return $types;
  }

  protected function shouldSendMail(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return true;
  }

  public function getMailTagsMap() {
    return array(
      CoursepathTransaction::MAILTAG_DETAILS =>
        pht('Someone changes the teachable\'s configuration.'),
      CoursepathTransaction::MAILTAG_COMMENT =>
        pht('Someone comments on a teachable configuration.'),
      CoursepathTransaction::MAILTAG_OTHER =>
        pht('Other teachable configuration activity not listed above occurs.'),
    );
  }

  protected function shouldPublishFeedStory(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return true;
  }

  protected function expandTransactions(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $actor = $this->getActor();
    $actor_phid = $actor->getPHID();

    $results = parent::expandTransactions($object, $xactions);

    // Automatically subscribe the author when they create a course path.
    if ($this->getIsNewObject()) {
      if ($actor_phid) {
        $results[] = id(new CoursepathTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_SUBSCRIBERS)
          ->setNewValue(
            array(
              '+' => array($actor_phid => $actor_phid),
            ));
      }
    }

    return $results;
  }

  protected function buildReplyHandler(PhabricatorLiskDAO $object) {
    return id(new PhabricatorCoursepathReplyHandler())
      ->setMailReceiver($object);
  }

  protected function buildMailTemplate(PhabricatorLiskDAO $object) {
    $name = $object->getName();
    $id = $object->getID();
    $subject = pht('Teachable configuration %d: %s', $id, $name);

    return id(new PhabricatorMetaMTAMail())
      ->setSubject($subject);
  }

  protected function getMailTo(PhabricatorLiskDAO $object) {
    return array(
      $object->getCreatorPHID(),
      $this->requireActor()->getPHID(),
    );
  }

  protected function buildMailBody(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $view_uri = '/coursepath/teachable/';
    $body = parent::buildMailBody($object, $xactions);

    $body->addLinkSection(
      pht('TEACHABLE CONFIGURATION DETAIL'),
      PhabricatorEnv::getProductionURI($view_uri));
    return $body;
  }

  protected function getMailSubjectPrefix() {
    return pht('[Teachable Configuratioin]');
  }

  protected function applyFinalEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $item_phid = $object->getPHID();
    $user_phids = array();
    $clear_everything = false;

    foreach ($xactions as $xaction) {
      switch ($xaction->getTransactionType()) {
        case CoursepathItemEnrollTransaction::TRANSACTIONTYPE:
        case CoursepathItemUnenrollTransaction::TRANSACTIONTYPE:
          foreach ($xaction->getNewValue() as $user_phid) {
            $user_phids[] = $user_phid;
          }
          break;
        default:
          $clear_everything = true;
          break;
      }
    }

    if ($clear_everything) {
      $enrollments = id(new CoursepathItemEnrollmentQuery())
        ->setViewer($this->getActor())
        ->withItemPHIDs(array($item_phid))
        ->execute();
      foreach ($enrollments as $enrollment) {
        $user_phids[] = $enrollment->getRegistrarPHID();
      }
    }

    return $xactions;
  }

}
