<?php

final class SuiteRspInfoConduitAPIMethod extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.rsp.info';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Retrieve RSP story points and balance info.');
  }

  protected function defineParamTypes() {
    return array(
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();
    $user = $request->getUser();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $profile = SuiteProfileQuery::loadProfileForUser($user,
      $request->newContentSource());

    $account = PhortuneAccountQuery::loadAccountsForUser($user,
    $request->newContentSource());
    $balance = id(new SuiteBalanceQuery())
                ->withOwnerPHIDs(array($user->getPHID()))
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->executeOne();

    $revisions = id(new DifferentialRevisionQuery())
                ->withAuthors(array($user->getPHID()))
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->needReviewers(true)
                ->execute();
    $revision_phids = mpull($revisions, 'getPHID');

    $story_point_gained = 0;
    if (!empty($revision_phids)) {
      $edges = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs($revision_phids)
        ->withEdgeTypes(array(DifferentialRevisionHasTaskEdgeType::EDGECONST))
        ->execute();


      $task_phids = array();
      foreach ($edges as $edge) {
        $task_phids += array_keys(
          $edge[DifferentialRevisionHasTaskEdgeType::EDGECONST]);
      }

      $task_dao = new ManiphestTask();
      $tasks = $task_dao->loadAllWhere('phid IN (%Ls) AND status=%s',
        $task_phids, 'resolved');
      $story_point_gained = array_sum(mpull($tasks, 'getPoints'));
    }

    return array(
      'isRsp'        => $profile->getIsRsp(),
      'storyPoints' => $story_point_gained,
      'amountBalance' => $balance->getWithdrawableAmountAsCurrency()
        ->formatForDisplay(),
      'amountWithdrawn' => PhortuneCurrency::newFromValueAndCurrency(0,
        SuiteBalance::ACCEPTED_CURRENCY)->formatForDisplay(),
    );
  }

}
