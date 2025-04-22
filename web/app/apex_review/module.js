cenozoApp.defineModule({
  name: "apex_review",
  dependencies: "apex_analysis",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "exam",
          column: "exam.id",
        },
      },
      name: {
        singular: "apex review",
        plural: "apex reviews",
        possessive: "apex review's",
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
        site: {
          column: "site.name",
          title: "Site",
        },
        interviewer: {
          column: "exam.interviewer",
          title: "Interviewer",
        },
        scan_type: {
          title: "Type",
        },
        user: {
          column: "user.name",
          title: "Reviewer",
          isIncluded: function ($state, model) { return !model.isRole("typist"); },
        },
        start_datetime: {
          column: "apex_review.start_datetime",
          title: "Start Date & Time",
          type: "datetime",
        },
        end_datetime: {
          column: "apex_review.end_datetime",
          title: "End Date & Time",
          type: "datetime",
        },
      },
      defaultOrder: {
        column: "user.name",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      uid: {
        column: "participant.uid",
        title: "Participant",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
      },
      phase: {
        column: "study_phase.name",
        title: "Phase",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
      },
      site: {
        column: "site.name",
        title: "Site",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
      },
      interviewer: {
        column: "exam.interviewer",
        title: "Interviewer",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
      },
      scan_type: {
        title: "Type",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
      },
      user_id: {
        title: "Reviewer",
        type: "lookup-typeahead",
        typeahead: {
          table: "user",
          select: 'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
          where: ["user.first_name", "user.last_name", "user.name"],
        },
        isConstant: "view",
        isExcluded: function ($state, model) {
          return model.isRole("typist");
        },
      },
      start_datetime: {
        title: "Start Date & Time",
        type: "datetime",
        isConstant: function ($state, model) { return !model.isRole("administrator"); },
        isExcluded: function($state, model) { return "add"; },
      },
      end_datetime: {
        title: "End Date & Time",
        type: "datetime",
        isConstant: function ($state, model) { return !model.isRole("administrator"); },
        isExcluded: function($state, model) { return "add"; },
      },
      note: {
        column: "exam.note",
        title: "Exam Notes",
        type: "text",
        isExcluded: function($state, model) { return "add"; },
      },
      prev_interview_review_id: { type: "hidden" },
      prev_exam_review_id: { type: "hidden" },
      next_exam_review_id: { type: "hidden" },
      next_interview_review_id: { type: "hidden" },
    });

    if (angular.isDefined(module.actions.multiedit)) {
      module.addExtraOperation("list", {
        title: "Review Multi-Edit",
        operation: async function ($state, model) {
          await $state.go("apex_review.multiedit");
        },
        isIncluded: function ($state, model) {
          // only show when viewing the base apex_review list
          return "apex_review" == model.getSubjectFromState();
        },
      });
    }

    if (angular.isDefined(cenozoApp.moduleList.apex_analysis.actions.upload)) {
      module.addExtraOperation("view", {
        title: "Upload",
        operation: async function ($state, model) {
          await $state.go(
            "apex_analysis.upload",
            { identifier: model.viewModel.currentAnalysis.analysisId }
          );
        },
        isIncluded: function ($state, model) {
          return model.isRole("administrator", "typist") && null == model.viewModel.record.end_datetime;
        },
        isDisabled: function ($state, model) {
          return null == model.viewModel.currentAnalysis;
        },
        help: "Upload images to an Apex workstation for re-analysis.",
      });
    }

    if (angular.isDefined(cenozoApp.moduleList.apex_analysis.actions.download)) {
      module.addExtraOperation("view", {
        title: "Download",
        operation: async function ($state, model) {
          await $state.go(
            "apex_analysis.download",
            { identifier: model.viewModel.currentAnalysis.analysisId }
          );
        },
        isIncluded: function ($state, model) {
          return model.isRole("administrator", "typist") && null == model.viewModel.record.end_datetime;
        },
        isDisabled: function ($state, model) {
          return null == model.viewModel.currentAnalysis;
        },
        help: "Download re-analysed images and data from an Apex workstation.",
      });
    }

    module.addExtraOperation("view", {
      title: "Close",
      operation: async function ($state, model) {
        model.viewModel.setState("complete");
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist") && null == model.viewModel.record.end_datetime;
      },
      help: "Mark the review as completed."
    });

    module.addExtraOperation("view", {
      title: "Re-Open",
      operation: async function ($state, model) {
        model.viewModel.setState("reopen");
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist") && null != model.viewModel.record.end_datetime;
      },
      help: "Re-open the review."
    });

    module.addExtraOperation("view", {
      title: "<i class='glyphicon glyphicon-fast-backward'></i> Prev Interview",
      operation: async function ($state, model) {
        await $state.go("apex_review.view", { identifier: model.viewModel.record.prev_interview_review_id });
      },
      isDisabled: function ($state, model) {
        return null == model.viewModel.record.prev_interview_review_id;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist", "administrator");
      },
    });

    module.addExtraOperation("view", {
      title: "<i class='glyphicon glyphicon-backward'></i> Prev Exam",
      operation: async function ($state, model) {
        await $state.go("apex_review.view", { identifier: model.viewModel.record.prev_exam_review_id });
      },
      isDisabled: function ($state, model) {
        return null == model.viewModel.record.prev_exam_review_id;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist", "administrator");
      },
    });

    module.addExtraOperation("view", {
      title: "<i class='glyphicon glyphicon-forward'></i> Next Exam",
      operation: async function ($state, model) {
        await $state.go("apex_review.view", { identifier: model.viewModel.record.next_exam_review_id });
      },
      isDisabled: function ($state, model) {
        return null == model.viewModel.record.next_exam_review_id;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist", "administrator");
      },
    });

    module.addExtraOperation("view", {
      title: "<i class='glyphicon glyphicon-fast-forward'></i> Next Interview",
      operation: async function ($state, model) {
        await $state.go("apex_review.view", { identifier: model.viewModel.record.next_interview_review_id });
      },
      isDisabled: function ($state, model) {
        return null == model.viewModel.record.next_interview_review_id;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist", "administrator");
      },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnApexReviewMultiedit", [
      "CnApexReviewMultieditFactory",
      "CnSession",
      "$state",
      function (CnApexReviewMultieditFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("multiedit.tpl.html"),
          restrict: "E",
          controller: function ($scope) {
            $scope.model = CnApexReviewMultieditFactory.instance();
            $scope.tab = "apex_review";
            CnSession.setBreadcrumbTrail([
              { title: "Apex Reviews", go: async function () { await $state.go("apex_review.list"); } },
              { title: "Multi-Edit", }
            ]);

            // trigger the elastic directive when confirming the review selection
            $scope.confirm = async function () {
              await $scope.model.confirm();
              angular.element("#uidListString").trigger("elastic");
            };
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexReviewMultieditFactory", [
      "CnApexReviewModelFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalDatetimeFactory",
      "CnModalMessageFactory",
      function (CnApexReviewModelFactory, CnSession, CnHttpFactory, CnModalDatetimeFactory, CnModalMessageFactory) {
        var object = function () {
          angular.extend(this, {
            parentModel: CnApexReviewModelFactory.root,
            module: module,
            confirmInProgress: false,
            bulkData: {
              canProceed: false,
              startDate: undefined,
              endDate: undefined,
              examsPer: null,
              examDataList: null,
            },
            uidData: {
              canProceed: false,
              uidListString: "",
              examDataList: null,
              withTotal: 0,
              withoutTotal: 0,
            },
            studyPhaseList: [],
            studyPhaseId: null,
            scanTypeList: [],
            scanTypeId: null,
            selectionTypeList: [
              { name: "Bulk", value: "bulk" },
              { name: "UID", value: "uid" },
            ],
            selectionType: "bulk",
            formattedStartDate: null,
            formattedEndDate: null,
            userList: null,
            userId: null,
            completedList: [
              { name: "(leave unchanged)", value: null },
              { name: "Yes", value: 1 },
              { name: "No", value: 0 },
            ],
            completed: null,

            selectDate: async function (type) {
              const response = await CnModalDatetimeFactory.instance({
                title: "start" == type ? "Start Date" : "End Date",
                date: "start" == type ? this.bulkData.startDate : this.bulkData.endDate,
                minDate: "end" == type ? this.bulkData.startDate : null,
                maxDate: "start" == type ? this.bulkData.endDate : null,
                pickerType: "date",
                emptyAllowed: true,
              }).show();

              if (false !== response) {
                if ("start" == type) {
                  this.bulkData.startDate = response;
                  this.formattedStartDate = CnSession.formatValue(response, "date", true);
                } else {
                  this.bulkData.endDate = response;
                  this.formattedEndDate = CnSession.formatValue(response, "date", true);
                }
                await this.updateForm("bulk");
              }
            },

            sanitizeExamsPer: function () {
              this.bulkData.examsPer =
                Number(this.bulkData.examsPer.replace(/[^0-9]/g, ""));
            },

            updateForm: async function (type) {
              if ((
                "bulk" == this.selectionType &&
                angular.isDefined(this.bulkData.startDate) &&
                angular.isDefined(this.bulkData.endDate)
              ) || (
                "uid" == this.selectionType &&
                angular.isDefined(this.uidData.uidListString) &&
                0 < this.uidData.uidListString.length
              )) {
                await this.confirm();
              }
            },

            confirm: async function () {
              this.confirmInProgress = true;
              if ("bulk" == this.selectionType) this.bulkData.canProceed = false;
              else if ("uid" == this.selectionType) this.uidData.canProceed = false;

              let data = {};
              if (this.studyPhaseId) data.study_phase_id = this.studyPhaseId;
              if (this.scanTypeId) data.scan_type_id = this.scanTypeId;

              try {
                // make sure the user list has been downloaded
                if (null == this.userList) {
                  var response = await CnHttpFactory.instance({
                    path: "user",
                    data: {
                      select: {
                        distinct: true,
                        column: ["id", "name", "first_name", "last_name"],
                      },
                      modifier: {
                        join: [
                          { table: "access", onleft: "user.id", onright: "access.user_id" },
                          { table: "role", onleft: "access.role_id", onright: "role.id" },
                        ],
                        where: [
                          { column: "user.active", operator: "=", value: true },
                          { column: "role.name", operator: "=", value: "typist" },
                        ],
                        order: "user.name",
                      },
                    },
                  }).query();

                  this.userList = response.data.reduce((list, item) => {
                    list.push({
                      value: item.id,
                      name: item.first_name + " " + item.last_name + " (" + item.name + ")",
                      user: item.name,
                    });
                    return list;
                  }, []);
                  this.userList.unshift({ name: "(empty)", value: null });
                }

                if ("bulk" == this.selectionType) {
                  angular.extend(data, {
                    start_date: null == this.bulkData.startDate ? null : this.bulkData.startDate.replace(/T.*/, ""),
                    end_date: null == this.bulkData.endDate ? null : this.bulkData.endDate.replace(/T.*/, ""),
                  });
                  var response = await CnHttpFactory.instance({ path: "apex_review", data: data }).post();
                  this.bulkData.examDataList = response.data;
                  this.bulkData.canProceed = 0 < Object.keys(this.bulkData.examDataList).length;
                } else {
                  var uidRegex = new RegExp(CnSession.application.uidRegex);

                  // clean up the uid list
                  var fixedList = this.uidData.uidListString
                    .toUpperCase() // convert to uppercase
                    .replace(/[\s,;|\/]/g, " ") // replace whitespace and separation chars with a space
                    .replace(/[^a-zA-Z0-9 ]/g, "") // remove anything that isn't a letter, number of space
                    .split(" ") // delimite string by spaces and create array from result
                    .filter((uid) => null != uid.match(uidRegex)) // match UIDs (eg: A123456)
                    .filter((uid, index, array) => index <= array.indexOf(uid)) // make array unique
                    .sort(); // sort the array

                  // now confirm UID list with server
                  if (0 == fixedList.length) {
                    angular.extend(this.uidData, {
                      uidListString: "",
                      examDataList: null,
                      withTotal: 0,
                      withoutTotal: 0,
                    });
                  } else {
                    data.uid_list = fixedList;
                    var response = await CnHttpFactory.instance({ path: "apex_review", data: data }).post();
                    angular.extend(this.uidData, {
                      uidListString: response.data.uid_list.join(" "),
                      examDataList: response.data.exam_list,
                    });
                    this.uidData.canProceed = 0 < this.uidData.uidListString.length;

                    // count with/without totals
                    angular.extend(this.uidData, { withTotal: 0, withoutTotal: 0 });
                    for(phase in this.uidData.examDataList) {
                      for(scanType in this.uidData.examDataList[phase]) {
                        this.uidData.withTotal += Number(this.uidData.examDataList[phase][scanType].with);
                        this.uidData.withoutTotal += Number(this.uidData.examDataList[phase][scanType].without);
                      }
                    }
                  }
                }
              } finally {
                this.confirmInProgress = false;
              }
            },

            proceed: async function (type) {
              if ("bulk" == this.selectionType) {
                let data = {
                  exams_per: this.bulkData.examsPer,
                  start_date: null == this.bulkData.startDate ? null : this.bulkData.startDate.replace(/T.*/, ""),
                  end_date: null == this.bulkData.endDate ? null : this.bulkData.endDate.replace(/T.*/, ""),
                  user_id: this.userId,
                  process: true,
                };

                if (angular.isDefined(this.studyPhaseId)) data.study_phase_id = this.studyPhaseId;
                if (angular.isDefined(this.scanTypeId)) data.scan_type_id = this.scanTypeId;

                const response = await CnHttpFactory.instance({
                  path: "apex_review",
                  data: data,
                  onError: CnModalMessageFactory.httpError,
                }).post();

                await CnModalMessageFactory.instance({
                  title: "Review(s) Processed",
                  message: "A total of " + response.data + " new reviews have been assigned.",
                }).show();

                this.userId = null;
              } else if ("uid" == this.selectionType) {
                let uidList = this.uidData.uidListString.split(" ");

                // test the formats of all columns
                let data = {
                  uid_list: uidList,
                  process: true,
                };

                if (angular.isDefined(this.studyPhaseId)) data.study_phase_id = this.studyPhaseId;
                if (angular.isDefined(this.scanTypeId)) data.scan_type_id = this.scanTypeId;
                if (0 < this.uidData.withoutTotal) {
                  if (angular.isDefined(this.userId)) data.user_id = this.userId;
                }
                if (0 < this.uidData.withTotal) {
                  if (angular.isDefined(this.completed)) data.completed = this.completed;
                }

                const response = await CnHttpFactory.instance({
                  path: "apex_review",
                  data: data,
                  onError: CnModalMessageFactory.httpError,
                }).post();

                let message =
                  "A total of " + uidList.length + " participant" +
                  (1 != uidList.length ? "s have " : " has ") + "been processed";

                if (0 < response.data.new || 0 < response.data.edit) {
                  message += " (";
                  if (0 < response.data.new) {
                    message += (
                      response.data.new + " new review" + (1 != response.data.new ? "s" : "") + " assigned"
                    );
                  }
                  if (0 < response.data.new && 0 < response.data.edit) message += " and ";
                  if (0 < response.data.edit) {
                    message += (
                      response.data.edit + " review" + (1 != response.data.edit ? "s" : "") + " edited"
                    );
                  }
                  message += ")"
                }
                message += ".";

                await CnModalMessageFactory.instance({ title: "Review(s) Processed", message: message }).show();

                angular.extend(this.uidData, {
                  canProceed: false,
                  uidListString: "",
                  examDataList: null,
                  withTotal: 0,
                  withoutTotal: 0,
                });
              }
            },
          });

          async function init(object) {
            const [studyPhaseResponse, scanTypeResponse] = await Promise.all([
              CnHttpFactory.instance({
                path: "study_phase",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: {
                    join: [
                      { table: "study", onleft: "study_phase.study_id", onright: "study.id" },
                    ],
                    where: [
                      { column: "study.name", operator: "=", value: "clsa" },
                    ],
                    order: "study_phase.rank",
                  },
                },
              }).query(),

              CnHttpFactory.instance({
                path: "scan_type",
                data: {
                  select: { column: ["id", "name", "side"] },
                  modifier: {
                    where: { column: "modality.name", operator: "=", value: "dxa" },
                    order: ["scan_type.name", "scan_type.side"],
                  },
                },
              }).query()
            ]);

            object.studyPhaseList = studyPhaseResponse.data.reduce((list, item) => {
              list.push({ value: item.id, name: item.name });
              return list;
            }, []);
            object.studyPhaseList.unshift({ name: "(all)", value: null });

            object.scanTypeList = scanTypeResponse.data.reduce((list, item) => {
              list.push({ value: item.id, name: "none" == item.side ? item.name : (item.side + " " + item.name) });
              return list;
            }, []);
            object.scanTypeList.unshift({ name: "(all)", value: null });
          }

          init(this);
        };

        return {
          instance: function () {
            return new object();
          },
        };
      },
    ]);

    cenozo.providers.factory("CnApexReviewViewFactory", [
      "CnBaseViewFactory",
      "CnApexAnalysisModelFactory",
      "CnImageDisplayFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (
        CnBaseViewFactory,
        CnApexAnalysisModelFactory,
        CnImageDisplayFactory,
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory
      ) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          // setup the analysis model
          let analysisModel = CnApexAnalysisModelFactory.instance();
          angular.extend(analysisModel, {
            getServiceResourcePath: resource =>
              "apex_analysis/" + (null == this.currentAnalysis ? 0 : this.currentAnalysis.analysisId),
            getEditEnabled: () => (
              this.parentModel.getEditEnabled() &&
              this.parentModel.isRole("typist") &&
              // make read-only for typists after marking as complete
              null == this.parentModel.viewModel.record.end_datetime
            ),
          });

          angular.extend(this, {
            isLoading: false,
            analysisModel: analysisModel,
            imageDisplayModel: CnImageDisplayFactory.instance(),
            analysisList: [],
            currentAnalysis: null,

            isTypist: function() {
              return this.parentModel.isRole("typist") && this.record.user_id == CnSession.user.id;
            },

            setState: async function(value) {
              try {
                this.changingState = true;
                await this.onPatch({ state: value });
                await this.onView(true);
              } catch (error) {
              } finally {
                this.changingState = false;
              }
            },

            selectAnalysis: async function(index) {
              const analysis = this.analysisList.findByProperty("index", index);
              if (null != analysis) {
                this.currentAnalysis = analysis;
                await this.analysisModel.viewModel.onView(true);
              }
            },

            onView: async function (force) {
              await this.$$onView(force);

              this.isLoading = true;

              try {
                // get a list of all analysis records
                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + "/apex_analysis",
                }).query();

                // do not include the analysisId since analysis is done in Apex, not locally in Alder
                this.analysisList = response.data.map((record, index) => ({
                  index: index,
                  analysisId: record.id,
                  annotations: false,
                  imageId: record.image_id,
                  codeGroupList: [],
                  selectionList: [],
                  pass: record.pass,
                  download_datetime: record.download_datetime,
                  note: record.note,
                }));

                // load the codes for all analyses
                await Promise.all(
                  this.analysisList.map(async (analysis) => {
                    const response = await CnHttpFactory.instance({
                      path: ["apex_analysis", analysis.analysisId, "code?full=1"].join("/"),
                    }).query();
                    analysis.codeGroupList = response.data;

                    // add a working property to all codes
                    analysis.codeGroupList.forEach(g => g.code_list.map(c => { c.working = false; return c }));
                  })
                );

                // load the selections for all analyses
                await Promise.all(
                  this.analysisList.map(async (analysis) => {
                    const response = await CnHttpFactory.instance({
                      path: ["apex_analysis", analysis.analysisId, "apex_analysis_selection?full=1"].join("/"),
                    }).query();
                    analysis.selectionList = response.data;

                    // add an empty option and working property to all selections
                    analysis.selectionList.forEach(
                      s => {
                        s.option_list.unshift({id: null, name: "(no change required)"});
                        s.working = false;
                      }
                    );
                  })
                );

                // now that the images are loaded view the image display
                angular.extend(this.imageDisplayModel, {
                  isTypist: this.isTypist(),
                  reviewUserId: this.record.user_id,
                  imageList: this.analysisList,
                  selectImage: async (index) => {
                    const image = this.imageDisplayModel.imageList.findByProperty("index", index);
                    if (null != image) {
                      await this.selectAnalysis(index);
                      this.imageDisplayModel.currentImage =
                        this.imageDisplayModel.imageList.findByProperty("index", index);
                      this.imageDisplayModel.loadImage();
                    }
                  }
                });
                await this.imageDisplayModel.onView();
              } finally {
                this.isLoading = false;
              }
            },

            getCodeDescription: function(code) {
              return (
                (code.description ? (code.description + " ") : "") +
                (0 == code.value ? "" : "(" + code.value + ")")
              );
            },

            selectionChanged: async function(selection) {
              selection.working = true;

              // see if the analysis_selection already exists
              let analysisSelectionId = null;
              try {
                const response = await CnHttpFactory.instance({
                  path:
                    "apex_analysis_selection/apex_analysis_id=" + this.currentAnalysis.analysisId +
                    ";selection_id=" + selection.id,
                  onError: function (error) {
                    if (404 == error.status) {
                      // a 404 just means there is no selection
                    } else {
                      CnModalMessageFactory.httpError(error);
                    }
                  },
                }).get();
                analysisSelectionId = response.data.id;
              } catch (error) {
                // errors are handled above in the onError function
              }

              try {
                // upsert the selection there is a selection_option_id, otherwise delete it
                if (selection.selection_option_id) {
                  if (null == analysisSelectionId) {
                    await CnHttpFactory.instance({
                      path: "apex_analysis_selection",
                      data: {
                        apex_analysis_id: this.currentAnalysis.analysisId,
                        selection_id: selection.id,
                        selection_option_id: selection.selection_option_id,
                      },
                    }).post();
                  } else {
                    await CnHttpFactory.instance({
                      path: "apex_analysis_selection/" + analysisSelectionId,
                      data: { selection_option_id: selection.selection_option_id }
                    }).patch();
                  }
                } else {
                  if (null != analysisSelectionId) {
                    await CnHttpFactory.instance({
                      path: "apex_analysis_selection/" + analysisSelectionId
                    }).delete();
                  }
                }
              } catch (error) {
                // errors are handled above in the onError functions
              } finally {
                selection.working = false;
              }
            },

            toggleCode: async function(code) {
              code.working = true;
              try {
                // remove the code if it is selected, add it if not
                let data = {};
                data[code.selected ? "remove" : "add"] = code.id;

                await CnHttpFactory.instance({
                  path: "apex_analysis/" + this.currentAnalysis.analysisId + "/code",
                  data: data
                }).post();

                code.selected = !code.selected;
              } catch (error) {
                // errors are handled above in the onError functions
              } finally {
                code.working = false;
              }
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

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexReviewAddFactory", [
      "CnBaseAddFactory",
      "CnHttpFactory",
      function (CnBaseAddFactory, CnHttpFactory) {
        var object = function (parentModel) {
          CnBaseAddFactory.construct(this, parentModel);
          this.onNew = async function (record) {
            this.heading =
              "Create " + parentModel.module.name.singular.ucWords() +
              " for Image " + this.parentModel.getParentIdentifier().identifier;
          };
        };
        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexReviewModelFactory", [
      "CnBaseModelFactory",
      "CnApexReviewAddFactory",
      "CnApexReviewListFactory",
      "CnApexReviewViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (
        CnBaseModelFactory,
        CnApexReviewAddFactory,
        CnApexReviewListFactory,
        CnApexReviewViewFactory,
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            addModel: CnApexReviewAddFactory.instance(this),
            listModel: CnApexReviewListFactory.instance(this),
            viewModel: CnApexReviewViewFactory.instance(this, root),

            getMetadata: async function() {
              await this.$$getMetadata();
              // load the analysis model's metadata now so it's ready when viewing analysis details
              await this.viewModel.analysisModel.getMetadata();
            },

            // override the service collection path so that roles can see their own reviews on the home screen
            getServiceCollectionPath: function () {
              // ignore the parent if it is root
              return this.$$getServiceCollectionPath("root" == this.getSubjectFromState());
            },

            // override the service data so that roles only see their own reviews from the home screen
            getServiceData: function (type, columnRestrictLists) {
              var data = this.$$getServiceData(type, columnRestrictLists);
              if ("root" == this.getSubjectFromState() && this.isRole("coordinator", "typist")) {
                if (angular.isUndefined(data.modifier.where)) data.modifier.where = [];

                if (this.isRole("typist")) {
                  data.modifier.where.push({
                    column: "apex_review.user_id",
                    operator: "=",
                    value: CnSession.user.id
                  });
                  data.modifier.where.push({
                    column: "apex_review.end_datetime",
                    operator: "=",
                    value: null
                  });
                }
              }
              return data;
            },

            // only include typists when selecting review users
            getTypeaheadData: function (input, viewValue) {
              let data = this.$$getTypeaheadData(input, viewValue);

              if ("user" == input.typeahead.table) {
                if (angular.isUndefined(data.modifier.join)) data.modifier.join = [];
                data.modifier.join.push({ table: "access", onleft: "user.id", onright: "access.user_id" });
                data.modifier.join.push({ table: "role", onleft: "access.role_id", onright: "role.id" });
                data.modifier.where.push({ column: "role.name", operator: "=", value: "typist" });
              }

              return data;
            },

            getAddEnabled: function() {
              // only allow new reviews when looking at an exam
              return (
                this.$$getAddEnabled() &&
                "exam" == this.getSubjectFromState() &&
                ["add_apex_review", "view"].includes( this.getActionFromState() )
              );
            },

            // override transitionToAddState
            transitionToAddState: async function () {
              // typists immediately get a new review (no add state required)
              if ("typist" == CnSession.role.name) {
                try {
                  var response = await CnHttpFactory.instance({
                    path: "apex_review",
                    data: { user_id: CnSession.user.id },
                    onError: async function (error) {
                      if (408 == error.status) {
                        // 408 means there are currently no images available
                        CnModalMessageFactory.instance({
                          title: "No Images Available",
                          message: error.data,
                          error: true,
                        }).show();
                      } else if (409 == error.status) {
                        // 409 means there is a conflict (user cannot start new reviews)
                        await CnModalMessageFactory.instance({
                          title: "Cannot Begin New Review",
                          message: error.data,
                          error: true,
                        }).show();
                      } else CnModalMessageFactory.httpError(error);
                    },
                  }).post();

                  // immediately view the new review
                  await this.transitionToViewState({
                    getIdentifier: function () {
                      return response.data;
                    },
                  });
                } catch (error) {
                  // handled by onError above
                }
              } else {
                await this.$$transitionToAddState(); // everyone else gets the default behaviour
              }
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
