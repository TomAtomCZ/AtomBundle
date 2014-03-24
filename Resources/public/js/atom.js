/**
 * 
 */
Atom = {
    init: function()
    {
        this.atoms = $('.atom');
        this.config = $('#atom-config');
        
        this._initRaptor();
    },
    
    _initRaptor: function()
    {
        this.atoms.raptor({
            initialLocale: this.config.data('locale'),
            plugins: {
                fontFamilyMenu: false,
                statistics: false,
                languageMenu: false,
                dockToElement: false,
                dockToScreen: false,
                guides: false,
                enableUi: true,
                logo: false,
                dock: {
                   docked: true
                },
                // Define which save plugin to use. May be saveJson or saveRest
                save: {
                    plugin: 'saveRest'
                },
                saveRest: {
                    // The URI to send the content to
                    url: this.config.data('save-url'),
                    // Returns an object containing the data to send to the server
                    data: function(html) {
                        return {
                            name: 'praprase',
                            body: html
                        };
                    }
                },
            }
        });
    }
}


$(function() 
{
    Atom.init();
});
