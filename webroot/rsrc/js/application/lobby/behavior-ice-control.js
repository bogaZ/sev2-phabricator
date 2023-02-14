/**
 * @provides javelin-behavior-ice-control
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-stratcom
 */

JX.behavior('ice-control', function(config, statics) {

  var username = config.username;
  var room = config.threadPHID;

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }

  function _toggleMute() {

    var lobby_control = JX.$('lobby-box-footer-container');
    var mute_button = JX.DOM.find(lobby_control, 'a',
      'lobby-control-toggle-microphone');

    var btn_class = mute_button.className;
    var muted = ((' '+btn_class+' ').indexOf(' button-red ') > -1);

    allMuted = !muted;

    JX.DOM.alterClass(mute_button, 'button-green', !allMuted);
    JX.DOM.alterClass(mute_button, 'button-red', allMuted);

    var icon = !allMuted ? 'microphone ' : 'microphone-slash ';
    var streaming = JX.$H(`<span class="visual-only phui-icon-view
    phui-font-fa fa-${icon} bluegrey"
    aria-hidden="true"></span>`);
    JX.DOM.setContent(mute_button, streaming);
  }

  JX.Stratcom.listen('click', 'lobby-control-toggle-microphone', function(e) {
    // Control toggle
    _toggleMute();

    // Emit audio state
    JX.Stratcom.invoke('ice-toggle-mute', null, {phid:username});
  });
});
