<?php

final class PhabricatorLobbyApplication extends PhabricatorApplication {

  public function canUninstall() {
    return false;
  }

  public function getName() {
    return pht('Lobby');
  }

  public function getBaseURI() {
    return '/lobby/';
  }

  public function getIcon() {
    return 'fa-heartbeat';
  }

  public function getShortDescription() {
    return pht('Lobby is a place where we start our day :)');
  }

  public function getTitleGlyph() {
    return "\xC2\xA9";
  }

  public function getApplicationGroup() {
    return self::GROUP_CORE;
  }

  public function isPrototype() {
    return true;
  }

  public function getRoutes() {
    return array(
      '/today/' => array(
        $this->getQueryRoutePattern() =>
          'LobbyGoalsListController',
        $this->getEditRoutePattern('edit/') =>
          'LobbyGoalsEditController',
        '(?:(?P<id>\d+)/)?' => 'LobbyGoalsDetailController',
      ),
      '/lobby/' => array(
        'join' => 'LobbyJoinController',
        'pilot' => 'LobbyPilotController',
        'state' => 'LobbyStateController',
        'reload' => 'LobbyReloadController',
        'leave' => 'LobbyLeaveController',
        'break/(?P<phid>[^/]+)/(?:(?P<const>\d+)/)?' => 'LobbyBreakController',
        'current/(?P<phid>[^/]+)/' => 'LobbyCurrentTaskController',
        'availability/(?P<phid>[^/]+)/' => 'LobbyAvailabilityGraphController',
        'in-channel/(?P<phid>[^/]+)' => 'LobbyInChannelController',
        'out-channel/(?P<phid>[^/]+)' => 'LobbyOutChannelController',
        'inline-reply/(?:(?P<const>\d+)/)?' => 'LobbyInlineReplyController',
        'conph/assoc/(?P<edgetype>[^/]+)/(?:(?P<room_id>\d+))/(?:(?P<id>\d+)/)?'
          => 'LobbyEdgeAssocController',
        'conph/(?P<edgetype>[^/]+)/(?:(?P<id>\d+)/)?' => 'LobbyEdgeController',
        'reaction/(?<phid>[^/]+)/'
          => 'LobbyTokenGiveController',
        $this->getQueryRoutePattern() => 'LobbyHomeController',
        'stickit/' => array(
          $this->getQueryRoutePattern() =>
            'LobbyStickitListController',
          $this->getEditRoutePattern('edit/') =>
            'LobbyStickitEditController',
          '(?:(?P<id>\d+)/)?' => 'LobbyStickitDetailController',
        ),
        'goals/' => array(
          $this->getQueryRoutePattern() =>
            'LobbyGoalsListController',
          $this->getEditRoutePattern('edit/') =>
            'LobbyGoalsEditController',
          '(?:(?P<id>\d+)/)?' => 'LobbyGoalsDetailController',
        ),

        'moderators/' => array(
          $this->getQueryRoutePattern() =>
            'LobbyModeratorListController',
          $this->getEditRoutePattern('edit/') =>
            'LobbyModeratorEditController',
          'disable/(?:(?P<id>\d+)/)?' =>
            'LobbyModeratorDisableController',
        ),
      ),
    );
  }

  protected function getCustomCapabilities() {
    return array(
      LobbyManageCapability::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_ADMIN,
        'caption' => pht('Default manage policy for Lobby.'),
      ),
      LobbyJoinCapability::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_USER,
        'caption' => pht('Default join policy for Lobby.'),
      ),
    );
  }

}
