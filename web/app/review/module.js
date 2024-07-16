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
        manual_rating: {
          title: "Manual Rating",
        },
        calculated_rating: {
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
      calculated_rating: { type: "hidden" },
      manual_rating: { type: "hidden" },
    });

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
