/**
 * @provides javelin-behavior-media-state
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-stratcom
 */

JX.behavior('media-state', function(config, statics) {

  var user_phid = config.user_phid;
  var user_image = config.user_image;
  var user_name = config.user_name;
  var globalMute = true;

  var members = {};
  var empty_view;
  var body;

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
    members[user_phid] = {
      phid: user_phid,
      image: user_image,
      name: user_name,
      muted: true,
    };

    empty_view = JX.$('lobby-media-empty-view');
    body = JX.$('lobby-box-body-container');
  }

  function _showEmpty() {
    JX.DOM.setContent(body, empty_view);
  }

  function _showMembers(participants) {

    var members = [];
    var phids = [];

    for (var i in participants) {
      if (Object.prototype.hasOwnProperty.call(participants, i)) {
        var member = participants[i];
        var phid = member.phid;

        if (phid && typeof phids[phid] === 'undefined') {
            // does not exist

          var muted = member.phid == user_phid ? globalMute : member.muted;

          var image = member.image;
          var name = member.name;

          var icon = muted ? ' fa-microphone-slash ' : ' fa-microphone ';
          var color = muted ? ' red ' : ' green ';

          var member_el = `<a href="#!" class="phui-icon-circle
            hover-violet circle-large phui-icon-circle-state mmr" id="member-${phid}" title="${name}">
            <span class="visual-only phui-icon-view phui-font-fa
            phui-icon-circle-icon lobby-circle" style="background-image:url(${image});">
            <span class="visual-only phui-icon-view phui-font-fa
            ${icon} ${color} phui-icon-circle-state-icon"
              aria-hidden="true" data-sigil="member-media-state"></span></span></a>`;

          members.push(member_el);
          phids.push(phid);
        }
      }
    }

    var box = '<div class="phui-box members">'+members.join('')+'</div>';

    JX.DOM.setContent(body, JX.$H(box));
  }

  JX.Stratcom.listen('ice-toggle-mute', null, function(e) {
    var actor = e.getData();
    globalMute = !globalMute;

    try {

      var my_member = JX.$('member-'+user_phid);
      var my_indicator = JX.DOM.find(my_member, 'span', 'member-media-state');


      var icon = globalMute ? 'fa-microphone-slash' : 'fa-microphone';
      var color = globalMute ? 'red' : 'green';


      JX.DOM.alterClass(my_indicator, 'green', !globalMute);
      JX.DOM.alterClass(my_indicator, 'red', globalMute);

      JX.DOM.alterClass(my_indicator, 'fa-microphone', !globalMute);
      JX.DOM.alterClass(my_indicator, 'fa-microphone-slash', globalMute);
    } catch (e) {

    }
  });

  JX.Stratcom.listen('ice-current-peers', null, function(e) {
    var peers = e.getData();
    var count = 0;
    var counterparts = members;

    for (var registeredPeer in peers) {
      if (Object.prototype.hasOwnProperty.call(peers, registeredPeer)) {
        count++;
        peer = peers[registeredPeer];
        peerPHID = peer.phid;

        counterparts[peerPHID] = {
          phid: peer.phid,
          image: peer.image,
          name: peer.name,
          muted: peer.muted
        }
      }
    }

    if (count > 0) {
      _showMembers(counterparts);
    } else {
      _showEmpty();
    }
  });


});
