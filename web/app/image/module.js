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
    cenozo.providers.directive("cnImageView", [
      "CnImageModelFactory",
      "CnSession",
      function (CnImageModelFactory, CnSession) {
        return {
          templateUrl: module.getFileUrl("view.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: async function ($scope, $element) {
            if (angular.isUndefined($scope.model)) $scope.model = CnImageModelFactory.root;
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnImageViewFactory", [
      "CnBaseViewFactory",
      "CnHttpFactory",
      "$timeout",
      function (CnBaseViewFactory, CnHttpFactory, $timeout) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          // load the image as well as the record data
          angular.extend(this, {
            image: null,
            onView: async function(force) {
              await this.$$onView(force);

              const canvas = document.getElementById("canvas");
              const context = canvas.getContext("2d");
              let image = new Image();

              function drawImage() {
                const canvasRect = canvas.getBoundingClientRect();
                const rectRatio = canvasRect.width/canvasRect.height;
                const imageRatio = image.width/image.height;
                const canvasRatio = canvas.width/canvas.height;
                let width = 1 > imageRatio ? canvas.width / canvasRatio / rectRatio * imageRatio : canvas.width;
                let height = 1 < imageRatio ? canvas.height * canvasRatio * rectRatio / imageRatio : canvas.height;
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0, width, height);
              }

              image.onload = function() { drawImage(); }

              const response = await CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath(),
                data: { select: { column: "image" } }
              }).get();
              image.src = response.data.image.data;

              var resizeWait = false;
              window.addEventListener("resize", function() {
                if (!resizeWait) {
                  drawImage();
                  resizeWait = true;
                  $timeout(() => (resizeWait = false), 50);
                }
              });
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