//
function ciniki_tutorials_settings() {
    this.toggleOptions = {'no':'Hide', 'yes':'Display'};
    this.yesNoOptions = {'no':'No', 'yes':'Yes'};
    this.viewEditOptions = {'view':'View', 'edit':'Edit'};
    this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};
    this.weightUnits = {
        '10':'lb',
        '20':'kg',
        };

    this.init = function() {
        //
        // The menu panel
        //
        this.menu = new M.panel('Settings',
            'ciniki_tutorials_settings', 'menu',
            'mc', 'narrow', 'sectioned', 'ciniki.tutorials.settings.menu');
        this.menu.sections = {
            'image':{'label':'Header Image', 'fields':{
                'coverpage-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tutorials_settings.settingsSave();'},
                }},
        };
        this.menu.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sapos.settingsHistory', 
                'args':{'tnid':M.curTenantID, 'setting':i}};
        }
        this.menu.fieldValue = function(s, i, d) {
            if( this.data[i] == null && d.default != null ) { return d.default; }
            return this.data[i];
        };
        this.menu.addDropImage = function(iid) {
            M.ciniki_tutorials_settings.menu.setFieldValue('coverpage-image', iid);
            return true;
        };
        this.menu.deleteImage = function(fid) {
            this.setFieldValue(fid, 0);
            return true;
        };
        this.menu.addButton('save', 'Save', 'M.ciniki_sapos_settings.settingsSave();');
        this.menu.addClose('Back');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tutorials_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.showMenu(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMenu = function(cb) {
        M.api.getJSONCb('ciniki.tutorials.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tutorials_settings.menu;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    };

    //
    // Save the Paypal settings
    //
    this.settingsSave = function() {
        var c = this.menu.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tutorials.settingsUpdate', {'tnid':M.curTenantID}, 
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tutorials_settings.menu.close();
                });
        } else {
            this.menu.close();
        }
    };
}
