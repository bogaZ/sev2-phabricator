<?php

final class PonderAnswerSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Ponder Answer');
  }

  public function getAttachmentDescription() {
    return pht('Get corresponding answers for the object.');
  }

  public function willLoadAttachmentData($query, $spec) {
    $query->needAnswers(true);
  }

  public function getAttachmentForObject($object, $data, $spec) {
    $answers = array();

    foreach ($object->getAnswers() as $answer) {
      $answers[] = array(
        'content' => $answer->getContent(),
      );
    }

    return $answers;
  }

}
