cenozoApp.extendModule({
  name: "root",
  dependencies: ["apex_review", "review"],
  create: (module) => {
    var apexReviewModule = cenozoApp.module("apex_review");
    var reviewModule = cenozoApp.module("review");

    // extend the view factory
    cenozo.providers.decorator("cnHomeDirective", [
      "$delegate",
      "$compile",
      "CnSession",
      "CnHttpFactory",
      "CnApexReviewModelFactory",
      "CnReviewModelFactory",
      "$state",
      "$window",
      function (
        $delegate,
        $compile,
        CnSession,
        CnHttpFactory,
        CnApexReviewModelFactory,
        CnReviewModelFactory,
        $state,
        $window
      ) {
        var oldController = $delegate[0].controller;
        var oldLink = $delegate[0].link;

          // show typists their active reviews on their home page
          angular.extend($delegate[0], {
            compile: function () {
              return function (scope, element, attrs) {
                if (angular.isFunction(oldLink)) oldLink(scope, element, attrs);
                const el = angular.element(element[0].querySelector(".inner-view-frame div"));
                el.append(`
                  <div class="row">
                    <div class="container-fluid vertical-spacer">
                      <label class="control-label" for="apex_user">
                        Review Mode (determines whether you are in Apex re-analysis or quality assessment mode)
                      </label>
                      <select
                        id="apex_user"
                        class="form-control"
                        ng-model="apex_user"
                        ng-change="updateApexMode()"
                      >
                        <option value="1">Apex Re-Analysis</option>
                        <option value="0">Quality Assessment</option>
                      </select>
                    </div>
                  </div>
                `);
                if (["coordinator", "typist"].includes(CnSession.role.name)) {
                  el.append(
                    CnSession.user.apexUser ? `
                      <cn-apex-review-list
                        model="reviewModel"
                        class="row"
                        remove-columns="status end_datetime"
                      ></cn-apex-review-list>
                    ` :
                    '<cn-review-list model="reviewModel" class="row"></cn-review-list>'
                  );
                }
                $compile(element.contents())(scope);
              };
            },
            controller: function ($scope) {
              oldController($scope);

              // determine the user's current apex mode
              $scope.apex_user = CnSession.user.apexUser ? "1" : "0";

              $scope.updateApexMode = async () => {
                await CnHttpFactory.instance({
                  path: "self/0",
                  data: { apex_user: Number($scope.apex_user) },
                }).patch();
                await $state.go("self.wait");
                $window.location.reload();
              };

              if (["coordinator", "typist"].includes(CnSession.role.name)) {
                $scope.reviewModel = (
                  CnSession.user.apexUser ?
                  CnApexReviewModelFactory.instance() :
                  CnReviewModelFactory.instance()
                );
                $scope.reviewModel.listModel.heading = "Outstanding Review List";

                if ($scope.reviewModel.isRole("typist") && CnSession.user.apexUser) {
                  // only show uploaded reviews to apex typists
                  $scope.reviewModel.getServiceData = function(type, columnRestrictList) {
                    const data = this.$$getServiceData(type, columnRestrictList);
                    if (!data.modifier.where) data.modifier.where = [];
                    data.modifier.where.push({ column: "status", operator: "=", value: "Uploaded" });
                    return data;
                  };
                }
              }
            },
          });

        return $delegate;
      },
    ]);
  },
});
