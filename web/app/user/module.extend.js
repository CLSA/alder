cenozoApp.extendModule({
  name: "user",
  create: (module) => {
    // apex users don't need the site column
    delete module.columnList.site_list;

    module.addInput("", "apex_user", {
      title: "Apex User",
      type: "boolean",
      help: "Whether the user should see Apex reviews instead of standard reviews.",
    } );
  },
});
