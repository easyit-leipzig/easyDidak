    evaluateDidak = function ( data ) {
        // content
        let jsonobject, el, l, i, m, j, tmp, decVal, strVal;
        if( typeof data === "string" ) {
            jsonobject = JSON.parse( data );
        } else {
            jsonobject = data;
        }
        if( !nj().isJ( jsonobject ) ) {
            throw "kein JSON-Objekt übergeben";
        }
        console.log( jsonobject );
        tmp = "zielwert_lernfortschritt,zielwert_beherrscht_thema,zielwert_transferdenken,zielwert_basiswissen,zielwert_vorbereitet,zielwert_note";
        switch( jsonobject.command ) {
            case "getAVG":
                l = Object.keys( jsonobject.res[0]).length;
                i = 0;
                while( i < l ) {
                    nj( "#" + Object.keys( jsonobject.res[0])[i] ).v( jsonobject.res[0][Object.keys( jsonobject.res[0])[i]] )
                    i += 1;
                }
                nj("#startDate").v( jsonobject.dates[0].minDate.substring(0,10));
                nj("#endDate").v( jsonobject.dates[0].maxDate.substring(0,10));
                nj("#anz").v( jsonobject.dates[0].anz );
                el = tmp.split( "," );
                l= el.length
                i=0
                while( i < l ) {
                    m = el[i].split( "_");
                    if(m.length===3) m[1] = "beherrscht_thema"
                    decVal = nj( "#" + m[1] ).v() - 0.2;
                    if(decVal<1) decVal = 1;
                    console.log( decVal )
                    nj("#" + el[i]).v( decVal )
                    i += 1;
                }
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
nj("#getAVG").on("click", function(){getAVG()})
nj("#setGroup").on("click", function(){setGroup()})
//setTooltips()