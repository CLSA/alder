cenozoApp.defineModule({
  name: "code",
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
        singular: "code",
        plural: "codes",
        possessive: "code's",
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
      apex: {
        column: "code_group.apex",
        type: "hidden",
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnCodeViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          this.getChildList = function() {
            let list = this.$$getChildList();

            // only show apex reviews for apex codes, and non-apex reviews for non-apex codes
            return list.filter(
              child => (
                [true, false].includes(this.record.apex) &&
                (this.record.apex ? "review" : "apex_review") != child.subject.snake
              )
            );
          };

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
