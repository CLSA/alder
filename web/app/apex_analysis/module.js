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
      download_datetime: {
        title: "Download",
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
      apex_review_id: { type: "hidden" },
      uid: { column: "participant.uid", type: "hidden" },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnApexAnalysisDownload", [
      "CnApexAnalysisDownloadFactory",
      "CnSession",
      "$state",
      function (CnApexAnalysisDownloadFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("download.tpl.html"),
          restrict: "E",
          controller: async function ($scope, $element) {
            $scope.model = CnApexAnalysisDownloadFactory.instance();
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
              { title: "Download", }
            ]);
          },
        };
      },
    ]);

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
    cenozo.providers.factory("CnApexAnalysisDownloadFactory", [
      "CnApexAnalysisModelFactory",
      "CnHttpFactory",
      "CnModalMessageFactory",
      "CnModalConfirmFactory",
      function (CnApexAnalysisModelFactory, CnHttpFactory, CnModalMessageFactory, CnModalConfirmFactory) {
        var object = function () {

          angular.extend(this, {
            parentModel: CnApexAnalysisModelFactory.instance(),
            isLoading: true,
            hostId: null,
            analysisData: [],
            deletingImages: false,
            downloadingImage: false,
            downloadResult: null,

            deleteImages: async function() {
              if (!this.hostId) return;
              const hostName = this.parentModel.hostList.findByProperty("value", this.hostId).name;

              try {
                // confirm with the user first
                const response = await CnModalConfirmFactory.instance({
                  title: "Delete all Images on " + hostName,
                  message:
                    "Are you sure you wish to delete all images on " + hostName + "?  " +
                    "This operation cannot be reversed and any existing images and analysis will be lost."
                }).show();

                if (response) {
                  this.deletingImages = true;
                  await CnHttpFactory.instance({
                    path: "apex_host/" + this.hostId,
                    data: { delete: true },
                  }).patch();
                }
              } finally {
                this.deletingImages = false;
              }
            },

            downloadImage: async function() {
              if (!this.hostId) return;
              const hostName = this.parentModel.hostList.findByProperty("value", this.hostId).name;

              angular.extend(this, { downloadResult: null, downloadingImage: true });
              try {
                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.hostId,
                  data: { download: this.parentModel.viewModel.record.id },
                }).patch();

                // update the view record in case the download date has changed
                this.downloadResult = response.data;
                await this.onView();
              } finally {
                this.downloadingImage = false;
              }
            },

            onView: async function() {
              angular.extend(this, { isLoading: true, hostId: null });
              try {
                await this.parentModel.viewModel.onView();
                this.hostId = 0 < this.parentModel.hostList.length ? this.parentModel.hostList[0].value : null;
                this.analysisData = JSON.parse(this.parentModel.viewModel.record.data);
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

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexAnalysisUploadFactory", [
      "CnApexAnalysisModelFactory",
      "CnHttpFactory",
      "CnModalConfirmFactory",
      function (CnApexAnalysisModelFactory, CnHttpFactory, CnModalConfirmFactory) {
        var object = function () {

          angular.extend(this, {
            parentModel: CnApexAnalysisModelFactory.instance(),
            isLoading: true,
            currentImage: null,
            baseImage: null,
            hostId: null,
            checkingHostStatus: false,
            hostStatus: null,
            imageStatus: null,
            deletingImages: false,
            uploadingImages: false,

            checkHostStatus: async function() {
              angular.extend(this, {
                checkingHostStatus: true,
                hostStatus: null,
              });

              try {
                // get the host's status
                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.hostId,
                  data: { select: { column: 'status' } },
                }).get();

                this.hostStatus = JSON.parse(response.data.status);
              } finally {
                this.checkingHostStatus = false;
              }
            },

            checkImageStatus: async function() {
              if (!this.hostId) return;
              const hostName = this.parentModel.hostList.findByProperty("value", this.hostId).name;

              // get the image(s) associated with this analysis
              const response = await CnHttpFactory.instance({
                path: (
                  "apex_analysis/" +
                  this.parentModel.viewModel.record.id +
                  "/image?apex_host_id=" +
                  this.hostId
                ),
              }).query();

              response.data.forEach((image) => {
                // determine the UI-friendly image name
                image.name = (
                  "Phase " + image.phase.rank + " (" + image.phase.name + "): " +
                  (null == image.side ? "" : image.side + " ") + image.type
                );
                if (null != image.number) image.name = image.name + " #" + image.number;

                // set the UI-friendly image status
                image.status = (image.uploaded ? "Successfully uploded to " : "Not uploaded to ") + hostName;

                if (image.reanalysed) {
                  image.name += " (re-analysed)";
                  this.baseImage = image;
                } else {
                  this.currentImage = image;
                }
              });
            },

            deleteImages: async function() {
              if (!this.hostId) return;
              const hostName = this.parentModel.hostList.findByProperty("value", this.hostId).name;

              try {
                // confirm with the user first
                const response = await CnModalConfirmFactory.instance({
                  title: "Delete all Images on " + hostName,
                  message:
                    "Are you sure you wish to delete all images on " + hostName + "?  " +
                    "This operation cannot be reversed and any existing images and analysis will be lost."
                }).show();

                if (response) {
                  this.deletingImages = true;
                  await CnHttpFactory.instance({
                    path: "apex_host/" + this.hostId,
                    data: { delete: true },
                  }).patch();
                  await this.checkImageStatus();
                }
              } finally {
                this.deletingImages = false;
              }
            },

            uploadImages: async function() {
              if (!this.hostId) return;
              const hostName = this.parentModel.hostList.findByProperty("value", this.hostId).name;

              this.uploadingImages = true;
              try {
                let fileList = [];
                if (null != this.currentImage) {
                  this.currentImage.uploaded = null;
                  this.currentImage.status = "Uploading image to " + hostName + " ...";
                  fileList.push(this.currentImage.filename);
                }
                if (null != this.baseImage) {
                  this.baseImage.uploaded = null;
                  this.baseImage.status = "Uploading image to " + hostName + " ...";
                  fileList.push(this.baseImage.filename);
                }

                if (0 == fileList.length) return;

                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.hostId,
                  data: { upload: this.parentModel.viewModel.record.id },
                }).patch();
                await this.checkImageStatus();

                response.data.forEach(image => {
                  const workingImage = (
                    null != this.baseImage && this.baseImage.filename == image.file ?
                    this.baseImage :
                    this.currentImage
                  );
                  angular.extend(workingImage, {
                    uploaded: null == image.error,
                    error: image.error,
                    status: null == image.error ? ("Successfully uploded to " + hostName) : image.error,
                  });
                });
              } finally {
                this.uploadingImages = false;
              }
            },

            onView: async function() {
              angular.extend(this, { isLoading: true, hostId: null, currentImage: null, baseImage: null });
              try {
                await this.parentModel.viewModel.onView();
                this.hostId = 0 < this.parentModel.hostList.length ? this.parentModel.hostList[0].value : null;
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

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexAnalysisModelFactory", [
      "CnBaseModelFactory",
      "CnApexAnalysisViewFactory",
      "CnHttpFactory",
      function (CnBaseModelFactory, CnApexAnalysisViewFactory, CnHttpFactory) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            viewModel: CnApexAnalysisViewFactory.instance(this, root),
            hostList: null,

            getMetadata: async function() {
              const [metadataResponse, hostResponse] = await Promise.all([
                this.$$getMetadata(),

                CnHttpFactory.instance({
                  path: "apex_host",
                  data: { select: { column: "name" }, modifier: { order: "name" } },
                }).query(),
              ]);

              this.hostList = hostResponse.data.reduce((list, item) => {
                list.push({ value: item.id, name: item.name });
                return list;
              }, []);
            },
          });
        };

        return {
          root: new object(true),
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);

  },
});
