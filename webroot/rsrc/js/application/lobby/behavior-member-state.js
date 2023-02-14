/**
 * @provides javelin-behavior-member-state
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-stratcom
 */

JX.behavior('member-state', function(config, statics) {

  var user_phid = config.username;
  var topic = config.threadPHID;
  var check_url = config.check_url;
  var sound_url = config.sound_url;

  var first_time_joined = false;
  var play_notif = false;
  var members = [user_phid];
  var current_request;
  var members_pane;

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
    JX.Sound.load(sound_url);
  }

  function _reloadMembersPane() {
    var body = JX.$('lobby-box-members');
    if (members_pane) {
      JX.DOM.setContent(body, JX.$H(members_pane));
    }
  }

  function resetMember() {
    if (current_request) {
      current_request.abort();
      current_request = null;
    }
    members_pane = null;

    current_request = new JX.Request(check_url, function(r) {
      members_pane = r.members_pane;
    });
    // current_request.listen('send', _showLoading);
    current_request.listen('finally', _reloadMembersPane);
    current_request.send();
  }

  function addMember(phid) {
    var index = members.indexOf(phid);
    if (index == -1) {
      members.push(phid);
      play_notif = true;
      resetMember();
    }
  }

  function removeMember(phid) {
    var index = members.indexOf(phid);
    if (index !== -1) {
      members.splice(phid, 1);
      play_notif = true;
      resetMember();
    }
  }

  JX.Stratcom.listen('aphlict-server-message', null, function(e) {
    var message = e.getData();

    if (!!message.type
      && message.type != "state" && message.type != "message") {
      return;
    }

    if (!!message.type
      && message.type == "state" && message.target == user_phid
      && !first_time_joined) {
      first_time_joined = true;
    } else if (message.threadPHID == topic && message.target != user_phid) {
      var notification;
      if (message.type == "message") {
        
      } else {
        switch (message.state) {
          case "joining":
            addMember(message.target);
            notification = new JX.Notification();

            notification.setContent(message.target_data.name + ' joining');
            notification.setTitle("Channel info");
            notification.setBody(message.target_data.name + ' joining');
            notification.setIcon(message.target_data.image_uri);
            break;
          case "leaving":
            removeMember(message.target);
            notification = new JX.Notification();

            notification.setContent(message.target_data.name + ' leaving')
              .alterClassName('jx-notification-alert', true);
            notification.setTitle("Channel info");
            notification.setBody(message.target_data.name + ' leaving');
            notification.setIcon(message.target_data.image_uri);
            break;
          default:
        }
      }

      if (notification && play_notif) {
        notification.setShowAsDesktopNotification(true);
        notification.setDuration(4000);
        notification.show();
        setTimeout(function(){
          JX.Sound.play(sound_url);
        }, 300);

        play_notif = false;
      }
    }
  });

});
