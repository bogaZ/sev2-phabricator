/**
 * @provides javelin-behavior-availability
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-stratcom
 *           javelin-scrollbar
 *           conpherence-thread-manager
 */

JX.behavior('availability', function(config, statics) {

  var current_request;
  var data = [];
  var availability_uri = config.availability_uri;

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
    NodeList.prototype.forEach = Array.prototype.forEach;
  }

  function _showContent() {
    const squares = document.querySelector('.squares');
    for (var i = 1; i < 365; i++) {
      var level = 0;
      var title = 'No activity found';

      if (data[i]) {
        level = data[i].level;
        title = data[i].title;
      }

      squares.insertAdjacentHTML('beforeend', `<li data-level="${level}" title="${title}"></li>`);
    }
  }

  current_request = new JX.Request(availability_uri, function(r) {
    data = r.data;
    console.log(data);
  });
  current_request.listen('finally', _showContent);
  current_request.send();

});
