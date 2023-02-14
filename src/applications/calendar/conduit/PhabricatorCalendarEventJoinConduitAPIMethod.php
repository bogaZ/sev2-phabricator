<?php

final class PhabricatorCalendarEventJoinConduitAPIMethod extends
  PhabricatorCalendarAPIMethod {

  public function getAPIMethodName() {
    return 'calendar.event.join';
  }

  public function getMethodDescription() {
    return pht('Accept or Decline an Event');
  }

  public function getMethodSummary() {
    return pht('Accept or Decline an Event.');
  }

  protected function defineParamTypes() {
    return array(
      'calendarPHID'   => 'required string',
      'action'         => 'required string | `accept`,`decline`',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $calendar_phid = $request->getValue('calendarPHID');
    $action = $request->getValue('action');
    $user = $request->getViewer();

    $event = id(new PhabricatorCalendarEventQuery())
      ->setViewer($user)
      ->withPHIDs(array($calendar_phid))
      ->executeOne();

    if (!$event) {
      return array(
        'message' => 'Event not found',
        'error' => true
      );
    }

    if (!$action) {
      return array(
        'message' => 'action is required',
        'error' => true
      );
    }

    switch ($action) {
      case 'accept':
        $message = pht('%s attended this event', $user->getUsername());
        $is_join = true;
        break;
      case 'decline':
        $message = pht('%s declined this event', $user->getUsername());
        $is_join = false;
        break;
      default:
        $is_join = !$event->getIsUserAttending($user->getPHID());
        break;
    }

    $validation_exception = null;
    if ($is_join) {
      $xaction_type =
        PhabricatorCalendarEventAcceptTransaction::TRANSACTIONTYPE;
    } else {
      $xaction_type =
        PhabricatorCalendarEventDeclineTransaction::TRANSACTIONTYPE;
    }

    $xaction = id(new PhabricatorCalendarEventTransaction())
      ->setTransactionType($xaction_type)
      ->setNewValue(true);

    $request = new AphrontRequest('', '');

    $editor = id(new PhabricatorCalendarEventEditor())
      ->setActor($user)
      ->setContentSourceFromRequest($request)
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true);

    try {
      $editor->applyTransactions($event, array($xaction));

      return array(
        'message' => $message,
        'error' => false
      );
    } catch (PhabricatorApplicationTransactionValidationException $ex) {
      $validation_exception = $ex;
    }

    return array(
      'message' => 'Unexpected error :'.$validation_exception,
      'error' => true
    );

  }
}