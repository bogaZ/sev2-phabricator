<?php

final class LobbyPilotController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $user = $request->getUser();
    $phids = $request->getArr('pilotPHID');
    $phid = head($phids);

    if ($request->isFormPost()) {
      try {
        $channel_table = new ConpherenceThread();
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
          $conn_w = $channel_table->establishConnection('w');

          // Set all channels as non-HQ
          queryfx(
            $conn_w,
            'UPDATE %T SET isHQ = %d',
            $channel_table->getTableName(),
            0);

          // Set selected channel as non-HQ
          queryfx(
            $conn_w,
            'UPDATE %T SET isHQ = %d WHERE phid = %s',
            $channel_table->getTableName(), 1, $phid);
        unset($unguarded);

      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }

      return id(new AphrontRedirectResponse())->setURI('/');
    }


    $title = 'We need a Pilot';
    $content = pht('Hi %s. Each company deserve to'.
      ' have a HQ, a place for everyone.',
      $user->getRealname());

    $conpherence_ds = id(new LobbyConpherenceDatasource());

    $pilot_form = id(new AphrontFormView())
      ->setUser($user)
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setLabel(pht('Pilot Channel'))
          ->setName('pilotPHID')
          ->setLimit(1)
          ->setValue(array())
          ->setDatasource($conpherence_ds));

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($title)
      ->appendParagraph($content)
      ->appendForm($pilot_form)
      ->addCancelButton('/')
      ->addSubmitButton('Set Pilot');
  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
