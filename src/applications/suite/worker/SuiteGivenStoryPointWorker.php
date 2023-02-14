<?php

final class SuiteGivenStoryPointWorker
  extends PhabricatorWorker {

  protected function doWork() {
    $data = $this->getTaskData();
    $viewer = PhabricatorUser::getOmnipotentUser();

    $story_point = idx($data, 'storyPoint');
    $revision_phid = idx($data, 'revisionPHID');
    $author_phid = idx($data, 'authorPHID');
    $revision_id = idx($data, 'revisionID');
    $project_phid = idx($data, 'projectPHID');
    $content_source = PhabricatorContentSource::newForSource(
      SuiteContentSource::SOURCECONST);

    $spec = id(new PhabricatorProjectRspSpecQuery())
                ->withProjectPHIDs(array($project_phid))
                ->setViewer($viewer)
                ->executeOne();

    if ($spec) {
      $actor = id(new PhabricatorPeopleQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($author_phid))
                ->executeOne();

      $accounts = PhortuneAccountQuery::loadAccountsForUser(
                      $actor,
                      $content_source);
      $account = head($accounts);

      // Get Product SP value & currency
      $sp_currency = PhortuneCurrency::newFromValueAndCurrency(
        (((int)$spec->getStoryPointBilledValue()) * $story_point),
        $spec->getStoryPointCurrency());

      $balance = SuiteBalanceQuery::loadBalanceForUserAccount($actor,
            $account, $content_source);

      $balance->add($actor, $content_source,
      $sp_currency->formatBareValue(), true,
      'Story points for D'.$revision_id);
    }
  }
}
