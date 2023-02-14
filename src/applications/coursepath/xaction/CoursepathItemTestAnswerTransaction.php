<?php

final class CoursepathItemTestAnswerTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.answer';

  public function generateOldValue($object) {
    return $object->getAnswer();
  }

  public function applyInternalEffects($object, $value) {
    $object->setAnswer($value);
  }

  public function getTitle() {
    return pht(
      '%s updated the answer.',
      $this->renderAuthor());
  }

  public function getTitleForFeed() {
    return pht(
      '%s updated the answer for %s.',
      $this->renderAuthor(),
      $this->renderObject());
  }

  public function hasChangeDetailView() {
    return true;
  }

  public function getMailDiffSectionHeader() {
    return pht('CHANGES TO SKILL TEST ANSWER');
  }

  public function newChangeDetailView() {
    $viewer = $this->getViewer();

    return id(new PhabricatorApplicationTransactionTextDiffDetailView())
      ->setViewer($viewer)
      ->setOldText($this->getOldValue())
      ->setNewText($this->getNewValue());
  }

  public function newRemarkupChanges() {
    $changes = array();

    $changes[] = $this->newRemarkupChange()
      ->setOldValue($this->getOldValue())
      ->setNewValue($this->getNewValue());

    return $changes;
  }


}
