cenozoApp.defineModule({
  name: "code",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "code",
        plural: "codes",
        possessive: "code's",
      },
      columnList: {
        image: {
        },
        user: {
        },
        code_type: {
        },
      },
      defaultOrder: {
        column: "code.",
        reverse: false,
      },
    });

    module.addInputGroup("", {
    });
  },
});