define(['mage/url'], function (url) {
    return async function getid() {
        let sfCuid = localStorage.getItem('sf_cuid') || null;
    
        if (! sfCuid) {
            sfCuid = await fetch(url.build('/salesfire/ajax/sfgetid'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .then(async response => await response.json())
            .catch(error => {
                console.error('Error: ' + error);
                return null;
            });
        }

        if (sfCuid) {
            localStorage.setItem('sf_cuid', sfCuid);
    
            window.sfDataLayer = window.sfDataLayer || [];
            window.sfDataLayer.push({
                session: {
                    id: sfCuid,
                },
            });
        }
    };
});
