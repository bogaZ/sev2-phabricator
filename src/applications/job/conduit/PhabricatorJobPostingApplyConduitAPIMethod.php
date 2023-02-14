<?php

final class PhabricatorJobPostingApplyConduitAPIMethod
  extends PhabricatorJobConduitAPIMethod {

  public function getAPIMethodName() {
    return 'job.applicants.apply';
  }

  public function getMethodDescription() {
    return pht('Invite someone to Job');
  }

  public function getMethodSummary() {
    return pht('Invite someone to Job.');
  }

  protected function defineParamTypes() {
    $status_const = $this->formatStringConstants(
      array(
        'active',
        'deactive',
      ));
    return array(
      'postingPHID'             => 'required string',
      'applicantPHID'           => 'required string',
      'status'                  => 'required '.$status_const,
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $posting_phid = $request->getValue('postingPHID');
    $applicant_phid = $request->getValue('applicantPHID');
    $xactions = array();

    if ($posting_phid) {
      $job = id(new JobPostingQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($posting_phid))
        ->needTechStack(true)
        ->executeOne();
      if ($job) {
        $posting_phid = $job->getPHID();
      }
    }

    $xactions[] = id(new JobPostingTransaction())
      ->setTransactionType(
        JobPostingInviteTransaction::TRANSACTIONTYPE)
      ->setNewValue(array($applicant_phid));

    id(new PhabricatorJobPostingEditor())
      ->setActor($viewer)
      ->setContentSource($request->newContentSource())
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true)
      ->applyTransactions($job, $xactions);

    return array(
      'postingPHID' => $job->getPHID(),
    );
  }

}
