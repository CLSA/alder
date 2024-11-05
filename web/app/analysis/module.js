cenozoApp.defineModule({
  name: "analysis",
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
      rating: {
        title: "Rating",
        type: "string",
        isConstant: true,
      },
      note: {
        title: "Analysis Notes",
        type: "text",
      },
    });
  },
});
