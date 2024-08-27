cenozoApp.defineModule({
  name: "image",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "exam",
          column: "exam.id",
        },
      },
      name: {
        singular: "image",
        plural: "images",
        possessive: "image's",
      },
      columnList: {
        filename: {
          title: "Filename",
        },
      },
      defaultOrder: {
        column: "filename",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      filename: {
        title: "Filename",
        type: "string",
      },
      scan_type_id: {
        column: "exam.scan_type_id",
        type: "hidden",
      },
    });
  },
});
