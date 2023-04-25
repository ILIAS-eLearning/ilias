class Data {

    #jquery;
    #params;
    #kbnav;
    #actions_registry;
    
    constructor(jquery, params, kbnav) {
        this.jquery = jquery;
        this.params = params;
        this.kbnav = kbnav;
        this.actions_registry = {};
    }

    /**
     * @param string target_id
     */
    initKeyboardNavigation(target_id) {
        this.kbnav.init(target_id);
    }

    /**
     * @param string table_id
     * @param string action_id
     * @param string type 'SIGNAL' | 'URL'
     * @param mixed target
     * @param string parameter_name
     */
    registerAction(table_id, action_id, type, target, parameter_name) {
        let r = this.actions_registry[table_id] || {};
        r[action_id] = {
            type : type,
            target : target,
            param : parameter_name
        };
        this.actions_registry[table_id] = r;
    }

    /**
     * @param string table_id
     * @param array signal_data
     * @param array row_ids
     */
    doAction(table_id, signal_data, row_ids) {
        const act_id = signal_data.options.action;
        const action = this.actions_registry[table_id][act_id];
        let target;

        if(action.type === 'URL') {
            target = this.params.amendParameterToUrl(action.target, action.param, row_ids);
            window.location.href = target;
        }
        if(action.type === 'SIGNAL') {
            target = this.params.amendParameterToSignal(action.target, action.param, row_ids);
            $('#' + table_id).trigger(
                target.id,
                {
                    'id': target.id,
                    'options': target.options
                }
            );
        }
    }

    /**
     * @param string table_id
     * @param node originator
     */
    doActionForAll(table_id, originator) {
        const actions = this.actions_registry[table_id];
        const modal_content = originator.parentNode.parentNode;
        const modal_close = modal_content.getElementsByClassName('close')[0];
        const selected_action = modal_content
            .getElementsByClassName('modal-body')[0]
            .getElementsByTagName('select')[0].value;

        if(selected_action in actions) {
            let signal_data = {options : {action : selected_action}};
            modal_close.click();
            this.doAction(table_id, signal_data, ['ALL_OBJECTS']) ;
        }
    }

    /**
     * @param string table_id
     */
    collectSelectedRowIds(table_id) {
        const table = document.getElementById(table_id);
        const cols = table.getElementsByClassName('c-table-data__row-selector');
        const ret = [];
        let col;
        let i = 0;

        for(i; i < cols.length; i = i + 1) {
            col = cols[i];
            if(col.checked) {
                ret.push(col.value);
            }
        }
        return ret;
    }
    
    /**
     * @param string table_id
     * @param bool state
     */
    selectAll(table_id, state) {
        const table = document.getElementById(table_id);
        const cols = table.getElementsByClassName('c-table-data__row-selector');
        const selector_all = table.getElementsByClassName('c-table-data__selection_all')[0];
        const selector_none = table.getElementsByClassName('c-table-data__selection_none')[0];
        let col;
        let i = 0;
        
        for(i; i < cols.length; i = i + 1) {
            col = cols[i];
            col.checked = state;
        }
        if(state) {
            selector_all.style.display='none';
            selector_none.style.display='block';
        } else {
            selector_all.style.display='block';
            selector_none.style.display='none';
        }
    }
}
export default Data;


