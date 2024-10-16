cenozoApp.defineModule({
  name: "review",
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
        singular: "review",
        plural: "reviews",
        possessive: "review's",
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
        completed: {
          title: "Completed",
          type: "boolean",
        },
        notification: {
          title: "Notification",
          type: "string",
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
      completed: {
        title: "Completed",
        type: "boolean",
        isConstant: function ($state, model) {
          return !model.viewModel.isTypist();
        },
        isExcluded: function($state, model) { return "add"; },
      },
      notification: {
        title: "Notification",
        type: "enum",
        isExcluded: function ($state, model) {
          return "add_review" == model.getActionFromState() || !model.isRole("administrator");
        },
      },
      feedback: {
        title: "Feedback",
        type: "text",
        isConstant: function ($state, model) {
          return !model.viewModel.isTypist();
        },
        isExcluded: function($state, model) { return "add"; },
      },
      note: {
        column: "exam.note",
        title: "Exam Notes",
        type: "text",
        isExcluded: function($state, model) { return "add"; },
      },
    });

    if (angular.isDefined(module.actions.multiedit)) {
      module.addExtraOperation("list", {
        title: "Review Multi-Edit",
        operation: async function ($state, model) {
          await $state.go("review.multiedit");
        },
        isIncluded: function ($state, model) {
          // only show when viewing the base review list
          return "review" == model.getSubjectFromState();
        },
      });
    }

    module.addExtraOperation("view", {
      title: "Mark as Read",
      operation: async function ($state, model) {
        if (model.isRole("coordinator")) {
          await model.viewModel.setNotification("read");
        }
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotification;
      },
      isIncluded: function ($state, model) {
        return model.isRole("coordinator") && "alert" == model.viewModel.record.notification;
      },
    });

    module.addExtraOperation("view", {
      title: "Mark as Unread",
      operation: async function ($state, model) {
        if (model.isRole("coordinator")) {
          await model.viewModel.setNotification("alert");
        }
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotification;
      },
      isIncluded: function ($state, model) {
        return model.isRole("coordinator") && "read" == model.viewModel.record.notification;
      },
    });

    module.addExtraOperation("view", {
      title: "Alert Coordinator",
      operation: async function ($state, model) {
        await model.viewModel.setNotification("alert");
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotification;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist") && !model.viewModel.record.notification;
      },
    });

    module.addExtraOperation("view", {
      title: "Remove Alert",
      operation: async function ($state, model) {
        await model.viewModel.setNotification("");
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotification;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist") && model.viewModel.record.notification;
      },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnReviewMultiedit", [
      "CnReviewMultieditFactory",
      "CnSession",
      "$state",
      function (CnReviewMultieditFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("multiedit.tpl.html"),
          restrict: "E",
          controller: function ($scope) {
            $scope.model = CnReviewMultieditFactory.instance();
            $scope.tab = "review";
            CnSession.setBreadcrumbTrail([
              { title: "Reviews", go: async function () { await $state.go("review.list"); } },
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
    cenozo.providers.factory("CnReviewMultieditFactory", [
      "CnReviewModelFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalDatetimeFactory",
      "CnModalMessageFactory",
      function (CnReviewModelFactory, CnSession, CnHttpFactory, CnModalDatetimeFactory, CnModalMessageFactory) {
        var object = function () {
          angular.extend(this, {
            parentModel: CnReviewModelFactory.root,
            module: module,
            confirmInProgress: false,
            randomData: {
              canProceed: false,
              startDate: null,
              endDate: null,
              examsPerInterviewer: null,
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
            modalityList: [],
            modalityId: null,
            selectionTypeList: [
              { name: "Random", value: "random" },
              { name: "UID", value: "uid" },
            ],
            selectionType: "random",
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
            notificationList: [
              { name: "(leave unchanged)", value: null },
              { name: "Remove alert", value: "" },
              { name: "Alert user", value: "alert" },
              { name: "Mark as read", value: "read" },
            ],
            notification: null,

            selectDate: async function (type) {
              const response = await CnModalDatetimeFactory.instance({
                title: "start" == type ? "Start Date" : "End Date",
                date: "start" == type ? this.randomData.startDate : this.randomData.endDate,
                minDate: "end" == type ? this.randomData.startDate : null,
                maxDate: "start" == type ? this.randomData.endDate : null,
                pickerType: "date",
                emptyAllowed: false,
              }).show();

              if (false !== response) {
                if ("start" == type) {
                  this.randomData.startDate = response;
                  this.formattedStartDate = CnSession.formatValue(response, "date", true);
                } else {
                  this.randomData.endDate = response;
                  this.formattedEndDate = CnSession.formatValue(response, "date", true);
                }
                await this.selectionChanged("random");
              }
            },

            sanitizeExamsPerInterviewer: function () {
              this.randomData.examsPerInterviewer =
                Number(this.randomData.examsPerInterviewer.replace(/[^0-9]/g, ""));
            },

            selectionChanged: async function (type) {
              if ((
                "random" == this.selectionType &&
                this.randomData.startDate &&
                this.randomData.endDate
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
              if ("random" == this.selectionType) this.randomData.canProceed = false;
              else if ("uid" == this.selectionType) this.uidData.canProceed = false;

              let data = {};
              if (this.studyPhaseId) data.study_phase_id = this.studyPhaseId;
              if (this.modalityId) data.modality_id = this.modalityId;

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

                if ("random" == this.selectionType) {
                  angular.extend(data, {
                    start_date: this.randomData.startDate.replace(/T.*/, ""),
                    end_date: this.randomData.endDate.replace(/T.*/, ""),
                  });
                  var response = await CnHttpFactory.instance({ path: "review", data: data }).post();
                  this.randomData.examDataList = response.data;
                  this.randomData.canProceed = 0 < Object.keys(this.randomData.examDataList).length;
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
                    var response = await CnHttpFactory.instance({ path: "review", data: data }).post();
                    angular.extend(this.uidData, {
                      uidListString: response.data.uid_list.join(" "),
                      examDataList: response.data.exam_list,
                    });
                    this.uidData.canProceed = 0 < this.uidData.uidListString.length;

                    // count with/without totals
                    angular.extend(this.uidData, { withTotal: 0, withoutTotal: 0 });
                    for(phase in this.uidData.examDataList) {
                      for(modality in this.uidData.examDataList[phase]) {
                        this.uidData.withTotal += Number(this.uidData.examDataList[phase][modality].with);
                        this.uidData.withoutTotal += Number(this.uidData.examDataList[phase][modality].without);
                      }
                    }
                  }
                }
              } finally {
                this.confirmInProgress = false;
              }
            },

            proceed: async function (type) {
              if ("random" == this.selectionType) {
                let data = {
                  exams_per_interviewer: this.randomData.examsPerInterviewer,
                  start_date: this.randomData.startDate.replace(/T.*/, ""),
                  end_date: this.randomData.endDate.replace(/T.*/, ""),
                  user_id: this.userId,
                  process: true,
                };

                if (angular.isDefined(this.studyPhaseId)) data.study_phase_id = this.studyPhaseId;
                if (angular.isDefined(this.modalityId)) data.modality_id = this.modalityId;

                const response = await CnHttpFactory.instance({
                  path: "review",
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
                if (angular.isDefined(this.modalityId)) data.modality_id = this.modalityId;
                if (0 < this.uidData.withoutTotal) {
                  if (angular.isDefined(this.userId)) data.user_id = this.userId;
                }
                if (0 < this.uidData.withTotal) {
                  if (angular.isDefined(this.completed)) data.completed = this.completed;
                  if (angular.isDefined(this.notification)) data.notification = this.notification;
                }

                const response = await CnHttpFactory.instance({
                  path: "review",
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
            const [studyPhaseResponse, modalityResponse] = await Promise.all([
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
                path: "modality",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: {
                    order: "modality.name",
                  },
                },
              }).query()
            ]);

            object.studyPhaseList = studyPhaseResponse.data.reduce((list, item) => {
              list.push({ value: item.id, name: item.name });
              return list;
            }, []);
            object.studyPhaseList.unshift({ name: "(all)", value: null });

            object.modalityList = modalityResponse.data.reduce((list, item) => {
              list.push({ value: item.id, name: item.name });
              return list;
            }, []);
            object.modalityList.unshift({ name: "(all)", value: null });
          }

          init(this);
        };

        return {
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);

    cenozo.providers.factory("CnReviewViewFactory", [
      "CnBaseViewFactory",
      "CnImageDisplayFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (CnBaseViewFactory, CnImageDisplayFactory, CnSession, CnHttpFactory, CnModalMessageFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          angular.extend(this, {
            isLoading: false,
            imageDisplayModel: CnImageDisplayFactory.instance(),
            analysisList: [],
            currentAnalysis: null,

            changingNotification: false,
            setNotification: async function(value) {
              try {
                this.changingNotification = true;
                await this.onPatch({ notification: value });
                this.record.notification = value;
              } catch (error) {
              } finally {
                this.changingNotification = false;
              }
            },

            isTypist: function() {
              return this.parentModel.isRole("typist") && this.record.user_id == CnSession.user.id;
            },

            selectAnalysis: function(index) {
              const analysis = this.analysisList.findByProperty("index", index);
              if (null != analysis) {
                this.currentAnalysis = analysis;
                this.loadAnalysis();
              }
            },

            onView: async function (force) {
              await this.$$onView();

              this.isLoading = true;

              try {
                // get a list of all analysis records
                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + '/analysis',
                }).query();

                this.analysisList = response.data.map((record, index) => ({
                  index: index,
                  analysisId: record.id,
                  imageId: record.image_id,
                  codeGroupList: [],
                  rating: null,
                }));

                // load the codes for all analyses
                await Promise.all(
                  this.analysisList.map(async (analysis) => {
                    const response = await CnHttpFactory.instance({
                      path: ["analysis", analysis.analysisId, "code?full=1"].join("/"),
                    }).query();
                    analysis.codeGroupList = response.data;

                    // add a working property to all codes
                    analysis.codeGroupList.forEach(g => g.code_list.map(c => { c.working = false; return c }));
                  })
                );

                // now that the images are loaded view the image display
                angular.extend(this.imageDisplayModel, {
                  isTypist: this.isTypist(),
                  reviewUserId: this.record.user_id,
                  imageList: this.analysisList,
                  selectImage: (index) => {
                    const analysis = this.analysisList.findByProperty("index", index);
                    if (null != analysis) {
                      this.currentAnalysis = analysis;
                      this.loadAnalysis();

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

            loadAnalysis: function () {
              if (null != this.currentAnalysis) this.calculateRating();
            },
            
            calculateRating: function () {
              let rating = 5;
              this.currentAnalysis.codeGroupList.forEach(group => {
                let inGroup = false;
                group.code_list.filter(code => code.selected).forEach(code => {
                  rating += code.value;
                  inGroup = true;
                });
                if (inGroup) rating += group.value;
              });

              if (1 > rating) rating = 1;
              else if (5 < rating) rating = 5;
              this.currentAnalysis.rating = rating;
            },
            
            getCodeDescription: function(code) {
              return (
                (code.description ? (code.description + " ") : "") +
                (0 == code.value ? "" : "(" + code.value + ")")
              );
            },
            
            toggleCode: async function(code) {
              code.working = true;

              try {
                const self = this;
                if (code.selected) {
                  // remove the code
                  const identifierList = [
                    "analysis_id=" + this.currentAnalysis.analysisId,
                    "code_type_id=" + code.code_type_id,
                  ];
                  await CnHttpFactory.instance({
                    path: "code/" + identifierList.join(";"),
                    onError: function (error) {
                      if (404 == error.status) {
                        console.info("The above 404 error can be safely ignored.");
                        code.selected = !code.selected;
                        self.calculateRating();
                      } else CnModalMessageFactory.httpError(error);
                    }
                  }).delete();
                } else {
                  // add the code
                  await CnHttpFactory.instance({
                    path: ["analysis", this.currentAnalysis.analysisId, "code"].join("/"),
                    data: { image_id: this.record.id, code_type_id: code.code_type_id },
                    onError: function (error) {
                      if (409 == error.status) {
                        console.info("The above 409 error can be safely ignored.");
                        code.selected = !code.selected;
                        self.calculateRating();
                      } else CnModalMessageFactory.httpError(error);
                    }
                  }).post();
                }

                code.selected = !code.selected;
                this.calculateRating();
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
    cenozo.providers.factory("CnReviewAddFactory", [
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
    cenozo.providers.factory("CnReviewModelFactory", [
      "CnBaseModelFactory",
      "CnReviewAddFactory",
      "CnReviewListFactory",
      "CnReviewViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (
        CnBaseModelFactory,
        CnReviewAddFactory,
        CnReviewListFactory,
        CnReviewViewFactory,
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            addModel: CnReviewAddFactory.instance(this),
            listModel: CnReviewListFactory.instance(this),
            viewModel: CnReviewViewFactory.instance(this, root),

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

                if (this.isRole("coordinator")) {
                  data.modifier.where.push({ column: "review.notification", operator: "=", value: "alert" });
                } else if (this.isRole("typist")) {
                  data.modifier.where.push({ column: "review.user_id", operator: "=", value: CnSession.user.id });
                  data.modifier.where.push({ column: "review.completed", operator: "=", value: false });
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
                ["add_review", "view"].includes( this.getActionFromState() )
              );
            },

            // override transitionToAddState
            transitionToAddState: async function () {
              // typists immediately get a new review (no add state required)
              if ("typist" == CnSession.role.name) {
                try {
                  var response = await CnHttpFactory.instance({
                    path: "review",
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
