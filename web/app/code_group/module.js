cenozoApp.defineModule({
  name: "code_group",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: { column: "name" },
      name: {
        singular: "code group",
        plural: "code groups",
        possessive: "code group's",
      },
      columnList: {
        name: {
          column: "code_group.name",
          title: "Name",
        }
      },
      defaultOrder: {
        column: "code_group.name",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      name: {
        title: "Name",
        type: "string",
        format: "identifier",
      },
    });
  },
});
