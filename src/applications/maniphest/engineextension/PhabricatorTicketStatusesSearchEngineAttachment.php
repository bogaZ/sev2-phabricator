<?php

final class PhabricatorTicketStatusesSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Status');
  }

  public function getAttachmentDescription() {
    return pht('Get information about maniphest statuses');
  }

  public function getData($arr) {
      yield $arr;
  }

  public function findData($arr, $phid) {
    $yeild_arr = current(iterator_to_array($this->getData($arr)));
    foreach ($yeild_arr as $key => $value) {
      if ($value['phid'] == $phid) {
        return ($value);
      }
    }
    return null;
  }

  public function getAttachmentForObject($object, $data, $spec) {

    $ph_maniphest = new ManiphestTransaction();
    $conn_maniphest = $ph_maniphest->establishConnection('r');
    $table_maniphest = sev2table($ph_maniphest->getTableName());

    $ph_author = new PhabricatorUser();
    $conn_author = $ph_author->establishConnection('r');
    $table_author = sev2table($ph_author->getTableName());

    $data_logs = queryfx_all(
      $conn_maniphest,
      'SELECT phid, transactionType, oldValue, newValue, '.
        'objectPHID, dateCreated, authorPHID from %T '.
        'WHERE objectPHID = %s AND transactionType = %s',
      $table_maniphest,
      $object->getPHiD(),
      'status');

    $data_author_all = queryfx_all(
      $conn_author,
      'SELECT phid, userName, realName from %T',
      $table_author);
    $status_logs = array();


    foreach ($data_logs as $key => $value) {

      $author_phid = $value['authorPHID'];
      $phid = $value['phid'];
      $type = $value['transactionType'];
      $old_value = trim($value['oldValue'], '"');
      $new_value = trim($value['newValue'], '"');

      $data_author = $this->findData($data_author_all, $author_phid);

      $status_logs[$key] = array(
        'transactionPHID' => $phid,
        'transactionType' => $type,
        'author' => $data_author,
        'oldValue' => $old_value,
        'newValue' => $new_value,
        'dateCreated' => (int)$value['dateCreated'],
      );
    }

    return $status_logs;
  }

}
