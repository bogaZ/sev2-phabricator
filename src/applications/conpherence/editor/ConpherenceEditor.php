<?php

/**
 * @phutil-external-symbol function notify
 */

$root = dirname(phutil_get_library_root('phabricator'));
require $root.'/externals/firebase-php/notify.php';

final class ConpherenceEditor extends PhabricatorApplicationTransactionEditor {

  const ERROR_EMPTY_PARTICIPANTS = 'error-empty-participants';
  const ERROR_EMPTY_MESSAGE = 'error-empty-message';

  public function getEditorApplicationClass() {
    return 'PhabricatorConpherenceApplication';
  }

  public function getEditorObjectsDescription() {
    return pht('Conpherence Rooms');
  }

  public static function createThread(
    PhabricatorUser $creator,
    array $participant_phids,
    $title,
    $message,
    PhabricatorContentSource $source,
    $topic) {

    $conpherence = ConpherenceThread::initializeNewRoom($creator);
    $errors = array();
    if (empty($participant_phids)) {
      $errors[] = self::ERROR_EMPTY_PARTICIPANTS;
    } else {
      $participant_phids[] = $creator->getPHID();
      $participant_phids = array_unique($participant_phids);
    }

    if (empty($message)) {
      $errors[] = self::ERROR_EMPTY_MESSAGE;
    }

    if (!$errors) {
      $xactions = array();
      $xactions[] = id(new ConpherenceTransaction())
        ->setTransactionType(
          ConpherenceThreadParticipantsTransaction::TRANSACTIONTYPE)
        ->setNewValue(array('+' => $participant_phids));
      if ($title) {
        $xactions[] = id(new ConpherenceTransaction())
          ->setTransactionType(
            ConpherenceThreadTitleTransaction::TRANSACTIONTYPE)
          ->setNewValue($title);
      }
      if (strlen($topic)) {
        $xactions[] = id(new ConpherenceTransaction())
          ->setTransactionType(
            ConpherenceThreadTopicTransaction::TRANSACTIONTYPE)
          ->setNewValue($topic);
      }

      $xactions[] = id(new ConpherenceTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
        ->attachComment(
          id(new ConpherenceTransactionComment())
          ->setContent($message)
          ->setConpherencePHID($conpherence->getPHID()));

      id(new ConpherenceEditor())
        ->setActor($creator)
        ->setContentSource($source)
        ->setContinueOnNoEffect(true)
        ->applyTransactions($conpherence, $xactions);
    }

    return array($errors, $conpherence);
  }

  public function generateTransactionsFromText(
    PhabricatorUser $viewer,
    ConpherenceThread $conpherence,
    $text) {

    $xactions = array();
    $xactions[] = id(new ConpherenceTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
      ->attachComment(
        id(new ConpherenceTransactionComment())
        ->setContent($text)
        ->setConpherencePHID($conpherence->getPHID()));
    return $xactions;
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();

    $types[] = PhabricatorTransactions::TYPE_COMMENT;
    $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
    $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

    return $types;
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this room.', $author);
  }


  protected function applyBuiltinInternalTransaction(
    PhabricatorLiskDAO $object,
    PhabricatorApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $object->setMessageCount((int)$object->getMessageCount() + 1);
        break;
    }

    return parent::applyBuiltinInternalTransaction($object, $xaction);
  }


  protected function applyFinalEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $acting_phid = $this->getActingAsPHID();
    $participants = $object->getParticipants();
    foreach ($participants as $participant) {
      if ($participant->getParticipantPHID() == $acting_phid) {
        $participant->markUpToDate($object);
      }
    }

    if ($participants) {
      PhabricatorUserCache::clearCaches(
        PhabricatorUserMessageCountCacheType::KEY_COUNT,
        array_keys($participants));
    }

    if ($xactions) {
      $data = array(
        'type'        => 'message',
        'threadPHID'  => $object->getPHID(),
        'messageID'   => last($xactions)->getID(),
        'subscribers' => array($object->getPHID()),
      );

      PhabricatorNotificationClient::tryToPostMessage($data);

      $chat = true;
      $body = "New Message";
      $trans = last($xactions);

      try {
        $comment = $trans->getComment();
        if ($comment && strlen($comment->getContent())) {
          $body = $comment->getContent();
        } else {
          $chat = false;
          $transData = array($trans->getAuthorPHID());
          if ($trans->getTransactionType() == PhabricatorTransactions::TYPE_EDGE) {
            $record = PhabricatorEdgeChangeRecord::newFromTransaction($trans);
            $add = $record->getAddedPHIDs();
            $add_value = reset($add);
            $transData[] = $add_value;
          }
          $handles = $trans->getViewer()->loadHandles($transData);
          $handles = iterator_to_array($handles);
          $trans->setHandles($handles);
          $body = $trans->getTitleForTextMail();
        }
      } catch (Exception $e) {
        $body = $e->getMessage();
      }

      $sender = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs(array($trans->getAuthorPHID()))
        ->executeOne();

      $content = $chat ? sprintf('@%s: %s',
        $sender->getUserName(), $body) : $body;

      $workspace = PhabricatorEnv::getEnvConfig('sev2.workspace');

      // if (!$object->getIsPublic()) {
        notify($object->getPHID(),
          $object->getTitle(),
          $content,
          array(
            'title' => $object->getTitle(),
            'body' => $content,
            'conpherencePHID' => $object->getPHID(),
            'authorPHID' => $sender->getPHID(),
            'workspaceID' => $workspace,
          ));
      // }
    }

    return $xactions;
  }

  protected function shouldSendMail(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return true;
  }

  protected function buildReplyHandler(PhabricatorLiskDAO $object) {
    return id(new ConpherenceReplyHandler())
      ->setActor($this->getActor())
      ->setMailReceiver($object);
  }

  protected function buildMailTemplate(PhabricatorLiskDAO $object) {
    $id = $object->getID();
    $title = $object->getTitle();
    if (!$title) {
      $title = pht(
        '%s sent you a message.',
        $this->getActor()->getUserName());
    }

    return id(new PhabricatorMetaMTAMail())
      ->setSubject("Z{$id}: {$title}");
  }

  protected function getMailTo(PhabricatorLiskDAO $object) {
    $to_phids = array();

    $participants = $object->getParticipants();
    if (!$participants) {
      return $to_phids;
    }

    $participant_phids = mpull($participants, 'getParticipantPHID');

    $users = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($participant_phids)
      ->needUserSettings(true)
      ->execute();
    $users = mpull($users, null, 'getPHID');

    $notification_key = PhabricatorConpherenceNotificationsSetting::SETTINGKEY;
    $notification_email =
      PhabricatorConpherenceNotificationsSetting::VALUE_CONPHERENCE_EMAIL;

    foreach ($participants as $phid => $participant) {
      $user = idx($users, $phid);
      if ($user) {
        $default = $user->getUserSetting($notification_key);
      } else {
        $default = $notification_email;
      }

      $settings = $participant->getSettings();
      $notifications = idx($settings, 'notifications', $default);

      if ($notifications == $notification_email) {
        $to_phids[] = $phid;
      }
    }

    return $to_phids;
  }

  protected function getMailCC(PhabricatorLiskDAO $object) {
    return array();
  }

  protected function buildMailBody(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $body = parent::buildMailBody($object, $xactions);
    $body->addLinkSection(
      pht('CONPHERENCE DETAIL'),
      PhabricatorEnv::getProductionURI('/'.$object->getMonogram()));

    return $body;
  }

  protected function addEmailPreferenceSectionToMailBody(
    PhabricatorMetaMTAMailBody $body,
    PhabricatorLiskDAO $object,
    array $xactions) {

    $href = PhabricatorEnv::getProductionURI(
      '/'.$object->getMonogram().'?settings');
    $label = pht('EMAIL PREFERENCES FOR THIS ROOM');
    $body->addLinkSection($label, $href);
  }

  protected function getMailSubjectPrefix() {
    return pht('[Conpherence]');
  }

  protected function supportsSearch() {
    return true;
  }

}
