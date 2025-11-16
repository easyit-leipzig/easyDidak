    evaluateAdmin = function ( data ) {
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
        console.log( jsonobject, jsonobject.command );
        let el;
        switch( jsonobject.command ) {
            case "callProcedure":
                    if( jsonobject.res == 1 ) {
                                    dMNew.show( { title: "Buch speichern", type: true, text: "Die Gruppe 10 wurden auf die aktuelle Zeit gesetzt", buttons: [ { title: "Okay", action: function( args ) {} } ] } );
                        //dMNew.show( "Setze Gruppe 10", "okay", "Die Gruppe 10 wurden auf die aktuelle Zeit gesetzt", "okay" );
                    } 
            break;
        }
}
callProcedure = function(){
    data.command = "callProcedure";
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateAdmin);

}
init = function() {
    Df_1.init();
    nj( "#setGroupForTime").on( "click", function(){ callProcedure() } );
}