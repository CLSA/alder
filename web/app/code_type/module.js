cenozoApp.defineModule({
  name: "code_type",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "code_group",
          column: "code_group.id",
        },
      },
      name: {
        singular: "code type",
        plural: "code types",
        possessive: "code type's",
      },
      columnList: {
        rank: {
          title: "Rank",
          type: "rank",
        },
        name: {
          title: "Name",
        },
      },
      defaultOrder: {
        column: "rank",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      rank: {
        title: "Rank",
        type: "rank",
      },
      name: {
        title: "Name",
        type: "string",
        format: "identifier",
      },
    });
  },
});
