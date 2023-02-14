<?php

final class ManiphestAssignmentsSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Assignments');
  }

  public function getAttachmentDescription() {
    return pht('Get information about maniphest assignment transactions.');
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

    $pd = new ManiphestTransaction();
    $conn = $pd->establishConnection('r');
    $table = sev2table($pd->getTableName());

    $pd_author = new PhabricatorUser();
    $conn_author = $pd_author->establishConnection('r');
    $table_author = sev2table($pd_author->getTableName());

    $data_logs = queryfx_all(
      $conn,
      'SELECT phid,transactionType,oldValue,newValue,
        objectPHID, dateCreated, authorPHID from %T '.
          'WHERE objectPHID = %s AND transactionType = %s',
          $table,
          $object->getPHiD(),
          'reassign');

    $data_assign_all = queryfx_all(
      $conn_author,
      'SELECT phid, userName, realName from %T',
      $table_author);

    $logs = array();

    foreach ($data_logs as $key => $value) {
      $assign_from = trim($value['oldValue'], '"');
      $assign_to = trim($value['newValue'], '"');
      $phid = ($value['phid']);
      $transaction_type = $value['transactionType'];

      $data_assign_from = $this->findData($data_assign_all, $assign_from);
      $data_assign_to = $this->findData($data_assign_all, $assign_to);

      $logs[$key] = array(
        'transactionPHID' => $phid,
        'transactionType' => $transaction_type,
        'oldValue' => $data_assign_from,
        'newValue' => $data_assign_to,
        'dateCreated' => (int)$value['dateCreated'],
      );
    }

    return $logs;
  }

}
