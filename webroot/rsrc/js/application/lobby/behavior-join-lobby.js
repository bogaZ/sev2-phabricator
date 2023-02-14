 /**
  * @provides javelin-behavior-join-lobby
  * @requires javelin-behavior
  *           javelin-dom
  *           javelin-util
  *           javelin-workflow
  *           javelin-aphlict
  *           javelin-stratcom
  */

 JX.behavior('join-lobby', function(config) {

   var device;
   var topic = "lobby";
   var main_pane = JX.$('lobby-main-pane');
   var side_pane = JX.$('lobby-side-pane');

   function init() {
     setTimeout(function(){
       var client = JX.Aphlict.getInstance();
       if (client) {
         client.replay();
       }
     }, 2000);

     device = JX.Device.getDevice();
     if (device != 'phone') {
       config.device = "desktop";
     }
   }
   init();


   function on_reload() {
     request = new JX.Request(config.reload_uri, function(response) {
          JX.DOM.setContent(side_pane, JX.$H(response.side_pane));
          JX.DOM.setContent(main_pane, JX.$H(response.main_pane));
     });
     request.send();
   }

   request = new JX.Request(config.uri, function(response) {
     // Add subscription to the lobby
     var client = JX.Aphlict.getInstance();
     if (client) {
       var has_lobby = false;
       var old_subs = client.getSubscriptions();
       var new_subs = [];
       for (var ii = 0; ii < old_subs.length; ii++) {
         if (old_subs[ii] == topic) {
           has_lobby = true;
           continue;
         }
         new_subs.push(old_subs[ii]);
       }

       new_subs.push(topic);
       client.clearSubscriptions(client.getSubscriptions());
       client.setSubscriptions(new_subs);
       client.replay();
     }
   });
   request.setData(config);
   request.send();


   JX.Stratcom.listen('aphlict-server-message', null, function(e) {
     var message = e.getData();

     if (message.type != topic) {
       return;
     }

     // Check if this update notification is about the currently visible
     // board. If it is, update the board state.

     on_reload();
   });

   JX.Stratcom.listen('aphlict-reconnect', null, function(e) {
     on_reload();
   });
 });
