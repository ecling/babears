(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _modal = require('../ui/modal.jsx');

var _modal2 = _interopRequireDefault(_modal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var IEOverlay = function () {

  var Overlay = React.createClass({
    displayName: 'Overlay',

    render: function render() {
      var overLayStyles = {
        width: '600px',
        height: '150px',
        position: 'relative',
        top: '50%',
        left: '50%',
        marginTop: '-75px',
        marginLeft: '-300px',
        backgroundColor: 'white',
        textAlign: 'center',
        fontFamily: 'helvetica, arial, sans-serif',
        zIndex: '11'
      };

      var h1Styles = {
        fontSize: '24px',
        lineHeight: '28px',
        color: '#141823',
        fontWeight: 'normal',
        paddingTop: '44px'
      };

      var h2Styles = {
        fontSize: '14px',
        lineHeight: '20px',
        color: '#9197a3',
        fontWeight: 'normal'
      };

      return React.createElement(
        'div',
        { style: overLayStyles, id: 'ieOverlay' },
        React.createElement(
          'h1',
          { style: h1Styles },
          'Internet Explorer Not Supported'
        ),
        React.createElement(
          'h2',
          { style: h2Styles },
          'Please use a modern browser such as Google Chrome or Mozilla Firefox'
        )
      );
    }
  });

  return {
    render: function render() {
      var containerId = 'page:main-container';
      var containerEl = document.getElementById(containerId);
      containerEl.style.position = 'relative';

      var ieContainer = document.createElement('div');
      ieContainer.id = 'ie-container';

      ieContainer.style.width = '100%';
      ieContainer.style.height = '100%';
      ieContainer.style.position = 'absolute';
      ieContainer.style.top = '0';
      ieContainer.style.left = '0';
      ieContainer.style.backgroundColor = 'rgba(0,0,0,0.3)';

      containerEl.appendChild(ieContainer);
      ReactDOM.render(React.createElement(Overlay, null), ieContainer);
    }
  };
}();

exports.default = IEOverlay;

},{"../ui/modal.jsx":5}],2:[function(require,module,exports){
'use strict';

var _utils = require('./utils.js');

var _container = require('./pixelFlow/container.jsx');

var _container2 = _interopRequireDefault(_container);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

(0, _utils.onPageLoad)(function () {
  ReactDOM.render(React.createElement(_container2.default, {
    currentPixelId: window.facebookMarketingConfig.pixelId }), document.getElementById('pixel-flow'));
});

},{"./pixelFlow/container.jsx":3,"./utils.js":6}],3:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _modal = require('../ui/modal.jsx');

var _modal2 = _interopRequireDefault(_modal);

var _fbAuthFlow = require('./fbAuthFlow.jsx');

var _fbAuthFlow2 = _interopRequireDefault(_fbAuthFlow);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var PixelFlowContainer = React.createClass({
  displayName: 'PixelFlowContainer',

  getInitialState: function getInitialState() {
    return {
      currentPixelId: this.props.currentPixelId,
      modal: false
    };
  },
  onPixelSelected: function onPixelSelected(pixelId) {
    this.savePixelId(pixelId);
  },
  savePixelId: function savePixelId(pixelId) {
    new Ajax.Request(facebookMarketingConfig.pixelAjaxRoute, {
      parameters: { pixelId: pixelId },
      onSuccess: function (transport) {
        var response = transport.responseText.evalJSON();

        if (response.success) {
          window.setCurrentPixelId = response.pixelId;
          this.modalMessage = "The Facebook Pixel with ID: " + response.pixelId + " is now installed on your website.";
        } else {
          this.modalMessage = "There was a problem saving the pixel. Please try again";
        }

        this.setState({
          isDisabled: true,
          storedPixelId: response.pixelId,
          currentPixelId: response.pixelId,
          modal: true
        });
      }.bind(this)
    });
  },
  closeModal: function closeModal() {
    this.setState({ modal: false });
  },
  render: function render() {
    var currentPixelMessage = this.state.currentPixelId ? React.createElement(
      'h2',
      null,
      'Current installed pixel: ',
      this.state.currentPixelId
    ) : null;
    var modal = this.state.modal ? React.createElement(_modal2.default, { onClose: this.closeModal, message: this.modalMessage }) : null;
    return React.createElement(
      'div',
      { className: 'pixelFlowContainer' },
      modal,
      React.createElement(
        'h1',
        null,
        'Easily install the Facebook Pixel on every page of your website'
      ),
      React.createElement(
        'h2',
        null,
        'Use information from your pixel to make Facebook ads that better reach your customers.'
      ),
      currentPixelMessage,
      React.createElement(_fbAuthFlow2.default, {
        onPixelSelected: this.savePixelId,
        currentPixelId: this.state.currentPixelId })
    );
  }
});

exports.default = PixelFlowContainer;

},{"../ui/modal.jsx":5,"./fbAuthFlow.jsx":4}],4:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var FacebookAuthFlow = React.createClass({
  displayName: 'FacebookAuthFlow',

  getInitialState: function getInitialState() {
    return {
      messageChannelConnected: false
    };
  },
  bindMessageEvents: function bindMessageEvents() {
    window.addEventListener("message", this.receiveMessage, false);

    if (this.isIE() && window.MessageChannel) {
      this.setState({ messageChannelConnected: false });
      this.fbMessageChannel = new MessageChannel();
      this.fbMessageChannel.port1.onmessage = this.receiveMessageFromMessageChannel;
    }
  },
  isIE: function isIE() {
    return (/MSIE |Trident\/|Edge\//.test(document.location.userAgent)
    );
  },
  receiveMessage: function receiveMessage(event) {
    var origin = event.origin || event.originalEvent.origin;
    if (origin === window.facebookMarketingConfig.popupOrigin) {
      this.props.onPixelSelected(event.data);
    }
  },
  openPopup: function openPopup() {
    var width = 600;
    var height = 400;
    var topPos = screen.height / 2 - height / 2;
    var leftPos = screen.width / 2 - width / 2;

    if (this.popupWindow) {
      this.popupWindow.close();
    }

    this.popupWindow = window.open(facebookMarketingConfig.popupOrigin + '/login.php?display=popup&next=' + encodeURIComponent(facebookMarketingConfig.popupOrigin + '/ads_toolbox/pixel_selector/?' + ['pixel_id=' + this.props.currentPixelId, 'timezone_id=' + window.facebookMarketingConfig.timezoneId, 'store_name=' + window.facebookMarketingConfig.storeName, 'base_currency=' + window.facebookMarketingConfig.baseCurrency, 'display=popup', 'source=magento'].join('&')), 'PixelSelector', ['toolbar=no', 'location=no', 'directories=no', 'status=no', 'menubar=no', 'scrollbars=no', 'resizable=no', 'copyhistory=no', 'width=' + width, 'height=' + height, 'top=' + topPos, 'left=' + leftPos].join(','));

    if (this.isIE() && window.MessageChannel) {
      this.connectMessageChannel();
    }
  },
  connectMessageChannel: function connectMessageChannel() {
    if (!this.state.messageChannelConnected) {
      setTimeout(function () {
        this.popupWindow.postMessage({ message: "Facebook Ads Toolbox connect message" }, "*", [this.fbMessageChannel.port2]);
      }.bind(this), 500);
    }
  },
  receiveMessageFromMessageChannel: function receiveMessageFromMessageChannel(e) {
    if (e.data.message === 'Facebook Ads Toolbox connection made') {
      this.state.messageChannelConnected = true;
    } else if (e.data.message === 'Facebook Ads Toolbox pixel selected') {
      this.props.onPixelSelected(e.data.pixelId);
    }
  },
  componentDidMount: function componentDidMount() {
    this.bindMessageEvents();
  },

  render: function render() {
    return React.createElement(
      'div',
      { id: 'facebookFlow' },
      React.createElement(
        'button',
        { className: 'facebookButton blue', onClick: this.openPopup },
        React.createElement('i', { className: 'logo' }),
        'Get Started'
      )
    );
  }
});

exports.default = FacebookAuthFlow;

},{}],5:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var Modal = React.createClass({
  displayName: 'Modal',

  render: function render() {
    return React.createElement(
      'div',
      { className: 'modal-container' },
      React.createElement(
        'div',
        { className: 'modal' },
        React.createElement(
          'div',
          { className: 'modal-header' },
          this.props.title
        ),
        React.createElement(
          'div',
          { className: 'modal-content' },
          this.props.message
        ),
        React.createElement(
          'div',
          { className: 'modal-close' },
          React.createElement(
            'button',
            { onClick: this.props.onClose, className: 'medium blue' },
            'OK'
          )
        )
      )
    );
  }
});

exports.default = Modal;

},{}],6:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.onPageLoad = onPageLoad;
exports.isIE = isIE;
exports.parseURL = parseURL;
exports.urlFromSameDomain = urlFromSameDomain;
exports.safeJSONParse = safeJSONParse;
exports.togglePopupOriginBusiness = togglePopupOriginBusiness;

var _ieOverlay = require('./fb/ieOverlay.jsx');

var _ieOverlay2 = _interopRequireDefault(_ieOverlay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

(function () {
  Ajax._getTransport = Ajax.getTransport;
  Ajax._timeout = 30 * 60 * 1000; // 30min
  Ajax.getTransport = function () {
    var t = Ajax._getTransport();
    t.timeout = Ajax._timeout;
    return t;
  };

  console.log('[Facebook Ads Toolbox] hack Ajax.Request to support timeout complete.');
})(); /**
       * Copyright (c) 2016-present, Facebook, Inc.
       * All rights reserved.
       *
       * This source code is licensed under the BSD-style license found in the
       * LICENSE file in the root directory of this source tree. An additional grant
       * of patent rights can be found in the PATENTS file in the code directory.
       */

function onPageLoad(runfn) {
  if (!runfn) {
    return;
  }

  var display = function display() {
    if (isIE()) {
      _ieOverlay2.default.render();
    }
    runfn();
  };

  if (document.readyState === 'interactive') {
    // in case the document is already rendered
    display();
  } else if (document.addEventListener) {
    // modern browsers
    document.addEventListener('DOMContentLoaded', display);
  } else {
    document.attachEvent('onreadystatechange', function () {
      // IE <= 8
      if (document.readyState === 'complete') {
        display();
      }
    });
  }
};

function isIE() {
  return (/MSIE |Trident\/|Edge\//.test(window.navigator.userAgent)
  );
};

function parseURL(url) {
  var parser = document.createElement('a');
  parser.href = url;
  return parser;
};

function urlFromSameDomain(url1, url2) {
  var u1 = parseURL(url1);
  var u2 = parseURL(url2);
  return u1.protocol === u2.protocol && u1.host === u2.host;
};

function safeJSONParse(jsonstr) {
  try {
    return JSON.parse(jsonstr);
  } catch (e) {
    console.log('Failed parse jsonstr:' + jsonstr);
    return undefined;
  }
};

function togglePopupOriginBusiness(dia_origin) {
  var current_origin = window.facebookAdsToolboxConfig.popupOrigin;
  if (dia_origin.includes('business') && !current_origin.includes('business')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('www', 'business');
  } else if (!dia_origin.includes('business') && current_origin.includes('business')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('business', 'www');
  }
};

},{"./fb/ieOverlay.jsx":1}]},{},[2]);
