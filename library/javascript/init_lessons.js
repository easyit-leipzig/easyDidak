getGermanDate = function(){
    let d = new Date();
    return d.getGermanDateString();
    let gDate = d.getGermanDateString();
}
changeSchulform = function( id ){
        data = {};
        data.id = getIdAndName( id ).Id;
        data.schulformV = nj( "#df3_schulform_id_" + getIdAndName( id ).Id ).v();
        data.fachV = nj( "#df3_fach_id_" + getIdAndName( id ).Id ).v();
        data.value = nj("#" + id).v();//df3_schulform_id_new
        data.command = "getLernthema";
        console.log(data)
        nj().fetchPostNew("library/php/ajax_lesson.php", data, this.evaluateLesson);
}
changeLernthema = function( id ){
        data = {};
        data.id = id;
        data.value = nj("#" + id).v();
        let str = id.split("_");
        data.schulform = nj("#df3_schulform_id_" + str.at(-1) ).v();
        data.tmpId = str.at(-1);

        data.command = "getLerninhalt";
        nj().fetchPostNew("library/php/ajax_lesson.php", data, this.evaluateLesson);
}
setUeTeinehmer = function(  ) {
    console.log( data );
    data.id = data.primaryKeyValue;
    data.command = "setUeTeinehmer";
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
        let el, id;
        switch( jsonobject.command ) {
            case "getLernthema":
                id= jsonobject.id
                el = nj().cEl("datalist");
                el.id = "df3_std_lernthema_id_" + id + "_list";
                if( nj("#df3_std_lernthema_id_" + id + "_list").isE() ) nj("#df3_std_lernthema_id_" + id + "_list").oHt("");
                nj( )
                nj(el).htm( jsonobject.lernthema );

                    nj("#df3_std_lernthema_id_" + id ).atr("list", "df3_std_lernthema_id_" + id + "_list");
                    if( !nj("#df3_std_lernthema_id_" + id + "_list").isE() ) nj("#df3_std_lernthema_id_" + id ).a(el);
                    
                break;
            case "getLerninhalt":
                if( nj( "#df3_thema_" + jsonobject.id + "_list").isE() ) nj( "#df3_thema_" + jsonobject.id + "_list").oHt("");
                el = nj().cEl("datalist");
                el.id = "df3_thema_" + jsonobject.id + "_list";
                nj(el).htm( '' );
                nj(el).htm( jsonobject.lerninhalt );

                    nj("#df3_thema_" + jsonobject.id + "").atr("list", "df3_thema_" + jsonobject.id + "_list");

                    if( !nj("#df3_thema_" + jsonobject.id + "_list").isE()) nj("#df3_thema_" + jsonobject.id + "").a(el);;
                    
                break;
            case "setUeTeinehmer":
            break;
        }
}
setTooltipsBewertung = function() {

}
setTooltipsBewUe = function() {
    let l = Df_2.opt.fieldDefinitions.length;
    let i = 0;
    while( i < l ) {
        if( typeof Df_2.opt.fieldDefinitions[i].title !== "undefined" ) {

        tippy( "input[id^=df2_" + Df_2.opt.fieldDefinitions[i].field + "]", {
            content:  "<div>" + Df_2.opt.fieldDefinitions[i].title + "</div>"
                +  "<div class='tippy_small'></div>" ,
            allowHTML: true,    
        })
        }
        nj("input[id^=df2_" + Df_2.opt.fieldDefinitions[i].field + "]").rAt( "title" );
        i += 1;
    }
}
setBewTn = function(){
    let l = Df_6.opt.fieldDefinitions.length;
    let i = 0;
    let str;
    while( i < l ) {
        if( typeof Df_6.opt.fieldDefinitions[i].title !== "undefined" ) {
        str = Df_6.opt.fieldDefinitions[i].title.split( "&#10;" )
        if( str.length === 2 ) {

            tippy( "input[id^=df6_" + Df_6.opt.fieldDefinitions[i].field + "]", {
                content:  "<div>" + str[0] + "</div>"
                    +  "<div class='tippy_small'>" + str[1] + "</div>" ,
                allowHTML: true,    
            })
            }
        }
        nj("input[id^=df6_" + Df_2.opt.fieldDefinitions[i].field + "]").rAt( "title" );
        i += 1;
    }

}
setMtrLeistung = function( el ) {
    let v = nj("#df4_teilnehmer_id_" + Df_4.opt.currentRecord).v();
    nj("#df8_teilnehmer_id_new" ).v( v );
    data.tn = nj("#df4_teilnehmer_id_" + Df_4.opt.currentRecord).v();
    let l = Df_8.opt.fieldDefinitions.length;
    let i = 0;
    while( i < l ) {
        tippy( "input[id^=df8_" + Df_8.opt.fieldDefinitions[i].field + "]", {
            content:  "<div>" + Df_8.opt.fieldDefinitions[i].title + "</div>"
                +  "<div class='tippy_small'>Test</div>" ,
            allowHTML: true,    
        })
        //nj("input[id^=df8_" + Df_8.opt.fieldDefinitions[i].field + "]").rAtr( "title" );
        i += 1;
    }
}
getAVG = function( el ) {
    data.id = el.id;
    data.command = "getAVG";
    nj().fetchPostNew("library/php/ajax_lesson.php", data, this.evaluateLesson);

} 
init = function() {
    nj( "#std_teilnehmer" ).m( "#myDia")
    nj( "#tln_bewertung" ).m( "#myDia")
    nj("#showTN").on("click", function(){
        myDia.show();
    })
//setTooltipsBewertung();
}