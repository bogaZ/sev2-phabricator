/**
 * @provides javelin-behavior-inline-reply-menu
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-stratcom
 *           conpherence-thread-manager
 */

JX.behavior('inline-reply-menu', function(config, statics) {

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }

  var in_flight = false;

  /**
   * Mark notif as completed
   */
  JX.Stratcom.listen(
    ['click'],
    'inline-reply-workflow',
    function (e) {
      e.kill();

      in_flight = true;

      var current_menu = e.getNodes()["tag:a"];
      var thread_uri = current_menu.href;

      new JX.Workflow(thread_uri, {})
        .setCloseHandler(function() {
          JX.Stratcom.invoke('notification-panel-update', null, {});
          in_flight = false;
        })
        .setHandler(function(r) {
          JX.Stratcom.invoke('notification-panel-update', null, {});
          in_flight = false;
        })
        .start();
    }
  );

  // Periodical check for new messages
  setInterval(function(){
      if (!in_flight) {
        JX.Stratcom.invoke('notification-panel-update', null, {});
      }
  }, 30000);
});
