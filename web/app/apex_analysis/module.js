cenozoApp.defineModule({
  name: "apex_analysis",
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
        singular: "analysis",
        plural: "analyses",
        possessive: "analysis'",
      },
      columnList: {
        uid: {
          column: "participant.uid",
          title: "Participant",
        },
        phase: {
          column: "study_phase.name",
          title: "Phase",
        },
        scan_type: {
          title: "Type",
        },
        upload_status: {
          title: "Upload Status",
        },
        apex_review_id: {
          isIncluded: ($state, model) => false,
        },
      },
      defaultOrder: {
        column: "participant.uid",
        reverse: false,
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

    module.addExtraOperation("list", {
      title: "Re-Schedule Failed Uploads",
      operation: async function ($state, model) {
        await model.listModel.reUploadImages($state.params.identifier);
      },
      isIncluded: function ($state, model) {
        return "apex_host" == model.getSubjectFromState();
      },
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
    cenozo.providers.factory("CnApexAnalysisListFactory", [
      "CnBaseListFactory",
      "CnHttpFactory",
      "$state",
      function (CnBaseListFactory, CnHttpFactory, $state) {
        var object = function (parentModel) {
          CnBaseListFactory.construct(this, parentModel);

          angular.extend(this, {
            onSelect: async function (record) {
              await $state.go("apex_review.view", { identifier: record.apex_review_id });
            },
            reUploadImages: async function (apexHostId) {
              await CnHttpFactory.instance({
                path: "apex_host/" + apexHostId + "?action=reupload_images",
              }).patch();
              await this.onList(true);
            },
          });
        };
        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexAnalysisUploadFactory", [
      "CnApexAnalysisModelFactory",
      "CnHttpFactory",
      "CnModalApexHostStatusFactory",
      function (CnApexAnalysisModelFactory, CnHttpFactory, CnModalApexHostStatusFactory) {
        var object = function () {
          angular.extend(this, {
            parentModel: CnApexAnalysisModelFactory.instance(),
            isLoading: true,
            currentImage: null,
            baseImage: null,
            imageStatus: null,
            uploadingImages: false,

            checkApexHostStatus: async function() {
              await CnModalApexHostStatusFactory.instance(this.parentModel.viewModel.record.apex_host_id).show();
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
