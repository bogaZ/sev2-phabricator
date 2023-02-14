<?php

final class PhabricatorTicketTitlesSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Title Log');
  }

  public function getAttachmentDescription() {
    return pht('Get information about maniphest title');
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

    $data_logs = queryfx_all(
      $conn_maniphest,
      'SELECT id, dateCreated  '.
        'from %T '.
        'WHERE objectPHID = %s AND transactionType = %s',
      $table_maniphest,
      $object->getPHiD(),
      'title');
      $status_logs = array();

    foreach ($data_logs as $key => $value) {

      $status_logs[$key] = array(
        'transactionID' => (int)$value['id'],
        'dateCreated' => (int)$value['dateCreated'],
      );
    }
    return $status_logs;
  }

}
