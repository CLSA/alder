cenozoApp.defineModule({
  name: "modality",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "modality",
        plural: "modalitys",
        possessive: "modality's",
      },
      columnList: {
      },
      defaultOrder: {
        column: "modality.",
        reverse: false,
      },
    });

    module.addInputGroup("", {
    });
  },
});
