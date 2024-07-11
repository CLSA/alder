cenozoApp.defineModule({
  name: "code_group",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "scan_type",
          column: "scan_type.name",
        },
      },
      name: {
        singular: "code group",
        plural: "code groups",
        possessive: "code group's",
      },
      columnList: {
        rank: {
          title: "Rank",
          type: "rank",
        },
        name: {
          title: "Name",
        },
        value: {
          title: "Value",
        }
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
      },
    });
  },
});
