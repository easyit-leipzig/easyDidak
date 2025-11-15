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
        console.log( jsonobject, jsonobject.command );
        let el;
        switch( jsonobject.command ) {
            case "setGroup":
                    data.command = "getUeData";
                    data.ueId = jsonobject.ueId;
                    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
                break;
            case "getUeData":
                    Df_3.opt.filter = "ue_unterrichtseinheit_id=" + jsonobject.ueId;
                    Df_3.opt.fieldDefinitions[2].default = jsonobject.ueId;

                    Df_3.init();
                    Df_3.dDF.show();
                break;
            case "getLernthemenData":
                    tmp = "#df3_std_lernthema_id_" + jsonobject.ueId;
                    console.log( getIdAndName( tmp ).Id/*, nj( tmp ).hAt( "list" )*/ );
/*
                    if( getIdAndName( tmp ).Id === "undefined" ) {
                        nj( "#df3_std_lernthema_id_new").atr( "list", "list_df3_std_lernthema_id_new");
                        if ( jsonobject.lernthemen === "" ) {
                            data.ueId = nj().els( "input[id^='df3_ue_unterrichtseinheit_id_']")[0].value;
                            data.tn = nj().els( "input[id^='df3_teilnehmer_id_']")[0].value;
                            data.fach_id = nj().els( "input[id^='df3_fach_id_']")[0].value;
                            data.command = "getLernthemenDataNew";
                            //data.lernthemen = jsonobject.lernthemen;                   
                            nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
                            return;
                        }

                        el.id = "#list_df3_std_lernthema_id_new";
                        nj( "#list_df3_std_lernthema_id_new" ).htm( jsonobject.lernthemen );

                        return;
                    }
                    if( nj( tmp ).hAt( "list" ) || ( typeof jsonobject.ueId === "undefined" && nj("#df3_std_lernthema_id_new").hAt( "list" ) ) ) {
                    //console.log( tmp, nj( tmp ).hAt( "list" ) );

                        return;
                    }
*/
                    if( nj( "#list_df3_std_lernthema_id_" + jsonobject.ueId ).htm() != jsonobject.lernthemen ) {
                        nj( "#list_df3_std_lernthema_id_" + jsonobject.ueId ).htm( jsonobject.lernthemen );
                        data.ueId = nj().els( "input[id^='df3_ue_unterrichtseinheit_id_']")[0].value;
                        data.tn = nj().els( "input[id^='df3_teilnehmer_id_']")[0].value;
                        console.log( nj().els( "select[id^='df3_fach_id_']") );
                        data.fach_id = nj().els( "select[id^='df3_fach_id_']")[0].value;
                        data.command = "getLernthemenDataNew";
                        data.lernthemen = jsonobject.lernthemen;                   
                        nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
                    }
                    //if( nj( "#list_df3_std_lernthema_id_new" ).htm() != jsonobject.lernthemen ) nj( "#list_df3_std_lernthema_id_new" ).htm( jsonobject.lernthemen );
                break;
            case "getLernthemenDataNew":
                        //console.log( tmp, nj( tmp ).hAt( "list" ) );
                    strVal = jsonobject.lernthemen;
                    /*
                    el = nj().cEl( "datalist" );                    
                    el.id = "list_df3_std_lernthema_id_new";
                    try { nj( "#" + el.id ).r() } catch {}
                    tmp = "#df3_std_lernthema_id_new";
                    nj( tmp ).a( el );
                    nj( "#" + el.id ).atr( "for", "df3_std_lernthema_id_new" );
                    nj( "#" + el.id ).htm( strVal )
                    strVal = nj( Df_2.opt.fieldDefinitions[2].id).v();
                    nj( "#df3_teilnehmer_id_new" ).v( strVal );
                    */
                    if( nj( "#list_df3_std_lernthema_id_new" ).htm() != jsonobject.lernthemen ) nj( "#list_df3_std_lernthema_id_new" ).htm( jsonobject.lernthemen );

                break;
            case "getThemenPerFach":
                    nj( "#list_df3_std_lernthema_id_" + jsonobject.Id ).htm( jsonobject.lernthemen );
                break;
        }
}
setFieldOptions = function( el ) {
    console.log(el);
    data.command = "getLernthemenData";
    data.ueId = getIdAndName( el.id ).Id;
    data.fachId = nj( "#df3_fach_id_" + data.ueId ).v();
    data.tn = nj( "#df3_teilnehmer_id_" + data.ueId ).v();
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
}
setTnDf_2 = function( el ) {
    console.log(el);
}
changeFach = function( el ) {
    console.log( el );

    //data.ueId = nj().els( "input[id^='df3_ue_unterrichtseinheit_id_']")[0].value;
    data.tn = nj().els( "input[id^='df3_teilnehmer_id_']")[0].value;
    data.command = "getThemenPerFach";
    data.id = getIdAndName( el.id ).Id;
    nj( "#df3_std_lernthema_id_" + data.id ).v( "" );
    data.ueId = nj( "#df3_ue_unterrichtseinheit_id_" + data.id ).v();
    data.fachId = nj( el ).v();
    //data.tn = nj( "#df3_teilnehmer_id_" + data.id ).v();
    console.log( data );
    if( data.id === "new" && nj( "#df3_teilnehmer_id_new" ).v() === "" ) nj( "#df3_teilnehmer_id_new" ).v( data.tn ) 
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);

}
changeLernthema = function( el ) {
    console.log(el);
    data.command = "getLernthemenData";
    data.ueId = getIdAndName( el.id ).Id;
    data.value = el.value;
    console.log( data );
}
setTooltips = function() {
   nj( "#df2_einrichtung_id_new").v( nj("#df1_search_einrichtung_id" ).v() );
    try {

  let myTooltip_1 = new Opentip("#df2_mitarbeit_new", "Wie aktiv beteiligst du dich am Unterricht (Fragen stellen, Antworten geben, mitdenken)?", "Mitarbeit");

} catch (err) {

  return;

}
    let myTooltip_1 = new Opentip("#df2_mitarbeit_new", "Wie aktiv beteiligst du dich am Unterricht (Fragen stellen, Antworten geben, mitdenken)?", "Mitarbeit");
    let myTooltip_2 = new Opentip("#df2_absprachen_new", "Hältst du dich an Absprachen mit dem Tutor (z. B. Formfragen, vereinbarte Ziele, Termine)?", "Absprachen");
    let myTooltip_3 = new Opentip("#df2_selbststaendigkeit_new", "Wie gut kannst du Aufgaben alleine bearbeiten, ohne ständig Hilfe zu brauchen?", "Selbständigkeit");
    let myTooltip_4 = new Opentip("#df2_konzentration_new", "Wie aufmerksam und fokussiert arbeitest du während der Stunde?", "Konzentration");
    let myTooltip_5 = new Opentip("#df2_fleiss_new", "Wie viel Mühe und Einsatz bringst du in die Bearbeitung der Aufgaben ein?", "Fleiss");
    let myTooltip_6 = new Opentip("#df2_lernfortschritt_new", "Hast du das Gefühl, dass du dich in den behandelten Themen verbesserst?", "Lernfortschritt");
    let myTooltip_7 = new Opentip("#df2_beherrscht_thema_new", "Kannst du das aktuelle Thema am Ende der Einheit sicher anwenden und erklären?", "beherrsche Thema");
    let myTooltip_8 = new Opentip("#df2_transferdenken_new", "Schaffst du es, das Gelernte auch in neuen Aufgaben oder Situationen zu nutzen?", "Transferdenken");
    let myTooltip_9 = new Opentip("#df2_basiswissen_new", "Verfügst du über das nötige Grundwissen, um neue Inhalte zu verstehen und darauf aufzubauen?", "Basiswissen");
    let myTooltip_10 = new Opentip("#df2_vorbereitet_new", "Kommst du vorbereitet in die Stunde (Hausaufgaben, Materialien, Vorwissen)?", "Vorbereitung");
    let myTooltip_11 = new Opentip("#df2_themenauswahl_new", "Empfindest du die behandelten Themen als sinnvoll und für dich passend?", "Themenauswahl");
    let myTooltip_12 = new Opentip("#df2_materialien_new", "Helfen dir die eingesetzten Materialien (Arbeitsblätter, Darstellungen, Beispiele) beim Lernen?", "Materialien");
    let myTooltip_13 = new Opentip("#df2_methodenvielfalt_new", "Empfindest du die eingesetzten Methoden (Erklärungen, Übungen, Visualisierungen) als abwechslungsreich und hilfreich?", "Methodenvielfalt");
    let myTooltip_14 = new Opentip("#df2_individualisierung_new", "Hast du das Gefühl, dass der Unterricht auf deine persönlichen Stärken und Schwächen eingeht?    ", "Individualisierung");
    let myTooltip_15 = new Opentip("#df2_aufforderung_new", "Wirst du vom Tutor ausreichend ermutigt und aufgefordert, dich aktiv einzubringen?", "Aufforderung");
    let myTooltip_16 = new Opentip("#df2_emotions_new", "Gib bitte ein oder mehrere Gefühle an, die du während der Nachhilfe hattest.", "Gefühle");
    let myTooltip_17 = new Opentip("#df2_bemerkungen_new", "Du kannst hier eine Textnachricht hinterlassen.", "Bemerkungen");
    let myTooltip_18 = new Opentip("#df2_zielgruppen_new", "Wie wurde durch das Lernthema und die zur Verfügung gestellten Materialien deine Zielgruppe getroffen.", "Zielgruppe");
}
setGroup = function() {
    data.id = args.jsonobject.newId;
    data.command = "setGroup";
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
}
nj("#df1_search_id").on("change", function(){ console.log(nj("#df1_search_id").v());    nj("#df2_teilnehmer_id_new").v( nj( "#df1_search_id").v())})

//setTooltips()