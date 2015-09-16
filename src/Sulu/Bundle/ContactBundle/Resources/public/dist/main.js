require.config({paths:{sulucontact:"../../sulucontact/dist","type/bic-input":"../../sulucontact/dist/validation/types/bic-input","type/vat-input":"../../sulucontact/dist/validation/types/vat-input","type/iban-input":"../../sulucontact/dist/validation/types/iban-input","extensions/iban":"../../sulucontact/dist/extensions/iban","vendor/iban-converter":"../../sulucontact/dist/vendor/iban-converter/iban","datagrid/decorators/card-view":"../../sulucontact/dist/components/card-decorator/card-view","services/sulucontact/contact-manager":"../../sulucontact/dist/services/contact-manager","services/sulucontact/account-manager":"../../sulucontact/dist/services/account-manager","services/sulucontact/account-router":"../../sulucontact/dist/services/account-router","services/sulucontact/contact-router":"../../sulucontact/dist/services/contact-router","services/sulucontact/account-delete-dialog":"../../sulucontact/dist/services/account-delete-dialog","extensions/sulu-buttons-contactbundle":"../../sulucontact/dist/extensions/sulu-buttons","type/customer-selection":"../../sulucontact/dist/validation/types/customer-selection","type/contact-selection":"../../sulucontact/dist/validation/types/contact-selection"},shim:{"vendor/iban-converter":{exports:"IBAN"}}}),define(["config","extensions/sulu-buttons-contactbundle","extensions/iban"],function(a,b,c){"use strict";return{name:"Sulu Contact Bundle",initialize:function(d){var e=d.sandbox;d.components.addSource("sulucontact","/bundles/sulucontact/dist/components"),c.initialize(d),e.sulu.buttons.push(b.getButtons()),e.sulu.buttons.dropdownItems.push(b.getDropdownItems()),a.set("sulucontact.components.autocomplete.default.contact",{remoteUrl:"/admin/api/contacts?searchFields=id,fullName&flat=true&fields=id,fullName&limit=25",getParameter:"search",resultKey:"contacts",valueKey:"fullName",value:"",instanceName:"contacts",noNewValues:!0,limit:25,fields:[{id:"id"},{id:"fullName"}]}),a.set("sulucontact.components.autocomplete.default.account",{remoteUrl:"/admin/api/accounts?searchFields=name,number&flat=true&fields=id,number,name,corporation&limit=25",resultKey:"accounts",getParameter:"search",valueKey:"name",value:"",instanceName:"accounts",noNewValues:!0,limit:25,fields:[{id:"number",width:"60px"},{id:"name",width:"220px"},{id:"corporation",width:"220px"}]}),a.set("sulucontact.routeToList.contact","contacts/contacts"),a.set("sulucontact.routeToList.account","contacts/accounts"),a.set("suluresource.filters.type.contacts",{routeToList:a.get("sulucontact.routeToList.contact")}),a.set("suluresource.filters.type.accounts",{routeToList:a.get("sulucontact.routeToList.account")}),e.mvc.routes.push({route:"contacts/contacts",callback:function(){return'<div data-aura-component="contacts/list@sulucontact"/>'}}),e.mvc.routes.push({route:"contacts/contacts/add",callback:function(){return'<div data-aura-component="contacts/edit@sulucontact"/>'}}),e.mvc.routes.push({route:"contacts/contacts/edit::id/:content",callback:function(a){return'<div data-aura-component="contacts/edit@sulucontact" data-aura-id="'+a+'"/>'}}),e.mvc.routes.push({route:"contacts/accounts",callback:function(){return'<div data-aura-component="accounts/list@sulucontact"/>'}}),e.mvc.routes.push({route:"contacts/accounts/add",callback:function(){return'<div data-aura-component="accounts/edit@sulucontact"/>'}}),e.mvc.routes.push({route:"contacts/accounts/edit::id/:content",callback:function(a){return'<div data-aura-component="accounts/edit@sulucontact" data-aura-id="'+a+'"/>'}})}}});