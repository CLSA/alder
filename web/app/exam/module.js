cenozoApp.defineModule({
  name: "exam",
  models: ["list", "view"],
  defaultTab: "review",
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "interview",
          column: "interview.id",
        },
      },
      name: {
        singular: "exam",
        plural: "exams",
        possessive: "exam's",
      },
      columnList: {
        modality: {
          column: "modality.name",
          title: "Modality",
        },
        scan_type: {
          title: "Scan Type",
        },
        interviewer: {
          title: "Interviewer",
        },
        user_list: {
          title: "Reviewers",
        },
        datetime: {
          title: "Date & Time",
          type: "datetime",
        },
      },
      defaultOrder: {
        column: "participant.uid",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      uid: {
        column: "participant.uid",
        title: "Participant",
        type: "string",
        isConstant: true,
      },
      phase: {
        column: "study_phase.name",
        title: "Phase",
        type: "string",
        isConstant: true,
      },
      modality: {
        column: "modality.name",
        title: "Modality",
        type: "string",
        isConstant: true,
      },
      scan_type: {
        title: "Scan Type",
        type: "string",
        isConstant: true,
      },
      interviewer: {
        title: "Interviewer",
        type: "string",
        isConstant: true,
      },
      datetime: {
        title: "Date & Time",
        type: "datetime",
        isConstant: true,
      },
      note: {
        column: "exam.note",
        title: "Note",
        type: "text",
      },
    });

    module.addExtraOperation("view", {
      title: "Display",
      operation: async function ($state, model) {
        await $state.go(
          "exam.display",
          { identifier: model.viewModel.record.getIdentifier() }
        );
      },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnExamDisplay", [
      "CnExamModelFactory",
      "CnSession",
      "$state",
      function (CnExamModelFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("display.tpl.html"),
          restrict: "E",
          controller: async function ($scope) {
            if (angular.isUndefined($scope.model)) $scope.model = CnExamModelFactory.root;

            angular.extend($scope, {
              directive: "cnRecordView",
              isComplete: false,
            });

            try {
              await $scope.model.displayModel.onView();

              let examId = $state.params.identifier;
              CnSession.setBreadcrumbTrail([
                { title: "Exam", go: async function () { await $state.go("exam.list"); } },
                {
                  title: examId,
                  go: async function () { await $state.go("exam.view", { identifier: examId }) },
                },
                { title: "display", },
              ]);
            } finally {
              $scope.isComplete = true;

              // emit that the directive is complete
              $scope.$emit("cnExamDisplay complete", $scope);
            }
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnExamDisplayFactory", [
      "CnImageDisplayFactory",
      "CnHttpFactory",
      function (CnImageDisplayFactory, CnHttpFactory) {
        var object = function (parentModel) {
          angular.extend(this, {
            parentModel: parentModel,
            isLoading: false,
            imageDisplayModel: CnImageDisplayFactory.instance(),

            onView: async function () {
              try {
                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + '/image',
                }).query();

                this.imageDisplayModel.imageList = response.data.map((record, index) => ({
                  index: index,
                  imageId: record.id,
                }));
                await this.imageDisplayModel.onView();
              } finally {
                this.isLoading = false;
              }
            },
          });

        };

        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnExamModelFactory", [
      "CnBaseModelFactory",
      "CnExamDisplayFactory",
      "CnExamListFactory",
      "CnExamViewFactory",
      function (
        CnBaseModelFactory,
        CnExamDisplayFactory,
        CnExamListFactory,
        CnExamViewFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            displayModel: CnExamDisplayFactory.instance(this),
            listModel: CnExamListFactory.instance(this),
            viewModel: CnExamViewFactory.instance(this, root),
          });
        };

        return {
          root: new object(true),
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);

  },
});
