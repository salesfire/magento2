define(['mage/url'], function (url) {
    return async function getid() {
        let sfCuid = localStorage.getItem('sf_cuid') || null;
    
        if (! sfCuid) {
            const response = await fetch(url.build('/salesfire/ajax/getid'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .catch(error => {
                console.error('Error: ' + error);
            });

            sfCuid = await response.json();
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
