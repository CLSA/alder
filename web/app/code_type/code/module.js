cenozoApp.defineModule({
  name: "xxx_xxx",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "xxx xxx",
        plural: "xxx xxxs",
        possessive: "xxx xxx's",
      },
      columnList: {
      },
      defaultOrder: {
        column: "xxx_xxx.",
        reverse: false,
      },
    });

    module.addInputGroup("", {
    });
  },
});
