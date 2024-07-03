cenozoApp.defineModule({
  name: "scan_type",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "scan type",
        plural: "scan types",
        possessive: "scan type's",
      },
      columnList: {
      },
      defaultOrder: {
        column: "scan_type.",
        reverse: false,
      },
    });

    module.addInputGroup("", {
    });
  },
});