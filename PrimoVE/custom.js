document.body.insertBefore(gtm, document.body.firstChild);app.component('myInstitutionComponent', {    
bindings: { parentCtrl: '<' },    
template: '<div id="Content" ng-controller="MyInstitutionComponentController"></div>‘
});

app.component('prmSearchBarAfter', {    
bindings: { parentCtrl: '<' },    
template: '<my-institution-component parent-ctrl="$ctrl.parentCtrl"></my-institution-component>‘
});

//***Replace the link in the data attribute with the link to your own html output

app.controller('MyInstitutionComponentController', ['$scope', function ($scope) {    
$scope.bookget = function () {        document.getElementById("Content").innerHTML = '<iframe src="https://joelle.domains.trincoll.edu/pvenewbooks.html" style="border:none; width:100%;" id="bookbox";>New books visual display</iframe>';    };    
$scope.bookget();
}]);
