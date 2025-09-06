getGermanDate = function(){
    let d = new Date();
    return d.getGermanDateString();
    let gDate = d.getGermanDateString();
}
changeSchulform = function( id ){
        data = {};
        data.id = id;
        data.value = nj("#" + id).v();
        data.command = "getLernthema";
        nj().fetchPostNew("library/php/ajax_lesson.php", data, this.evaluateLesson);
}
changeLernthema = function( id ){
        data = {};
        data.id = id;
        data.value = nj("#" + id).v();
        let str = id.split("_");
        data.schulform = nj("#df3_schulform_id_" + str.at(-1) ).v();
        data.command = "getLerninhalt";
        nj().fetchPostNew("library/php/ajax_lesson.php", data, this.evaluateLesson);
}
    evaluateLesson = function ( data ) {
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
            case "getLernthema":
                el = nj().cEl("datalist");
                el.id = "df3_std_lernthema_id_new_list";
                nj(el).htm( jsonobject.lernthema );

                    nj("#df3_std_lernthema_id_new").atr("list", "df3_std_lernthema_id_new_list");
                    nj("#df3_std_lernthema_id_new").a(el);
                break;
            case "getLerninhalt":
                el = nj().cEl("datalist");
                el.id = "df3_thema_new_list";
                nj(el).htm( jsonobject.lerninhalt );

                    nj("#df3_thema_new").atr("list", "df3_thema_new_list");
                    nj("#df3_thema_new").a(el);
                break;
        }
}
init = function() {
    nj( "#std_teilnehmer" ).m( "#myDia")
}