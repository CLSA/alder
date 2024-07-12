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
      path: {
        column: "image.path",
        type: "hidden",
      },
      filename: {
        title: "Filename",
        type: "string",
      },
      note: {
        column: "image.note",
        title: "Note",
        type: "text",
      },
      feedback: {
        column: "image.feedback",
        title: "Feedback",
        type: "text",
      },
      rating: {
        column: "rating.value",
        type: "hidden",
      },
      scan_type_id: {
        column: "exam.scan_type_id",
        type: "hidden",
      },
    });
  },
});
