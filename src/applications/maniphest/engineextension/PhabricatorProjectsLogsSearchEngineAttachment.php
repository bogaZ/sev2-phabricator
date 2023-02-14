<?php

final class PhabricatorProjectsLogsSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Logs');
  }

  public function getAttachmentDescription() {
    return pht('Get information about maniphest logs');
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

    $pd_col = new PhabricatorProjectColumn();
    $conn_col = $pd_col->establishConnection('r');
    $table_col = sev2table($pd_col->getTableName());

    $pd_author = new PhabricatorUser();
    $conn_author = $pd_author->establishConnection('r');
    $table_author = sev2table($pd_author->getTableName());

    $pd_project = new PhabricatorProject();
    $conn_project = $pd_project->establishConnection('r');
    $table_project = sev2table($pd_project->getTableName());

    $data_logs = queryfx_all(
      $conn,
      'SELECT t.newValue, t.objectPHID, t.dateCreated, t.authorPHID,
        ((
        SELECT min(mt.dateCreated)
        from %T mt
        WHERE mt.objectPHID = t.objectPHID
        AND mt.dateCreated > t.dateCreated
        AND mt.transactionType = %s
        ) - t.dateCreated) as duration
       from %T t '.
          'WHERE t.objectPHID = %s AND t.transactionType = %s',
          $table,
          'core:columns',
          $table,
          $object->getPHiD(),
          'core:columns');

    $get_current = array();
    $temp_data = array();

    $data_col_all = queryfx_all(
      $conn_col,
      'SELECT phid, name from %T',
      $table_col);

    $data_author_all = queryfx_all(
      $conn_author,
      'SELECT phid, userName, realName from %T',
      $table_author);

    $data_project_all = queryfx_all(
      $conn_project,
      'SELECT phid, name from %T',
      $table_project);

    foreach ($data_logs as $key => $value) {
      $column_to =  current(json_decode($value['newValue']))
      ->columnPHID;
      $column_from = current(current(json_decode($value['newValue']))
      ->fromColumnPHIDs);
      $author_phid = $value['authorPHID'];
      $board_phid = current(json_decode($value['newValue']))->boardPHID;

      $data_author = $this->findData($data_author_all, $author_phid);
      $data_project = $this->findData($data_project_all, $board_phid);
      $data_col_from = $this->findData($data_col_all, $column_from);
      $data_col_to = $this->findData($data_col_all, $column_to);

      if (is_null($value['duration'])) {
        $value['duration'] = strtotime('Now') - (int)$value['dateCreated'];
      }

      $temp_data['author'] = $data_author;
      $temp_data['board'] = $data_project;
      $temp_data['fromColumn'] = $data_col_from;
      $temp_data['toColumn'] = $data_col_to;
      $temp_data['dateCreated'] = (int)$value['dateCreated'];
      $temp_data['duration'] = (int)$value['duration'];
      $get_current[$key] = $temp_data;
    }

    return $get_current;
  }

}
