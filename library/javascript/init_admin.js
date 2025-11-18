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
                                    dMNew.show( { title: "Buch speichern", type: true, text: "Die Gruppe 10 wurden auf die aktuelle Zeit gesetzt" } );
                        //dMNew.show( "Setze Gruppe 10", "okay", "Die Gruppe 10 wurden auf die aktuelle Zeit gesetzt", "okay" );
                    } 
            break;
        }
}
callProcedure = function(){
    data.command = "callProcedure";
    nj().fetchPostNew("library/php/ajax_admin.php", data, this.evaluateAdmin);

}
rewriteSQL = function(){
    data.command = "rewriteSQL";
    nj().fetchPostNew("library/php/ajax_admin.php", data, this.evaluateAdmin);

}
copySQL = function(){
    data.command = "copySQL";
    data.withDrop = nj( "#withDrop").chk();
    nj().fetchPostNew("library/php/ajax_admin.php", data, this.evaluateAdmin);

}
init = function() {
    Df_1.init();
    Df_2.init();
    nj( "#setGroupForTime").on( "click", function(){ callProcedure() } );
    nj( "#rewriteSQL").on( "click", function(){ rewriteSQL() } );
    nj( "#copySQL").on( "click", function(){ copySQL() } );
}
rsOnFocus = function() {
    console.log( this );
}