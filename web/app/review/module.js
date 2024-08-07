cenozoApp.defineModule({
  name: "review",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
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
        side: {
          column: "exam.side",
          title: "Side",
        },
        interviewer: {
          column: "exam.interviewer",
          title: "Interviewer",
        },
        image_type: {
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
        rating: {
          title: "Calculated Rating",
        }
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
      side: {
        column: "exam.side",
        title: "Side",
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
      image_type: {
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
      rating: {
        title: "Rating",
        type: "string",
        isConstant: true,
        isExcluded: function($state, model) { return "add"; },
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
        column: "image.note",
        title: "Image Notes",
        type: "text",
        isExcluded: function($state, model) { return "add"; },
      },
      image_id: { type: "hidden" },
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
          this.completedList = [
            { name: "(leave unchanged)", value: undefined },
            { name: "Yes", value: 1 },
            { name: "No", value: 0 },
          ];
          this.completed = undefined;
          this.notificationList = [
            { name: "(leave unchanged)", value: undefined },
            { name: "Remove alert", value: null },
            { name: "Alert user", value: "alert" },
            { name: "Mark as read", value: "read" },
          ];
          this.notification = undefined;

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
            let uidList = this.uidListString.split(" ");

            // test the formats of all columns
            let data = {
              uid_list: uidList,
              process: true,
            };

            if (angular.isDefined(this.study_phase_id)) data.study_phase_id = this.study_phase_id;
            if (angular.isDefined(this.modality_id)) data.modality_id = this.modality_id;
            if (0 < this.withoutTotal) {
              if (angular.isDefined(this.user_id)) data.user_id = this.user_id;
            }
            if (0 < this.withTotal) {
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
            canvas: null,
            context: null,
            image: null,
            canvasRatio: null,
            imageRatio: null,
            transform: { x: 0, y: 0, b: 1, c: 1, scale: 1 },
            annotationList: [],
            activeAnnotation: null,
            hover: { type: null, handle: null, id: null },

            bounds: {
              x: { min: 0, max: 1 },
              y: { min: 0, max: 1 },
              b: { min: 0.5, max: 2.0 },
              c: { min: 1.0, max: 10.0 },
              scale: { min: 1, max: 16 },
            },

            isTypist: function() {
              return this.parentModel.isRole("typist") && this.record.user_id == CnSession.user.id;
            },
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

            codeGroupList: [],
            onView: async function (force) {
              await this.$$onView();

              this.isLoading = true;

              try {
                // prepare the canvas
                this.canvas = document.getElementById("canvas");
                const rect = this.canvas.getBoundingClientRect();
                this.canvas.width = rect.width*window.devicePixelRatio;
                this.canvas.height = rect.height*window.devicePixelRatio;

                this.context = this.canvas.getContext("2d")
                this.context.scale(window.devicePixelRatio, window.devicePixelRatio);
                this.drawNotice("loading image...");

                this.image = new Image();
                this.image.onload = () => {
                  this.imageRatio = this.image.width/this.image.height;
                  this.reset();
                  this.paint();
                }

                // load the image and all annotation data
                this.annotationList = [];
                this.codeGroupList = [];

                const [codeResponse, imageResponse, annotationResponse] = await Promise.all([
                  CnHttpFactory.instance({
                    path: this.parentModel.getServiceResourcePath() + "/code?full=1",
                  }).query(),

                  CnHttpFactory.instance({
                    path: "image/" + this.parentModel.viewModel.record.image_id,
                    data: { select: { column: "image" } },
                  }).get(),

                  CnHttpFactory.instance({
                    path: this.parentModel.getServiceResourcePath() + "/annotation"
                  }).query(),
                ]);

                if (0 == imageResponse.data.image.size) {
                  this.drawNotice("No image found");
                } else {
                  this.image.src = imageResponse.data.image.data;
                  this.annotationList = annotationResponse.data.reduce((list, a) => {
                    list.push({ id: a.id, type: a.type, x0: a.x0, y0: a.y0, x1: a.x1, y1: a.y1 });
                    return list;
                  }, []);
                  this.createEventListeners();
                }

                // add a working property to all codes
                this.codeGroupList = codeResponse.data;
                this.codeGroupList.forEach(g => g.code_list.map(c => { c.working = false; return c }));
              } finally {
                this.isLoading = false;
              }
            },
            calculateRating: function () {
              let rating = 5;
              this.codeGroupList.forEach(group => {
                let inGroup = false;
                group.code_list.filter(code => code.selected).forEach(code => {
                  rating += code.value;
                  inGroup = true;
                });
                if (inGroup) rating += group.value;
              });

              if (1 > rating) rating = 1;
              else if (5 < rating) rating = 5;
              this.record.rating = rating;
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

            updateView: function(scale) {
              const rect = this.canvas.getBoundingClientRect();
              this.canvas.width = rect.width*window.devicePixelRatio;
              this.canvas.height = rect.height*window.devicePixelRatio;
              this.canvasRatio = this.canvas.width/this.canvas.height;
              this.heightBased = this.canvasRatio > this.imageRatio;
              this.irRatio = this.heightBased ? this.image.height/rect.height : this.image.width/rect.width;
              this.icRatio = (
                this.heightBased ? this.image.height/this.canvas.height : this.image.width/this.canvas.width
              );
              this.canvasView = {
                x: 0,
                y: 0,
                width: this.heightBased ? this.canvas.height*this.imageRatio : this.canvas.width,
                height: this.heightBased ? this.canvas.height : this.canvas.width/this.imageRatio,
              };

              this.bounds.x.max = 0;
              this.bounds.y.max = 0;
              if (this.heightBased) {
                this.bounds.x.max = this.image.width - this.image.height/scale*this.canvasRatio;
                this.bounds.y.max = this.image.height - this.image.height/scale;
              } else {
                this.bounds.x.max = this.image.width - this.image.width/scale;
                this.bounds.y.max = this.image.height - this.image.width/scale/this.canvasRatio;
              }
              if (this.bounds.x.min > this.bounds.x.max) this.bounds.x.max = this.bounds.x.min;
              if (this.bounds.y.min > this.bounds.y.max) this.bounds.y.max = this.bounds.y.min;
            },

            canvasToImage: function(x, y) {
              const rect = this.canvas.getBoundingClientRect();
              return {
                x: (x - rect.x)*this.irRatio/this.transform.scale + this.transform.x,
                y: (y - rect.y)*this.irRatio/this.transform.scale + this.transform.y,
              };
            },

            imageToCanvas: function(x, y) {
              const rect = this.canvas.getBoundingClientRect();
              return {
                x: (x - this.transform.x)*this.transform.scale/this.icRatio,
                y: (y - this.transform.y)*this.transform.scale/this.icRatio,
              };
            },

            reset: function() {
              this.transform = { x: 0, y: 0, b: 1, c: 1, scale: 1 };
              this.hover = { type: null, handle: null, id: null };
            },

            drawNotice: function(notice) {
              this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
              this.context.font = "18px Arial";
              this.context.fillText(notice, 20, 40);
            },

            drawAnnotation: function(annotation) {
              let p0 = this.imageToCanvas(annotation.x0, annotation.y0);
              let p1 = this.imageToCanvas(annotation.x1, annotation.y1);

              this.context.lineWidth = 2;
              this.context.strokeStyle = "blue";
              this.context.fillStyle = "blue";

              if ("arrow" == annotation.type) {
                let a = Math.atan((p1.y - p0.y)/(p1.x - p0.x)) + (p1.x < p0.x ? Math.PI : 0);

                // draw the arrow body
                this.context.beginPath();
                this.context.moveTo(p0.x, p0.y);
                this.context.lineTo(p1.x, p1.y);
                this.context.stroke();

                // draw the hover markers
                if (this.hover.id == annotation.id) {
                  if (["all", "start"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(p0.x, p0.y, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }

                  if (["all", "end"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(p1.x, p1.y, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }
                }

                // rotate about the a head
                this.context.translate(p1.x, p1.y);
                this.context.rotate(a);
                this.context.translate(-p1.x, -p1.y);

                // draw the arrow head
                this.context.beginPath();
                this.context.moveTo(p1.x + 1, p1.y);
                this.context.lineTo(p1.x - 10, p1.y - 6);
                this.context.lineTo(p1.x - 10, p1.y + 6);
                this.context.fill();

                // reverse the rotation
                this.context.translate(p1.x, p1.y);
                this.context.rotate(-a);
                this.context.translate(-p1.x, -p1.y);
              } else if ("ellipse" == annotation.type) {
                let cx = (p0.x + p1.x)/2;
                let cy = (p0.y + p1.y)/2;
                let rx = Math.abs(p1.x - p0.x)/2;
                let ry = Math.abs(p1.y - p0.y)/2;

                this.context.beginPath();
                this.context.ellipse(cx, cy, rx, ry, 0, 0, 2*Math.PI);
                this.context.stroke();

                // draw the hover markers
                if (this.hover.id == annotation.id) {
                  if (["all", "nw"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(cx - rx, cy - ry, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }

                  if (["all", "sw"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(cx - rx, cy + ry, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }

                  if (["all", "se"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(cx + rx, cy + ry, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }

                  if (["all", "ne"].includes(this.hover.handle)) {
                    this.context.beginPath();
                    this.context.arc(cx + rx, cy - ry, 6, 0, 2*Math.PI);
                    this.context.stroke();
                  }
                }
              }
            },

            paint: function() {
              if (null == this.transform) this.reset();

              // recalculate the view based on the current scale
              this.updateView(this.transform.scale);

              // scale the image based on the current transform
              this.context.scale(this.transform.scale, this.transform.scale);

              // clear background by filling with black
              this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);

              this.context.filter =
                "brightness(" + (100*this.transform.b) + "%) " +
                "contrast(" + (100*this.transform.c) + "%)";

              this.context.drawImage(
                this.image,

                // location on image to paint at the canvas origin (only affected by scalling/panning)
                this.transform.x, this.transform.y,

                // image's width/height (never changes)
                this.image.width, this.image.height,

                // canvas view origin (never changes)
                this.canvasView.x, this.canvasView.y,

                // canvas view width/height (only affected by window size)
                this.canvasView.width, this.canvasView.height
              );

              this.context.scale(1/this.transform.scale, 1/this.transform.scale);

              // draw all annotations
              this.annotationList.forEach(annotation => this.drawAnnotation(annotation));

              // draw the active annotation
              if (null != this.activeAnnotation) {
                this.drawAnnotation(this.activeAnnotation);
              }
            },

            // cx and cy must be between 0 and 1 based on the center of scaling
            // inward should be true to zoom in, false to zoom out
            scale: function(cx, cy, inward) {
              // we either use the image height or width, depending on how it fits on the canvas
              let imageMeasure = this.heightBased ? this.image.height : this.image.width;

              // zoom in by 1/32nd of the image width
              let scaleFactor = (inward ? 1 : -1)*imageMeasure/32;

              // determine the new scale value
              let newScale = imageMeasure/(imageMeasure/this.transform.scale - scaleFactor);

              // restrict to scalling boundaries (1x to 10x)
              if (this.bounds.scale.min > newScale || this.bounds.scale.max < newScale) return;

              // determine the new transform coordinates
              let x = 0, y = 0;
              if (1 < newScale) {
                x = this.transform.x + scaleFactor*cx*(this.heightBased ? this.canvasRatio : 1);
                y = this.transform.y + scaleFactor*cy*(this.heightBased ? 1 : 1/this.canvasRatio);
              }

              // recalculate the view based on the new scale
              this.updateView(newScale);

              // apply the new scale and redraw the image
              angular.extend(this.transform, {
                x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
                y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
                scale: newScale,
              });

              this.paint();
            },

            pan: function(dx, dy) {
              let x = this.transform.x + dx*this.irRatio/this.transform.scale;
              let y = this.transform.y + dy*this.irRatio/this.transform.scale;
              angular.extend(this.transform, {
                x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
                y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
              });

              this.paint();
            },

            windowLevel: function(dc, db) {
              let b = this.transform.b + (this.bounds.b.max - this.bounds.b.min)*db/600;
              let c = this.transform.c + (this.bounds.c.max - this.bounds.c.min)*dc/600;

              angular.extend(this.transform, {
                b: b = b < this.bounds.b.min ? this.bounds.b.min : b > this.bounds.b.max ? this.bounds.b.max : b,
                c: c = c < this.bounds.c.min ? this.bounds.c.min : c > this.bounds.c.max ? this.bounds.c.max : c,
              });
              this.paint();
            },

            createAnnotation: function(type, point) {
              if (!this.isTypist()) return;

              this.activeAnnotation = { id: null, type: type };
              angular.extend(this.activeAnnotation, {
                x0: point.x,
                y0: point.y,
                x1: null,
                y1: null,
                grab: "arrow" == this.activeAnnotation.type ? "end" : "se"
              });
            },

            updateActiveAnnotation: function(point) {
              if (!this.isTypist()) return;

              let annotation = null;
              if (null != this.hover.id) {

                // if the annotation is found then remove it from the list
                let index = this.annotationList.findIndexByProperty("id", this.hover.id);
                if (null != index) {
                  annotation = this.annotationList[index];
                  this.annotationList.splice(index, 1);

                  // set where we've grabbed the annotation
                  annotation.grab = "all" == this.hover.handle ? point : this.hover.handle;
                }
              }

              this.activeAnnotation = annotation;
            },

            deleteActiveAnnotation: async function() {
              if (!this.isTypist()) return;
              if (null == this.hover.id) return;

              let index = this.annotationList.findIndexByProperty("id", this.hover.id);
              if (null != index) {
                let id = this.hover.id;
                this.annotationList.splice(index, 1);
                this.hover = { handle: null, id: null };
                this.paint();

                await CnHttpFactory.instance({
                  path: [this.parentModel.getServiceResourcePath(), "annotation", id].join("/")
                }).delete();
              }
            },

            saveActiveAnnotation: async function() {
              if (!this.isTypist()) return;
              if (null == this.activeAnnotation) return;

              let annotation = this.activeAnnotation;
              let data = {
                type: annotation.type,
                x0: annotation.x0,
                y0: annotation.y0,
                x1: annotation.x1,
                y1: annotation.y1
              };

              // check if the annotation is valid
              let valid = (
                null != this.activeAnnotation.x0 && null != this.activeAnnotation.x1 &&
                null != this.activeAnnotation.y0 && null != this.activeAnnotation.y1 && (
                  this.activeAnnotation.x0 != this.activeAnnotation.x1 ||
                  this.activeAnnotation.y0 != this.activeAnnotation.y1
                )
              );

              if (valid && null != annotation) {
                try {
                  let httpObj = {
                    path: this.parentModel.getServiceResourcePath() + "/annotation",
                    data: data,
                    onError: function (error) {}, // do nothing
                  };

                  if (null == annotation.id) {
                    // create a new annotation
                    const response = await CnHttpFactory.instance(httpObj).post();
                    annotation.id = response.data;
                  } else {
                    // update an existing annotation
                    httpObj.path += "/" + annotation.id;
                    await CnHttpFactory.instance(httpObj).patch();
                  }

                  // only add the annotation to the list if it was successfully created
                  this.annotationList.push(annotation);
                } catch (error) {
                  // errors are handled above in the onError functions
                }
              }

              this.activeAnnotation = null;
              this.paint();
            },

            updateHover: function(point) {
              if (!this.isTypist()) return;

              // distance to handle varies with scaling
              const dh = 16/this.transform.scale;

              // 1. check if we're hovering over an annotation handle
              let hover = this.annotationList.some((annotation) => {
                let handle = null;
                if (dh > Math.abs(point.x - annotation.x0) && dh > Math.abs(point.y - annotation.y0)) {
                  handle = "arrow" == annotation.type ? "start" : "nw";
                } else if (dh > Math.abs(point.x - annotation.x1) && dh > Math.abs(point.y - annotation.y0)) {
                  handle = "ne";
                } else if (dh > Math.abs(point.x - annotation.x1) && dh > Math.abs(point.y - annotation.y1)) {
                  handle = "arrow" == annotation.type ? "end" : "se";
                } else if (dh > Math.abs(point.x - annotation.x0) && dh > Math.abs(point.y - annotation.y1)) {
                  handle = "sw";
                }

                if (null != handle) {
                  angular.extend(this.hover, { handle: handle, id: annotation.id });
                  return true;
                }
              });

              // 2. check if we're hovering over an annotation body
              if (!hover) {
                hover = this.annotationList.some((annotation) => {
                  let b = {
                    x0: annotation.x0 < annotation.x1 ? annotation.x0 : annotation.x1,
                    x1: annotation.x0 < annotation.x1 ? annotation.x1 : annotation.x0,
                    y0: annotation.y0 < annotation.y1 ? annotation.y0 : annotation.y1,
                    y1: annotation.y0 < annotation.y1 ? annotation.y1 : annotation.y0,
                  };

                  if (b.x0 <= point.x && point.x <= b.x1 && b.y0 <= point.y && point.y <= b.y1) {
                    angular.extend(this.hover, { handle: "all", id: annotation.id });
                    return true;
                  }
                });
              }

              // 3. we aren't hovering over any annotation
              if (!hover) this.hover = { handle: null, id: null };

              this.paint();
            },

            transformActiveAnnotation: function(point) {
              if (!this.isTypist()) return;

              let aa = this.activeAnnotation;
              if (null != aa) {
                // change which ellipse handle is being grabbed, if necessary
                if ("nw" == aa.grab) {
                  aa.grab = point.x > aa.x1 ? "ne" : point.y > aa.y1 ? "sw" : "nw";
                } else if ("ne" == aa.grab) {
                  aa.grab = point.x < aa.x0 ? "nw" : point.y > aa.y1 ? "se" : "ne";
                } else if ("se" == aa.grab) {
                  aa.grab = point.x < aa.x0 ? "sw" : point.y < aa.y0 ? "ne" : "se";
                } else if ("sw" == aa.grab) {
                  aa.grab = point.x > aa.x1 ? "se" : point.y < aa.y0 ? "nw" : "sw";
                }
                this.hover.handle = aa.grab;

                if (["nw", "start"].includes(aa.grab)) {
                  // move the x0,y0 point
                  angular.extend(aa, { x0: point.x, y0: point.y });
                } else if ("ne" == aa.grab) {
                  // move the x1,y0 point
                  angular.extend(aa, { x1: point.x, y0: point.y });
                } else if (["se", "end"].includes(aa.grab)) {
                  // move the x1,y1 point
                  angular.extend(aa, { x1: point.x, y1: point.y });
                } else if ("sw" == aa.grab) {
                  // move the x0,y1 point
                  angular.extend(aa, { x0: point.x, y1: point.y });
                } else if (angular.isObject(aa.grab)) {
                  // move all points
                  aa.x0 += point.x - aa.grab.x;
                  aa.y0 += point.y - aa.grab.y;
                  aa.x1 += point.x - aa.grab.x;
                  aa.y1 += point.y - aa.grab.y;

                  // update the grab point
                  aa.grab.x = point.x;
                  aa.grab.y = point.y;
                }

                this.paint();
              }
            },

            createEventListeners: function() {
              // redraw the image when the window is resized
              window.addEventListener("resize", () => {
                this.paint();
              });

              // capture all key inputs when the mouse is on the canvas
              window.addEventListener("keydown", async (event) => {
                if (this.canvas.parentNode.matches(":hover")) {
                  event.preventDefault();
                  event.stopPropagation();

                  if ("Delete" == event.key) {
                    // delete the hovered annotation
                    await this.deleteActiveAnnotation();
                  } else if ("Escape" == event.key) {
                    // reset the image
                    this.reset();
                    this.paint();
                  }
                }
              });

              window.addEventListener("mouseout", (event) => {
                if (this.activeAnnotation) {
                  this.activeAnnotation = null;
                  this.paint();
                }
              });

              // track mouse operations
              angular.extend(this.canvas, {
                oncontextmenu: (event) => {
                  event.preventDefault();
                  event.stopPropagation();
                },

                onmousedown: (event) => {
                  let button = null;
                  if (0 == event.button) button = "left";
                  else if (1 == event.button) button = "middle";
                  else if (2 == event.button) button = "right";

                  const point = this.canvasToImage(event.clientX, event.clientY);
                  if (event.altKey) {
                    // do nothing
                  } else if (event.ctrlKey) {
                    // left click creates an arrow
                    if ("left" == button) this.createAnnotation("arrow", point);
                  } else if(event.shiftKey) {
                    // left click creates an ellipse
                    if ("left" == button) this.createAnnotation("ellipse", point);
                  } else {
                    // left click grabs hovered annotations (if none are already active)
                    if ("left" == button && null == this.activeAnnotation) {
                      this.updateActiveAnnotation(point);
                    }
                  }
                },

                onmouseup: async (event) => {
                  let button = null;
                  if (0 == event.button) button = "left";
                  else if (1 == event.button) button = "middle";
                  else if (2 == event.button) button = "right";

                  // save the annotation if we're letting go of an active annotation
                  if ("left" == button && null != this.activeAnnotation) {
                    await this.saveActiveAnnotation();
                  }
                },

                onwheel: (event) => {
                  event.preventDefault();
                  event.stopPropagation();

                  // send the center point that we are scaling in on in percent [0,1]
                  const rect = this.canvas.getBoundingClientRect();
                  this.scale(event.layerX/rect.width, event.layerY/rect.height, 0 < event.wheelDelta);
                },

                onmousemove: (event) => {
                  let button = null;
                  if (1 == event.buttons) button = "left";
                  else if (4 == event.buttons) button = "middle";
                  else if (2 == event.buttons) button = "right";

                  const point = this.canvasToImage(event.clientX, event.clientY);
                  if (null == button) {
                    // update which annotation is behing hovered over
                    if (!event.altKey && !event.ctrlKey && !event.shiftKey) {
                      this.updateHover(point);
                    }
                  } else if ("left" == button) {
                    // move the active annotation
                    this.transformActiveAnnotation(point);
                  } else if ("middle" == button) {
                    this.pan(-event.movementX, -event.movementY);
                  } else if ("right" == button) {
                    this.windowLevel(event.movementX, -event.movementY);
                  }
                },
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

            // only allow typists to create a new review
            getAddEnabled: function() {
              if ("review" == this.getSubjectFromState() && "list" == this.getActionFromState()) {
                // only typists can add a new review when they are looking at the review list
                return this.isRole("typist");
              } else if ("image" == this.getSubjectFromState()) {
                // only admins can add a new review when viewing and image
                return this.isRole("administrator");
              }
              return false;
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
