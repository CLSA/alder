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
          title: "Scan Type",
        },
        interviewer: {
          title: "Interviewer",
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
    });
  },
});
