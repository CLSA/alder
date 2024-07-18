cenozoApp.defineModule({
  name: "review",
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
        singular: "review",
        plural: "reviews",
        possessive: "review's",
      },
      columnList: {
        user: {
          column: "user.name",
          title: "Reviewer",
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
      image_type: {
        title: "Type",
        type: "string",
        isConstant: true,
      },
      rating: {
        title: "Rating",
        type: "string",
        isConstant: true,
      },
      note: {
        column: "image.note",
        title: "Image Notes",
        type: "text",
      },
      feedback: {
        title: "Feedback",
        type: "text",
      },
      image_id: { type: "hidden" },
    });

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
