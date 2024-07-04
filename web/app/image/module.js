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
            /*
            loadingImage: true,
            image: null,
            */
            imageModel: CnImageFactory.instance(this.parentModel),
            onView: async function(force) {
              await this.$$onView(force);
              await this.imageModel.onView();

/*                
              this.loadingImage = true;
              try {
                function createHiPPICanvas(width, height) {
                  const ratio = window.devicePixelRatio;
                  const canvas = document.getElementById("canvas");
                  const canvasRect = canvas.getBoundingClientRect();
                  canvas.width = canvasRect.width * ratio;
                  canvas.height = canvasRect.height * ratio;
                  canvas.getContext("2d").scale(ratio, ratio);
                  return canvas;
                }

                function drawImage() {
                  const canvasRect = canvas.getBoundingClientRect();
                  canvas.width = canvasRect.width;
                  canvas.height = canvasRect.height;
                  const imageRatio = image.width/image.height;
                  const canvasRatio = canvas.width/canvas.height;
                  let width = 1 > imageRatio ? canvas.width / canvasRatio * imageRatio : canvas.width;
                  let height = 1 < imageRatio ? canvas.height * canvasRatio / imageRatio : canvas.height;
                  context.clearRect(0, 0, canvas.width, canvas.height);
                  context.drawImage(image, 0, 0, width, height);
                }

                await this.$$onView(force);

                const ratio = window.devicePixelRatio;
                const canvas = createHiPPICanvas(); // document.getElementById("canvas");
                const context = canvas.getContext("2d");
                context.font = "24px Arial";
                context.fillText("Loading image...", 20, 40);

                let image = new Image();
                image.onload = function() { drawImage(); }

                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath(),
                  data: { select: { column: "image" } }
                }).get();
                image.src = response.data.image.data;
                this.loadingImage = false;

                // TODO: this may not resize at the very last 50 ms.  Try figuring out how to improve it
                var resizeWait = false;
                window.addEventListener("resize", function() {
                  if (!resizeWait) {
                    drawImage();
                    resizeWait = true;
                    $timeout(() => (resizeWait = false), 50);
                  }
                });
              } finally {
                this.loadingImage = false;
              }
*/              
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
