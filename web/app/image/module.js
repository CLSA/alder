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
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnImageViewFactory", [
      "CnBaseViewFactory",
      "CnImageFactory",
      "CnHttpFactory",
      "$timeout",
      function (CnBaseViewFactory, CnImageFactory, CnHttpFactory, $timeout) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          // load the image as well as the record data
          angular.extend(this, {
            imageModel: CnImageFactory.instance(this.parentModel),
            onView: async function(force) {
              await this.$$onView(force);
              await this.imageModel.onView();
            },
          });
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
