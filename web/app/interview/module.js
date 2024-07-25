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
        token: {
          title: "Token/Barcode",
        },
        start_datetime: {
          column: "interview.start_datetime",
          title: "Start Date & Time",
          type: "datetime",
        },
        end_datetime: {
          column: "interview.end_datetime",
          title: "End Date & Time",
          type: "datetime",
        },
      },
      defaultOrder: {
        column: "uid",
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
      token: {
        title: "Token/Barcode",
        type: "string",
      },
      start_datetime: {
        column: "interview.start_datetime",
        title: "Start Date & Time",
        type: "datetime",
      },
      end_datetime: {
        column: "interview.end_datetime",
        title: "End Date & Time",
        type: "datetime",
      },
    });
  },
});
