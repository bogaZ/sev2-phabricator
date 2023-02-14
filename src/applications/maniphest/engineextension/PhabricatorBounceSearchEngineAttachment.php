<?php

final class PhabricatorBounceSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Bounce');
  }

  public function getAttachmentDescription() {
    return pht('Get information about maniphest bounce back state ticket');
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

    $pd_column = new PhabricatorProjectColumn();
    $conn_column = $pd_column->establishConnection('r');
    $table_column = sev2table($pd_column->getTableName());

    $bounce = array();
    $column_name = ['Rejected', 'Ticket In Review'];
    $phids = array();
    $rejected_phid = array();
    $ticketinreview_phid = array();
    $columns = queryfx_all(
      $conn_column,
      'SELECT phid, name from %T where name IN (%Ls)',
      $table_column,
      $column_name);

    foreach ($columns as $key => $value) {
       if ($value['name'] === 'Rejected') {
            $rejected_phid[] = $value['phid'];
       } else {
        $ticketinreview_phid[] = $value['phid'];
       }
    }

    if (!empty($columns)) {
      $phids = queryfx_one(
        $conn_maniphest,

        'SELECT (
          SELECT count(mtr.id) FROM %T mtr
          WHERE TRIM(BOTH %s FROM
          JSON_EXTRACT(mtr.newValue->"$[0]","$.columnPHID")) IN
          (%Ls)
          AND mtr.transactionType = mt.transactionType
          AND mtr.objectPHID = mt.objectPHID
        ) as rejected_count,
        (
          SELECT count(id) FROM %T mttr
          WHERE TRIM(BOTH %s FROM
          JSON_EXTRACT(mttr.newValue->"$[0]","$.columnPHID")) IN
          (%Ls)
          AND mttr.transactionType = mt.transactionType
          AND mttr.objectPHID = mt.objectPHID
        ) as missalignment
        FROM %T mt
        WHERE mt.transactionType = %s
        AND mt.objectPHID = %s
        LIMIT 1',

        $table_maniphest,
        '"',
        $rejected_phid,

        $table_maniphest,
        '"',
        $ticketinreview_phid,

        $table_maniphest,
        'core:columns',
        $object->getPHiD());
    }

    if (empty($phids)) {
      $bounce['rejected_count'] = 0;
      $bounce['missalignment'] = 0;
    } else {
      $bounce['rejected_count'] = (int)$phids['rejected_count'];
      $bounce['missalignment'] = (int)$phids['missalignment'] < 1
        ? (int)$phids['missalignment']
        : (int)$phids['missalignment'] - 1;
    }

    return $bounce;
  }
}
