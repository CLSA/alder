const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_base_element } = await import(`${CENOZO_URL}/js/element/base_element.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/home.mjs`);

export class CN_model_home extends classes.CN_model_home {
  /** 
   * Extend parent method
   */
  _create_element() {
    const el = super._create_element();

    const config_el = el.querySelector("div[name=config]");
    
    const row_el = CN_base_element.html('<div class="row ms-3"></div>');
    CN_element_label.append(row_el, {
      for: "apex_user_mode",
      value: "Review Mode:",
      class: "col-sm-3",
    });
    row_el.querySelector("label").classList.remove("text-end");
    CN_input_enum.append(row_el, {
      id: "apex_user_mode",
      required: true,
      class: "col-sm-9",
      get_default: () => CN_session.get("user", "apex_user") ? 1 : 0,
      enum: {
        values: [
          { key: 1, value: "Apex Re-Analysis" },
          { key: 0, value: "Quality Assessment" },
        ],
      },
      on_change: async (form_input) => {
        await CN_api.patch("self/0", { apex_user: form_input.get_value() });
        CN_session.reload();
      },
    });

    config_el.append(row_el);

    return el;
  }
}
