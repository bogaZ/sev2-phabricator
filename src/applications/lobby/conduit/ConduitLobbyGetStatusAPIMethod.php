<?php

final class ConduitLobbyGetStatusAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.status';
  }

  public function getMethodDescription() {
    return pht('Get Availability Status');
  }

  public function getMethodSummary() {
    return pht('Get Availability Status.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
      $indexes = array();
      $result = array();
      $results = array();

      for ($i = 3; $i <= 8; $i++) {
          $indexes[] = $i;
      }

      $statuses = LobbyState::getStatusMap();
      $icons = LobbyState::getStatusIconMap();

      foreach ($indexes as $index) {
          $result['id'] = $index;
          $result['status'] = $statuses[$index];
          $result['icon'] = $icons[$index];

          $results[] = $result;
      }

      return array(
          'data' => $results,
      );
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
