<?php

final class PhabricatorAuthExchangeIdTokenController
  extends PhabricatorController {

  const ID_TOKEN_PATH = '/auth/exchange-id-token/';

  public function shouldRequireLogin() {
    return false;
  }

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $sub = $request->getStr('sub', 'undefined');
    $email = $request->getStr('email', 'undefined');
    $name = $request->getStr('realname');
    $provider = $request->getStr('provider');


    $identifiers = id(new PhabricatorExternalAccountIdentifierQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withRawIdentifiers(array(pht('id(%s)',$sub), $email))
                ->execute();

    if (empty($identifiers) || count($identifiers) < 2) {

      if ($name && $provider) {
        return $this->autoRegister($provider, $sub, $name, $email);
      }

      return new Aphront400Response();
    }

    // Ensure both are matched
    $phids = mpull($identifiers, 'getExternalAccountPHID');
    try {

      $external_accounts = id(new PhabricatorExternalAccountQuery())
                          ->setViewer(PhabricatorUser::getOmnipotentUser())
                          ->withPHIDs($phids)
                          ->execute();

      $exists_phids = mpull($external_accounts, 'getUserPHID');

      $exists = id(new PhabricatorPeopleQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs($exists_phids)
                    ->execute();
      if (count($exists) > 0) {
        $exists = head($exists);
        if ($name && $provider) {
          return $this->getMembershipStatus($exists);
        }

        $token = $this->obtainToken($exists);

        return id(new AphrontJSONResponse())
                ->setAddJSONShield(false)
                ->setContent(array(
                  'conduit_token' => $token->getToken(),
                  'user_phid' => $exists->getPHID(),
                  'external_account_phid' => head($phids),
                ));
      }
    } catch (Exception $e) {
    }

    return new Aphront400Response();
  }

  private function obtainToken(PhabricatorUser $exists) {
    $tokens = id(new PhabricatorConduitTokenQuery())
        ->setViewer($exists)
        ->withObjectPHIDs(array($exists->getPHID()))
        ->withExpired(false)
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->execute();

    $current_token = null;
    if (count($tokens) == 0) {
      // create token if there is no token yet
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $current_token = PhabricatorConduitToken::initializeNewToken(
          $exists->getPHID(),
          PhabricatorConduitToken::TYPE_STANDARD);
        $current_token->save();
      unset($unguarded);
    } else {
      // use current token
      $current_token = current($tokens);
    }

    return $current_token;
  }

  private function autoRegister($provider, $sub, $name, $email) {
    // First, check if the user exists
    $exists = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withEmails(array($email))
      ->executeOne();

    if (!$exists) {
      // If not exists create
      // set email
      $email_obj = id(new PhabricatorUserEmail())
        ->setAddress($email)
        ->setIsVerified(1);

      // initiate user object
      if (strpos($email, '@') !== false) {
        list($existsname, $_) = explode('@', $email);
      } else {
        $existsname = strtolower('sev2user').time();
      }

      $exists = new PhabricatorUser();
      $exists->setUsername($existsname.time());
      $exists->setRealname($name);
      $exists->setIsApproved(0);

      $exists->openTransaction();

      $editor = id(new PhabricatorUserEditor())
        ->setActor($exists);

      // create new user
      $editor->createNewUser($exists, $email_obj, false);

      // save user
      $exists->saveTransaction();
    }

    // Link account
    $providers = id(new PhabricatorAuthProviderConfigQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->execute();

    $auth_provider = null;
    foreach ($providers as $p) {
      if ($p->getProviderType() == $provider) {
        $auth_provider = $p;
      }
    }

    if (!$auth_provider || !$exists) {
      return new Aphront400Response();
    }

    $identifiers = array(
      $this->newAccountIdentifier($auth_provider, 'id', $sub),
      $this->newAccountIdentifier($auth_provider, 'email', $email),
    );

    $account = id(new PhabricatorExternalAccount());
    $account->setUserPHID($exists->getPHID());
    $account->setRealname($name);
    $account->setEmail($email);
    $account->setProviderConfigPHID($auth_provider->getPHID());
    $account->setAccountID('');
    $account->setAccountType($provider);
    $account->setAccountDomain($provider == 'google' ?
        'google.com' :
        'appleid.apple.com');
    $account->attachAccountIdentifiers($identifiers);

    $registration_key = Filesystem::readRandomCharacters(32);
    $account->setProperty(
      'registrationKey',
      PhabricatorHash::weakDigest($registration_key));

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
      $account->save();
    unset($unguarded);

    // Last, join public channel
    $xactions = array();
    $xactions[] = id(new ConpherenceTransaction())
      ->setTransactionType(
        ConpherenceThreadParticipantsTransaction::TRANSACTIONTYPE)
      ->setNewValue(array('+' => array($exists->getPHID())));

    $conpherences = id(new ConpherenceThreadQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPublic(true)
      ->needProfileImage(true)
      ->needTransactions(true)
      ->execute();
    $conpherence = head($conpherences);

    if ($conpherence) {
      id(new ConpherenceEditor())
        ->setActor($exists)
        ->setContentSource(PhabricatorContentSource::newForSource(
          SuiteContentSource::SOURCECONST))
        ->setContinueOnNoEffect(true)
        ->applyTransactions($conpherence, $xactions);
    }

    return $this->getMembershipStatus($exists);
  }

  private function newAccountIdentifier($provider, $type, $val) {
    if ($type == 'email') {
      return id(new PhabricatorExternalAccountIdentifier())
          ->setIdentifierRaw($val)
          ->setProviderConfigPHID($provider->getPHID());
    } else {
      $account_id = sprintf(
        'id(%s)',
        $val);
      return id(new PhabricatorExternalAccountIdentifier())
          ->setIdentifierRaw($account_id)
          ->setProviderConfigPHID($provider->getPHID());
    }
  }

  private function getMembershipStatus(PhabricatorUser $exists) {
    return id(new AphrontJSONResponse())
            ->setAddJSONShield(false)
            ->setContent(array(
              'phid' => $exists->getPHID(),
              'exists' => (bool)$exists->getPHID(),
              'is_guest' => (bool)!$exists->getIsApproved(),
              'is_admin' => (bool)$exists->getIsAdmin(),
              'is_member' => (bool)$exists->getIsApproved(),
              'is_connect' => (bool)$exists->getIsConnect(),
              'is_suite' => (bool)$exists->getIsSuite(),
              'is_dev' => (bool)$exists->getIsForDev(),
              'is_disabled' => (bool)($exists->getIsDisabled()
                || $exists->getIsSuiteDisabled()),
            ));
  }
}
