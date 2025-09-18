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

getAVG = function() {
    data.tn = nj("#selTN").v();
    data.sDate = nj("#startDate").v();
    data.eDate = nj("#endDate").v();
    data.command = "getAVG";
    nj().fetchPostNew("library/php/ajax_trends.php", data, this.evaluateDidak);
}
setGroup = function() {
    data.id = 41;
    data.command = "getAVG";
    nj().fetchPostNew("library/php/ajax_trends.php", data, this.evaluateDidak);
}
nj("#selTN").htm( list_teilnehmer );
nj("#getAVG").on("click", function(){getAVG()})
nj("#setGroup").on("click", function(){setGroup()})
//setTooltips()