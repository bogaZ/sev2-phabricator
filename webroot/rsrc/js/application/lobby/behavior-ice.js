/**
 * @provides javelin-behavior-ice
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-stratcom
 *           suite-ice
 */

JX.behavior('ice', function(config, statics) {

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }
  // Helper to easily iterate nodes
  NodeList.prototype.forEach = Array.prototype.forEach;

  let localStream;
  let peerId;
  let peerChannel;
  const peers = {};

  var globalMute = true;

  var username = config.username;
  var password = config.password;

  var peerName = config.user_name;
  var peerImage = config.user_image;

  var localAudio = document.querySelector('#my-audio');

  const stunUrl = config.stun_url;
  const turnUrl = config.turn_url;
  var pcConfig = {
    iceServers: [{
      urls: turnUrl,
      username: username,
      credential: password
    },{
      urls: stunUrl
    }]
  };


  /**
   * General algorithm:
   * 1. get local stream
   * 2. connect to signaling server
   * 3. authenticate and wait for other client to connect
   * 4. when both peers are connected (socker receives joined message),
   *    start webRTC peer connection
   */
  getStream()
    .then(() => connectSocket())
    .catch(console.log);

  function getStream() {
    return navigator.mediaDevices
      .getUserMedia({
        video: false,
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
        }
      })
      .then(function (stream) {
        // console.log('Adding local stream.');
        localAudio.srcObject = stream;
        localStream = stream;

        // Muted first as default behaviour
        localStream.getAudioTracks()[0].enabled = !globalMute;
      })
      .catch(function(e) {
        // console.log(e.stack);
        alert('getUserMedia() error: ' + e.name);
      });
  }

  /////////////////////////////////////////////

  const room = config.threadPHID;
  const wsUrl = config.ws_base_url + room;
  var socket;

  // helper to send ws messages with {event, data} structure
  function sendMessage(event, message, toPeer) {
    const payload = {
      event,
      data: message
    };
    if (toPeer) {
      payload.to = toPeer;
    }
    // console.log('Client sending message: ', event, message);
    socket.send(JSON.stringify(payload));
  }

  //// SOCKET EVENT LISTENERS

  function authenticated (data) {
    peerId = data.peer_id;
    // console.log('authenticated:', peerId);
  }

  function joined (data) {
    // console.log('Start RTC', data);
    // start RTC as initiator with newly joined peer
    startRTC(data.peer_id, data.username, true);
  }

  function offer (data, fromPeer) {
    // received an offer, need to initiate rtc as receiver before answering
    // console.log("Start RTC", data);
    startRTC(fromPeer, null, false);

    const connection = peers[fromPeer].connection;
    connection.setRemoteDescription(new RTCSessionDescription(data));
    // console.log('Sending answer to peer.');
    connection.createAnswer().then(
      function(sessionDescription) {
        connection.setLocalDescription(sessionDescription);
        sendMessage('answer', sessionDescription, fromPeer);
      },
      logEvent('Failed to create session description:')
    );
  }

  function candidate(data, fromPeer) {
    var candidate = new RTCIceCandidate({
      sdpMLineIndex: data.label,
      candidate: data.candidate
    });
    peers[fromPeer].connection.addIceCandidate(candidate);
  }

  function answer (data, fromPeer) {
    peers[fromPeer].connection.setRemoteDescription(new RTCSessionDescription(data));
  }

  function left (data) {
    // console.log('Session terminated.');
    const otherPeer = data.peer_id;
    peers[otherPeer].connection.close();

    // remove dom element
    const element = document.getElementById(peers[otherPeer].element);
    element.srcObject = undefined;
    element.parentNode.removeChild(element);

    delete peers[otherPeer];

    JX.Stratcom.invoke('ice-current-peers', null, peers);
  }

  /*
   * Connect the socket and set up its listeners.
   * Will return a promise that resolves once both clients are connected.
   */
  function connectSocket() {
    // setting global var, sorry
    socket = new WebSocket(wsUrl);

    socket.onopen = function(event) {
      // console.log('socket connected');
      sendMessage('authenticate', {username, password});
    };

    socket.onclose = function(event) {
      // console.log('socket was closed', event);
    };

    const listeners = {
      authenticated,
      joined,
      left,
      candidate,
      offer,
      answer
    };

    socket.onmessage = function(e) {
      const data = JSON.parse(e.data);
      // console.log('Client received message:', data);
      const listener = listeners[data.event];
      if (listener) {
        listener(data.data, data.from);
      } else {
        // console.log('no listener for message', data.event);
      }
    };

  }

  ////////////////////////////////////////////////////

  function startRTC(peerId, peerPHID, isInitiator) {
    // console.log('>>>>>> creating peer connection');

    try {
      const connection = new RTCPeerConnection(pcConfig);

      connection.onicecandidate = getHandleIceCandidate(peerId);
      connection.ontrack = getHandleRemoteStream(peerId);
      connection.onremovestream = logEvent('Remote stream removed,');
      connection.onconnectionstatechange = getHandleStateChange(peerId);

      connection.addStream(localStream);


      peerChannel = connection.createDataChannel("side", {negotiated: true, id: 0});

      peerChannel.onopen = getHandleChannelOpen(peerId);
      peerChannel.onmessage = getHandleData(peerId);

      peers[peerId] = {connection};
      peers[peerId].phid = peerPHID;
      peers[peerId].muted = true;

      // console.log('Created RTCPeerConnnection for', peerId);

      if (isInitiator) {
        createOffer(peerId);
      }


      JX.Stratcom.invoke('ice-current-peers', null, peers);
    } catch (e) {
      // console.log('Failed to create PeerConnection, exception: ' + e.message);
      return;
    }
  }

  //// PeerConnection handlers

  function getHandleIceCandidate(peerId) {
    return function(event) {
      // console.log('icecandidate event: ', event);
      if (event.candidate) {
        sendMessage('candidate', {
          label: event.candidate.sdpMLineIndex,
          id: event.candidate.sdpMid,
          candidate: event.candidate.candidate
        }, peerId);
      } else {
        // console.log('End of candidates.');
      }
    };
  }

  function getHandleChannelOpen(peerId) {
    return function(event) {
      var ehlo = JSON.stringify({
        type:'ehlo',
        peerId:peerId,
        phid:username,
        name:peerName,
        image:peerImage});
      event.currentTarget.send(ehlo);
    };
  }

  function getHandleData(peerId) {
    return function(event) {
      if (!!event.data) {
        var peerData = JSON.parse(event.data);

        if (peerData.type) {
          switch (peerData.type) {
            case 'ehlo':
              // Map PHID to peers
              identifyPeer(peerData);
              break;

            case 'media_state':
              identifyPeer(peerData);
              break;

            default:
          }
        }
      }
    };
  }

  function getHandleStateChange(peerId) {
    return function(event) {
      if (event.currentTarget.connectionState) {
        var connectionState = event.currentTarget.connectionState;
        var internalStatus;
        var commonStatus;
        switch (connectionState) {
          case 'new':
          case 'connecting':
            internalStatus = 'Connecting';
            commonStatus = 'connecting';
            break;
          case 'connected':
            internalStatus = 'Connected';
            commonStatus = 'connected';
            break;
          default:
            internalStatus = connectionState[0].toUpperCase() + connectionState.substring(1);
            commonStatus = 'connecting';
            break;
        }

        console.log('New Status:', peerId, internalStatus, commonStatus);
      }
    };
  }

  function getHandleRemoteStream(peerId) {
    return function(event) {
      // console.log('Remote stream added for peer', peerId);
      const elementId = "audio-" + peerId;

      // this handler can be called multiple times per stream, only
      // add a new audio element once
      if (!peers[peerId].element) {
        const t = document.querySelector('#audio-template');
        t.content.querySelector('audio').id = elementId;
        const clone = document.importNode(t.content, true);
        document.getElementById("audios").appendChild(clone);
        peers[peerId].element = elementId;
      }
      // always set the srcObject to the latest stream
      document.getElementById(elementId).srcObject = event.streams[0];
      // document.getElementById(elementId).muted = globalMute;
    };
  }

  function createOffer(peerId) {
    // console.log('Sending offer to peer');
    const connection = peers[peerId].connection;
    connection.createOffer(function(sessionDescription) {
      connection.setLocalDescription(sessionDescription);
      sendMessage('offer', sessionDescription, peerId);
    }, logEvent('createOffer() error:'));
  }

  // event/error logger
  function logEvent(text) {
    return function (data) {
      // console.log(text, data);
    };
  }

  function identifyPeer(data) {

    for (var registeredPeer in peers) {
      if (Object.prototype.hasOwnProperty.call(peers, registeredPeer)) {
        var i = peers[registeredPeer];

        if (i.phid == data.phid) {
          // Already there
        } else {
          peers[registeredPeer].phid = data.phid;
        }

        if (data.type == "media_state") {
          peers[registeredPeer].muted = data.muted;
        } else {
          peers[registeredPeer].image = data.image;
          peers[registeredPeer].name = data.name;
        }
      }
    }

    JX.Stratcom.invoke('ice-current-peers', null, peers);
  }

  function sendMuteSignal(phid, value) {
    if (peerChannel && peerChannel.readyState == "open") {
      var peerData = JSON.stringify({
        type:'media_state',
        phid:phid,
        muted:value});

      peerChannel.send(peerData);
    }
  }

  JX.Stratcom.listen('ice-toggle-mute', null, function(e) {
    var actor = e.getData();
    globalMute = !globalMute;

    if (localStream) {
      localStream.getAudioTracks()[0].enabled = !globalMute;
    }

    sendMuteSignal(actor.phid, globalMute);

    // var audios = JX.$('audios');
    // var children = audios.childNodes;
    // children.forEach(function(item){
    //   if (item.id != "my-audio") {
    //     item.muted = globalMute;
    //   }
    // });
  });

  JX.Stratcom.listen('ice-get-peers', null, function(e) {
    JX.Stratcom.invoke('ice-current-peers', null, peers);
  });

});
