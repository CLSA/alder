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
            hostList: null,
            hostId: null,
            deletingFiles: false,
            downloadingFiles: false,

            deleteFiles: async function() {
              try {
                // confirm with the user first
                const hostName = this.hostList.findByProperty("value", this.hostId).name;
                const response = await CnModalConfirmFactory.instance({
                  title: "Delete all Images on " + hostName,
                  message:
                    "Are you sure you wish to delete all images on " + hostName + "?  " +
                    "This operation cannot be reversed and any existing images and analysis will be lost."
                }).show();

                if (response) {
                  this.deletingFiles = true;
                  await CnHttpFactory.instance({
                    path: "apex_host/" + this.hostId,
                    data: { delete: true },
                  }).patch();
                }
              } finally {
                this.deletingFiles = false;
              }
            },

            downloadFiles: async function() {
              if (null == this.analysisFile) return;

              this.downloadingFiles = true;
              try {
                let fileList = [this.analysisFile.filename];

                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.hostId,
                  data: { download: fileList },
                }).patch();

                message = response.data.reduce(
                  (str, item) => {
                    let filename = this.analysisFile.name;
                    let result = null == item.error ? "Data successfully retrieved." : item.error;
                    let highlight = (
                      null == item.error ? "text-success" :
                      result.match( /already exists/ ) ? "text-warning" :
                      "text-danger"
                    );
                    let glyph = null == item.error ? "glyphicon-ok" : "glyphicon-remove";
                    str += (
                      '<div class="container-fluid vertical-spacer">' +
                        '<div>' + filename + '</div>' +
                        '<div class="spacer ' + highlight + '">' +
                          result + (
                            result.match( /already exists/ ) ?  "" : ' <i class="glyphicon ' + glyph + '"></i>'
                          ) +
                        '</div>' +
                      '</div>'
                    );
                    return str;
                  },
                  ""
                );
                await CnModalMessageFactory.instance({
                  title: "Upload Results",
                  message: message,
                  html: true,
                  size: "lg",
                }).show();
              } finally {
                this.downloadingFiles = false;
              }
            },

            onView: async function() {
              this.isLoading = true;
              try {
                // start by getting a list of all apex hosts
                const response = await CnHttpFactory.instance({
                  path: "apex_host",
                  data: { select: { column: "name" }, modifier: { order: "name" } },
                }).query();

                this.hostList = response.data.reduce((list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                }, []);
                this.hostId = 0 < this.hostList.length ? this.hostList[0].value : null;

                await this.parentModel.viewModel.onView();
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
            hostList: null,
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
              const hostName = this.hostList.findByProperty("value", this.hostId).name;

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
                  image.name += " (reanalysed)";
                  this.baseImage = image;
                } else {
                  this.currentImage = image;
                }
              });
            },

            deleteImages: async function() {
              if (!this.hostId) return;
              const hostName = this.hostList.findByProperty("value", this.hostId).name;

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
              const hostName = this.hostList.findByProperty("value", this.hostId).name;

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
                  data: { upload: fileList },
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
              this.isLoading = true;
              try {
                this.currentImage = null;
                this.baseImage = null;

                // start by getting a list of all apex hosts
                const response = await CnHttpFactory.instance({
                  path: "apex_host",
                  data: { select: { column: "name" }, modifier: { order: "name" } },
                }).query();

                this.hostList = response.data.reduce((list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                }, []);
                this.hostId = 0 < this.hostList.length ? this.hostList[0].value : null;

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
