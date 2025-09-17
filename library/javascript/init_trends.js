    evaluateDidak = function ( data ) {
        // content
        let jsonobject, l, i, m, j, tmp, decVal, strVal;
        if( typeof data === "string" ) {
            jsonobject = JSON.parse( data );
        } else {
            jsonobject = data;
        }
        if( !nj().isJ( jsonobject ) ) {
            throw "kein JSON-Objekt übergeben";
        }
        console.log( jsonobject );
        let el;
        switch( jsonobject.command ) {
            case "getAVG":
                break;
        }
}

setGroup = function() {
    data.id = 41;
    data.command = "getAVG";
    nj().fetchPostNew("library/php/ajax_trends.php", data, this.evaluateDidak);
}
nj("#setGroup").on("click", function(){setGroup()})
//setTooltips()