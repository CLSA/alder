cenozoApp.defineModule({
  name: "review",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "image",
          column: "image.id",
        },
      },
      name: {
        singular: "review",
        plural: "reviews",
        possessive: "review's",
      },
      columnList: {
        user: {
          column: "user.name",
          title: "Reviewer",
        },
        completed: {
          title: "Completed",
          type: "boolean",
        },
        coordinator: {
          title: "Coordinator Read",
          type: "string",
        },
        interviewer: {
          title: "Interviewer Read",
          type: "string",
        },
        rating: {
          title: "Calculated Rating",
        },
        code_list: {
          title: "Code List",
        }
      },
      defaultOrder: {
        column: "user.name",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      user: {
        column: "user.name",
        title: "Reviewer",
        type: "string",
        isConstant: true,
      },
      uid: {
        column: "participant.uid",
        title: "Participant",
        type: "string",
        isConstant: true,
      },
      phase: {
        column: "study_phase.name",
        title: "Phase",
        type: "string",
        isConstant: true,
      },
      site: {
        column: "site.name",
        title: "Site",
        type: "string",
        isConstant: true,
      },
      exam_interviewer: {
        column: "exam.interviewer",
        title: "Interviewer",
        type: "string",
        isConstant: true,
      },
      image_type: {
        title: "Type",
        type: "string",
        isConstant: true,
      },
      completed: {
        title: "Completed",
        type: "boolean",
        isConstant: function ($state, model) {
          return !model.viewModel.isTypist();
        },
      },
      coordinator: {
        title: "Coordinator notification",
        type: "enum",
        isExcluded: function ($state, model) {
          return !model.isRole("administrator");
        },
      },
      interviewer: {
        title: "Interviewer notification",
        type: "enum",
        isExcluded: function ($state, model) {
          return !model.isRole("administrator");
        },
      },
      rating: {
        title: "Rating",
        type: "string",
        isConstant: true,
      },
      feedback: {
        title: "Feedback",
        type: "text",
        isConstant: function ($state, model) {
          return !model.viewModel.isTypist();
        },
      },
      note: {
        column: "image.note",
        title: "Image Notes",
        type: "text",
      },
      user_id: { type: "hidden" },
      image_id: { type: "hidden" },
    });

    if (angular.isDefined(module.actions.multiedit)) {
      module.addExtraOperation("list", {
        title: "Review Multi-Edit",
        operation: async function ($state, model) {
          await $state.go("review.multiedit");
        },
      });
    }

    module.addExtraOperation("view", {
      title: "Mark as Read",
      operation: async function ($state, model) {
        if (model.isRole("coordinator")) {
          await model.viewModel.setNotification("coordinator", "read");
        } else if (model.isRole("interviewer")) {
          await model.viewModel.setNotification("interviewer", "read");
        }
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotifications;
      },
      isIncluded: function ($state, model) {
        return (
          (model.isRole("coordinator") && "alert" == model.viewModel.record.coordinator) ||
          (model.isRole("interviewer") && "alert" == model.viewModel.record.interviewer)
        );
      },
    });

    module.addExtraOperation("view", {
      title: "Mark as Unread",
      operation: async function ($state, model) {
        if (model.isRole("coordinator")) {
          await model.viewModel.setNotification("coordinator", "alert");
        } else if (model.isRole("interviewer")) {
          await model.viewModel.setNotification("interviewer", "alert");
        }
      },
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotifications;
      },
      isIncluded: function ($state, model) {
        return (
          (model.isRole("coordinator") && "read" == model.viewModel.record.coordinator) ||
          (model.isRole("interviewer") && "read" == model.viewModel.record.interviewer)
        );
      },
    });

    module.addExtraOperationGroup("view", {
      title: "Notifications",
      operations: [
        {
          title: "Alert Coordinator",
          operation: async function ($state, model) {
            await model.viewModel.setNotification("coordinator", "alert");
          },
          isIncluded: function ($state, model) {
            return !model.viewModel.record.coordinator;
          },
        },
        {
          title: "Remove Coordinator Alert",
          operation: async function ($state, model) {
            await model.viewModel.setNotification("coordinator", "");
          },
          isIncluded: function ($state, model) {
            return model.viewModel.record.coordinator;
          },
        },
        {
          title: "Alert Interviewer",
          operation: async function ($state, model) {
            await model.viewModel.setNotification("interviewer", "alert");
          },
          isIncluded: function ($state, model) {
            return !model.viewModel.record.interviewer;
          },
        },
        {
          title: "Remove Interviewer Alert",
          operation: async function ($state, model) {
            await model.viewModel.setNotification("interviewer", "");
          },
          isIncluded: function ($state, model) {
            return model.viewModel.record.interviewer;
          },
        },
      ],
      isDisabled: function ($state, model) {
        return model.viewModel.changingNotifications;
      },
      isIncluded: function ($state, model) {
        return model.isRole("typist");
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
      "CnModalMessageFactory",
      function (CnReviewModelFactory, CnSession, CnHttpFactory, CnModalMessageFactory) {
        var object = function () {
          this.parentModel = CnReviewModelFactory.root;
          this.module = module;
          this.confirmInProgress = false;
          this.confirmedCount = null;
          this.imageDataList = null;
          this.withTotal = 0;
          this.withoutTotal = 0;
          this.uidListString = "";
          this.studyPhaseList = [];
          this.study_phase_id = undefined;
          this.modalityList = [];
          this.modality_id = undefined;
          this.userList = [];
          this.user_id = undefined;
          this.notificationList = [
            { name: "(leave unchanged)", value: undefined },
            { name: "Remove alert", value: null },
            { name: "Alert user", value: "alert" },
            { name: "Mark as read", value: "read" },
          ];
          this.coordinator = undefined;
          this.interviewer = undefined;

          this.selectionChanged = function () {
            this.confirmedCount = null;
            this.user_id = undefined;
            this.userList = [];
          };

          this.confirm = async function () {
            this.confirmInProgress = true;
            this.confirmedCount = null;
            var uidRegex = new RegExp(CnSession.application.uidRegex);

            // clean up the uid list
            var fixedList = this.uidListString
              .toUpperCase() // convert to uppercase
              .replace(/[\s,;|\/]/g, " ") // replace whitespace and separation chars with a space
              .replace(/[^a-zA-Z0-9 ]/g, "") // remove anything that isn't a letter, number of space
              .split(" ") // delimite string by spaces and create array from result
              .filter((uid) => null != uid.match(uidRegex)) // match UIDs (eg: A123456)
              .filter((uid, index, array) => index <= array.indexOf(uid)) // make array unique
              .sort(); // sort the array

            // now confirm UID list with server
            if (0 == fixedList.length) {
              this.imageDataList = null;
              this.uidListString = "";
              this.confirmInProgress = false;
            } else {
              try {
                let data = { uid_list: fixedList };
                if (this.study_phase_id) data.study_phase_id = this.study_phase_id;
                if (this.modality_id) data.modality_id = this.modality_id;
                this.confirmedCount = null;
                var response = await CnHttpFactory.instance({ path: "review", data: data }).post();
                this.confirmedCount = response.data.uid_list.length;
                this.uidListString = response.data.uid_list.join(" ");
                this.imageDataList = response.data.image_list;
                
                // count with/without totals
                this.withTotal = 0;
                this.withoutTotal = 0;
                for(phase in this.imageDataList) {
                  for(modality in this.imageDataList[phase]) {
                    this.withTotal += Number(this.imageDataList[phase][modality].with);
                    this.withoutTotal += Number(this.imageDataList[phase][modality].without);
                  }
                }
              } finally {
                this.confirmInProgress = false;
              }

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
              this.userList.unshift({ name: "(empty)", value: undefined });
            }
          };

          this.proceed = async function (type) {
            // test the formats of all columns
            let data = {
              uid_list: this.uidListString.split(" "),
              process: true,
            };

            if (angular.isDefined(this.study_phase_id)) data.study_phase_id = this.study_phase_id;
            if (angular.isDefined(this.modality_id)) data.modality_id = this.modality_id;
            if (0 < this.withoutTotal) {
              if (angular.isDefined(this.user_id)) data.user_id = this.user_id;
            }
            if (0 < this.withTotal) {
              if (angular.isDefined(this.coordinator)) data.coordinator = this.coordinator;
              if (angular.isDefined(this.interviewer)) data.interviewer = this.interviewer;
            }

            await CnHttpFactory.instance({
              path: "review",
              data: data,
              onError: CnModalMessageFactory.httpError,
            }).post();

            let reviewString =
              uidList.length + " review" +
              (1 != uidList.length ? "s have " : " has ") + "been processed";

            let userString =
              angular.isDefined(this.user_id) ?
              (' and assigned to user "' + this.userList.findByProperty("value", this.user_id).user + '"') :
              '';

            await CnModalMessageFactory.instance({
              title: "Review(s) Processed",
              message:
                "A total of " +
                reviewString +
                userString +
                ".",
            }).show();

            this.confirmedCount = null;
            this.uidListString = "";
            this.userList = [];
            this.user_id = undefined;
          };

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
            object.studyPhaseList.unshift({ name: "(all)", value: undefined });

            object.modalityList = modalityResponse.data.reduce((list, item) => {
              list.push({ value: item.id, name: item.name });
              return list;
            }, []);
            object.modalityList.unshift({ name: "(all)", value: undefined });
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

    /* ############################################################################################## */
    cenozo.providers.factory("CnReviewViewFactory", [
      "CnBaseViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (CnBaseViewFactory, CnSession, CnHttpFactory, CnModalMessageFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          angular.extend(this, {
            isTypist: function() {
              return this.parentModel.isRole("typist") && this.record.user_id == CnSession.user.id;
            },
            changingNotifications: false,
            setNotification: async function(type, value) {
              try {
                this.changingNotifications = true;
                let patch = {};
                patch[type] = value;
                await this.onPatch(patch);
                this.record[type] = value;
              } catch (error) {
              } finally {
                this.changingNotifications = false;
              }
            },

            codeGroupList: [],
            onView: async function (force) {
              await this.$$onView();

              this.isLoading = true;

              try {
                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + "/code?full=1",
                }).query();

                // add a working property to all codes
                this.codeGroupList = response.data;
                this.codeGroupList.forEach(g => g.code_list.map(c => { c.working = false; return c }));
              } finally {
                this.isLoading = false;
              }
            },
            calculateRating: function () {
              let rating = 5;
              this.codeGroupList.forEach(group => {
                if (group.code_list.some(code => code.selected)) rating += group.value;
              });

              if (1 > rating) rating = 1;
              else if (5 < rating) rating = 5;
              this.record.rating = rating;
            },
            toggleCode: async function(code) {
              code.working = true;

              try {
                const self = this;
                if (code.selected) {
                  // remove the code
                  const identifierList = [
                    "review_id=" + this.record.id,
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
                    path: this.parentModel.getServiceResourcePath() + "/code",
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
    cenozo.providers.factory("CnReviewModelFactory", [
      "CnBaseModelFactory",
      "CnReviewListFactory",
      "CnReviewViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (
        CnBaseModelFactory,
        CnReviewListFactory,
        CnReviewViewFactory,
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
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
              if ("root" == this.getSubjectFromState() && this.isRole("coordinator", "interviewer", "typist")) {
                if (angular.isUndefined(data.modifier.where)) data.modifier.where = []; 

                if (this.isRole("coordinator")) {
                  data.modifier.where.push({ column: "review.coordinator", operator: "=", value: "alert" });
                } else if (this.isRole("interviewer")) {
                  data.modifier.where.push({ column: "review.interviewer", operator: "=", value: "alert" });
                } else if (this.isRole("typist")) {
                  data.modifier.where.push({ column: "review.user_id", operator: "=", value: CnSession.user.id });
                  data.modifier.where.push({ column: "review.completed", operator: "=", value: false });
                }
              }
              return data;
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
