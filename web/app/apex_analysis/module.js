cenozoApp.defineModule({
  name: "apex_analysis",
  models: ["view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "exam",
          column: "exam.id",
        },
      },
      name: {
        singular: "analysis",
        plural: "analyses",
        possessive: "analysis'",
      },
    });

    module.addInputGroup("", {
      export_datetime: {
        title: "Export Date & Time",
        type: "datetime",
        isConstant: true,
      },
      pass: {
        title: "Pass",
        type: "boolean",
      },
      note: {
        title: "Analysis Notes",
        type: "text",
      },
    });
  },
});
