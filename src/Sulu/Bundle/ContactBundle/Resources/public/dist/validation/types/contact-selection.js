define(["type/default"],function(a){"use strict";var b=function(a,b){App.emit("sulu.preview.update",b,a),App.emit("sulu.content.changed")};return function(c,d){var e={},f={initializeSub:function(){var a="sulu.contact-selection."+d.instanceName+".data-changed";App.off(a,b),App.on(a,b)},setValue:function(a){App.dom.data(c,"contact-selection",a)},getValue:function(){return App.dom.data(c,"contact-selection")},needsValidation:function(){return!1},validate:function(){return!0}};return new a(c,e,d,"contactSelection",f)}});