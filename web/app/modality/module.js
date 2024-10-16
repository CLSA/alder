cenozoApp.defineModule({
  name: "modality",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "modality",
        plural: "modalities",
        possessive: "modality's",
      },
      columnList: {
        name: { title: "name" },
        scan_type_list: { title: "Scan Types" },
        user_list: { title: "Users" },
      },
      defaultOrder: {
        column: "modality.name",
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
    cenozo.providers.factory("CnModalityViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "scan_type");

          async function init(object) {
            await object.deferred.promise;

            // allow users to be added/removed
            if (angular.isDefined(object.userModel)) {
              object.userModel.getChooseEnabled = function () {
                return parentModel.isRole("administrator");
              };
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
