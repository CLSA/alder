cenozoApp.extendModule({
  name: "root",
  dependencies: ["review"],
  create: (module) => {
    var reviewModule = cenozoApp.module("review");

    // extend the view factory
    cenozo.providers.decorator("cnHomeDirective", [
      "$delegate",
      "$compile",
      "CnSession",
      "CnReviewModelFactory",
      function (
        $delegate,
        $compile,
        CnSession,
        CnReviewModelFactory
      ) {
        var oldController = $delegate[0].controller;
        var oldLink = $delegate[0].link;

        if (["coordinator", "typist"].includes(CnSession.role.name)) {
          // show typists their active reviews on their home page
          angular.extend($delegate[0], {
            compile: function () {
              return function (scope, element, attrs) {
                if (angular.isFunction(oldLink)) oldLink(scope, element, attrs);
                angular
                  .element(element[0].querySelector(".inner-view-frame div"))
                  .append('<cn-review-list model="reviewModel"></cn-review-list>');
                $compile(element.contents())(scope);
              };
            },
            controller: function ($scope) {
              oldController($scope);
              $scope.reviewModel = CnReviewModelFactory.instance();
              $scope.reviewModel.listModel.heading =
                "Outstanding " + reviewModule.name.singular.ucWords() + " List";
            },
          });
        }

        return $delegate;
      },
    ]);
  },
});
