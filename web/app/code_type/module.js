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
        value: {
          title: "Value",
        },
        description: {
          title: "Description",
          align: "left",
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
      value: {
        title: "Value",
        type: "string",
        format: "integer",
      },
      description: {
        title: "Description",
        type: "text",
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnCodeTypeViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "review");

          async function init(object) {
            await object.deferred.promise;

            // do not allow reviews to be edited from this view
            if (angular.isDefined(object.reviewModel)) {
              object.reviewModel.getChooseEnabled = function () { return false; }
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
