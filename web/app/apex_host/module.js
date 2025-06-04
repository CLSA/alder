cenozoApp.defineModule({
  name: "apex_host",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: { column: "db_address" },
      name: {
        singular: "apex host",
        plural: "apex hosts",
        possessive: "apex host's",
      },
      columnList: {
        user: { column: "user.name", title: "User" },
        db_address: { title: "DB address" },
        ssh_address: { title: "SSH address" },
      },
      defaultOrder: {
        column: "apex_host.db_address",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      user_id: {
        title: "User",
        type: "enum",
      },
      db_address: {
        title: "MSSQL Address",
        type: "string",
      },
      db_username: {
        title: "MSSQL Username",
        type: "string",
        help: "The password is stored in the application's configuration file.",
      },
      ssh_address: {
        title: "SSH Address",
        type: "string",
      },
      ssh_username: {
        title: "SSH Username",
        type: "string",
        help: "No password is required since a keyfile is used instead.",
      },
    });

    module.addExtraOperation("view", {
      title: "Check Status",
      operation: async function ($state, model) {
        await model.viewModel.checkStatus();
      },
      isDisabled: function($state, model) {
        return model.viewModel.checkingStatus;
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexHostViewFactory", [
      "CnBaseViewFactory",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (CnBaseViewFactory, CnHttpFactory, CnModalMessageFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "image");

          angular.extend(this, {
            checkingStatus: false,
            checkStatus: async function() {
              this.checkingStatus = true;

              try {
                const response = await CnHttpFactory.instance({
                  path: "apex_host/" + this.record.id,
                  data: { select: { column: "status" } },
                }).get();

                const status = JSON.parse(response.data.status);
                let message = "<ul>";
                for(let key in status) {
                  message += "<li>" + key + ": " + (status[key] ? "online" : "offline") + "</li>";
                }
                message += "</ul>";
                await CnModalMessageFactory.instance({
                  title: "Apex Host Status",
                  message: message,
                  html: true,
                }).show();
              } finally {
                this.checkingStatus = false;
              }
            },
          });

          async function init(object) {
            await object.deferred.promise;

            // do not allow images to be edited from this view
            if (angular.isDefined(object.imageModel)) {
              object.imageModel.getChooseEnabled = function () { return false; }
            }
          }

          init(this);
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnApexHostModelFactory", [
      "CnBaseModelFactory",
      "CnApexHostAddFactory",
      "CnApexHostListFactory",
      "CnApexHostViewFactory",
      "CnHttpFactory",
      function (
        CnBaseModelFactory,
        CnApexHostAddFactory,
        CnApexHostListFactory,
        CnApexHostViewFactory,
        CnHttpFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            addModel: CnApexHostAddFactory.instance(this),
            listModel: CnApexHostListFactory.instance(this),
            viewModel: CnApexHostViewFactory.instance(this, root),

            getMetadata: async function() {
              await this.$$getMetadata();

              const response = await CnHttpFactory.instance({
                path: "user",
                data: {
                  select: { column: ["id", "name", "first_name", "last_name"] },
                  modifier: { where: { column: "apex_user.id", operator: "!=", value: null } },
                },
              }).query();

              this.metadata.columnList.user_id.enumList = response.data.reduce((list, item) => {
                list.push({
                  value: item.id,
                  name: item.first_name + " " + item.last_name + " (" + item.name + ")",
                });
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
