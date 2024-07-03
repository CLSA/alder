cenozoApp.defineModule({
  name: "code_type",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "code type",
        plural: "code types",
        possessive: "code type's",
      },
      columnList: {
      },
      defaultOrder: {
        column: "code_type.",
        reverse: false,
      },
    });

    module.addInputGroup("", {
    });
  },
});