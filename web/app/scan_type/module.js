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
        side: { title: "Side" },
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

    /* ############################################################################################## */
    cenozo.providers.factory("CnScanTypeViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "code_group");

          async function init(object) {
            await object.deferred.promise;

            // do not allow code groups to be edited from this view
            if (angular.isDefined(object.codeGroupModel)) {
              object.codeGroupModel.getChooseEnabled = function () { return false; }
            }
          }

          init(this);
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);
  },
});
