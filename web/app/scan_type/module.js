cenozoApp.defineModule({
  name: "scan_type",
  models: ["list", "view"],
  defaultTab: "code_group",
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "modality",
          column: "modality.name",
        },
      },
      name: {
        singular: "scan type",
        plural: "scan types",
        possessive: "scan type's",
      },
      columnList: {
        modality: { column: "modality.name", title: "Modality" },
        name: { title: "Name" },
        exam_count: { title: "Exams" },
      },
      defaultOrder: {
        column: "scan_type.name",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      name: {
        title: "Name",
        type: "string",
      },
    });
  },
});
