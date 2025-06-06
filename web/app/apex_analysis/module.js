cenozoApp.defineModule({
  name: "apex_analysis",
  models: ["view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "exam",
          column: "exam.id",
        },
      },
      name: {
        singular: "analysis",
        plural: "analyses",
        possessive: "analysis'",
      },
    });

    module.addInputGroup("", {
      upload_datetime: {
        title: "Uploaded On",
        type: "datetime",
        isConstant: true,
      },
      download_datetime: {
        title: "Downloaded On",
        type: "datetime",
        isConstant: true,
      },
      data: {
        title: "Data",
        type: "hidden",
      },
      pass: {
        title: "Pass",
        type: "boolean",
      },
      note: {
        title: "Analysis Notes",
        type: "text",
      },
      apex_host_id: { column: "apex_host.id", type: "hidden" },
      apex_review_id: { type: "hidden" },
      uid: { column: "participant.uid", type: "hidden" },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnApexAnalysisUpload", [
      "CnApexAnalysisUploadFactory",
      "CnSession",
      "$state",
      function (CnApexAnalysisUploadFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("upload.tpl.html"),
          restrict: "E",
          controller: async function ($scope, $element) {
            $scope.model = CnApexAnalysisUploadFactory.instance();
            $scope.viewParent = async function () {
              await $state.go(
                "apex_review.view",
                { identifier: $scope.model.parentModel.viewModel.record.apex_review_id }
              );
            };
            await $scope.model.onView();

            CnSession.setBreadcrumbTrail([
              { title: "Apex Review" },
              {
                title: $scope.model.parentModel.viewModel.record.apex_review_id,
                go: async function () {
                  $state.go(
                    "apex_review.view",
                    { identifier: $scope.model.parentModel.viewModel.record.apex_review_id }
                  );
                },
              },
              { title: "Upload", }
            ]);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexAnalysisUploadFactory", [
      "CnApexAnalysisModelFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (CnApexAnalysisModelFactory, CnSession, CnHttpFactory, CnModalMessageFactory) {
        var object = function () {

          angular.extend(this, {
            parentModel: CnApexAnalysisModelFactory.instance(),
            isLoading: true,
            currentImage: null,
            baseImage: null,
            imageStatus: null,
            uploadingImages: false,

            checkApexHostStatus: async function() {
              if (!this.parentModel.viewModel.record.apex_host_id) return;

              try {
                const modal = CnModalMessageFactory.instance({
                  title: "Checking Apex Status",
                  message: "Please wait...",
                  html: true,
                  block: true,
                });

                modal.show();

                // get the host's status
                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.parentModel.viewModel.record.apex_host_id,
                  data: { select: { column: 'status' } },
                  onError: (error) => {
                    modal.close();
                    modal.message = "Unable to connect to Apex workstation.";
                    modal.block = false;
                    modal.show();
                  },
                }).get();

                const status = JSON.parse(response.data.status);
                modal.close();
                modal.message = "<h4>Results:</h4><ul>";
                for (let key in status) {
                  let value = status[key];
                  modal.message += `
                    <li>
                      ${key}: 
                      <span ng-class="text-${value ? 'success' : 'danger'}">
                        ${null == value ? "failed" : value ? "online" : "offline"}
                        <i class="glyphicon" ng-class="glyphicon-${value ? 'ok' : 'remove'}"></i>
                      </span>
                    </li>
                  `;
                }
                modal.message += "</ul>";

                if (!status['DICOM In'] || !status['DICOM Apex'] || !status['QDR']) {
                  modal.message += `
                    <div
                      class="input-group text-danger"
                      ng-if="!${status['DICOM In']} || !${status['DICOM Apex']} || !${status['QDR']}"
                    >
                      <h4>Please Note:</h4>
                      <div class="container-fluid">
                        Before images can be sent to the selected Apex workstation,
                        DICOM In, DICOM Apex and QDR must all be online.<br />
                        Please check the Apex workstation and try again once all software is running.
                      </div>
                    </div>
                  `;
                }

                modal.block = false;
                modal.show();

              } finally {
              }
            },

            checkImageStatus: async function() {
              if (!this.parentModel.viewModel.record.apex_host_id) return;

              // get the image(s) associated with this analysis
              const response = await CnHttpFactory.instance({
                path: ["apex_analysis", this.parentModel.viewModel.record.id, "image"].join("/"),
              }).query();

              response.data.forEach((image) => {
                // determine the UI-friendly image name
                image.name = (
                  "Phase " + image.phase.rank + " (" + image.phase.name + "): " +
                  (null == image.side ? "" : image.side + " ") + image.type
                );
                if (null != image.number) image.name = image.name + " #" + image.number;

                // set the UI-friendly image status
                image.status = (image.uploaded ? "Successfully" : "Not") + " uploaded to Apex";

                if (image.reanalysed) {
                  image.name += " (re-analysed)";
                  this.baseImage = image;
                } else {
                  this.currentImage = image;
                }
              });
            },

            uploadImages: async function() {
              if (!this.parentModel.viewModel.record.apex_host_id) return;

              this.uploadingImages = true;
              try {
                let fileList = [];
                if (null != this.currentImage) {
                  this.currentImage.uploaded = null;
                  this.currentImage.status = "Uploading image to Apex workstation ...";
                  fileList.push(this.currentImage.filename);
                }
                if (null != this.baseImage) {
                  this.baseImage.uploaded = null;
                  this.baseImage.status = "Uploading image to Apex workstation ...";
                  fileList.push(this.baseImage.filename);
                }

                if (0 == fileList.length) return;

                const response = await CnHttpFactory.instance({
                  path: "apex_analysis/" + this.parentModel.viewModel.record.id + "?action=upload",
                }).patch();
                await this.checkImageStatus();

                if (angular.isString(response)) {
                  angular.extend(this.baseImage, { uploaded: false, error: response, status: response });
                  angular.extend(this.currentImage, { uploaded: false, error: response, status: response });
                } else if (angular.isArray(response)) {
                  response.data.forEach(image => {
                    const workingImage = (
                      null != this.baseImage && this.baseImage.filename == image.file ?
                      this.baseImage :
                      this.currentImage
                    );
                    angular.extend(workingImage, {
                      uploaded: null == image.error,
                      error: image.error,
                      status: null == image.error ? "Successfully uploaded to Apex workstation" : image.error,
                    });
                  });
                }
              } finally {
                this.uploadingImages = false;
              }
            },

            onView: async function() {
              angular.extend(this, { isLoading: true, currentImage: null, baseImage: null });
              try {
                await this.parentModel.viewModel.onView();
                await this.checkImageStatus();
              } finally {
                this.isLoading = false;
              }
            },
          });
        };

        return {
          instance: function () {
            return new object();
          },
        };
      },
    ]);

  },
});
