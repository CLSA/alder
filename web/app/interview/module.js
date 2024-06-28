cenozoApp.defineModule({
  name: "interview",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "interview",
        plural: "interviews",
        possessive: "interview's",
      },
      columnList: {
        uid: {
          column: "participant.uid",
          title: "Participant",
        },
        phase: {
          column: "study_phase.name",
          title: "Phase",
        },
        site: {
          column: "site.name",
          title: "Site",
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
      site: {
        column: "site.name",
        title: "Site",
        type: "string",
      },
    });
  },
});
