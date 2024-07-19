cenozoApp.defineModule({
  name: "exam",
  models: ["list", "view"],
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
          column: "scan_type.name",
          title: "Scan Type",
        },
        side: {
          column: "exam.side",
          title: "Side",
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
      },
      phase: {
        column: "study_phase.name",
        title: "Phase",
        type: "string",
      },
      modality: {
        column: "modality.name",
        title: "Modality",
        type: "string",
      },
      scan_type: {
        column: "scan_type.name",
        title: "Scan Type",
        type: "string",
      },
      side: {
        column: "exam.side",
        title: "Side",
        type: "string",
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnExamViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);
          console.log(this.getChildList());
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

  },
});
