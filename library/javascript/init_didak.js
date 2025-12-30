var Df_3;
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
                    //console.log( jsonobject.currentDate.date.substring(0, 19) );
                    data.ueId = jsonobject.ueId;
                        nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
                break;
            case "getUeData":
                    Df_3.opt.fieldDefinitions[2].default = jsonobject.ueId;
                    Df_3.opt.fieldDefinitions[3].default = jsonobject.datum.substring(0, 16);
                     Df_3.opt.fieldDefinitions[5].default = jsonobject.r_tn[0][0];
                    Df_3.opt.fieldDefinitions[10].options = jsonobject.lernthemen;
                    Df_3.opt.fieldDefinitions[13].options = jsonobject.r_tn[0]["Klassenstufe"];
                    Df_3.opt.fieldDefinitions[14].options = jsonobject.r_tn[0]["Klassentyp"];
                    Df_3.opt.filter = "ue_id=" + jsonobject.ueId  + " and tn_id=" + nj( "#df2_teilnehmer_id_new").v(),
                    Df_3.init();
                    Df_3.dDF.show();
                break;
            case "getLernthemenData":
                    tmp = "#df3_std_lernthema_id_" + jsonobject.ueId;
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
                    if( nj( "#list_df3_std_lernthema_id_new" ).htm() != jsonobject.lernthemen ) nj( "#list_df3_std_lernthema_id_new" ).htm( jsonobject.lernthemen );
                break;
            case "getThemenPerFach":
                    nj( "#list_df3_std_lernthema_id_" + jsonobject.Id ).htm( jsonobject.lernthemen );
                break;
            case "getThemenDataFromLernthema":
                    nj( "#list_df3_thema_" + jsonobject.ueId ).htm( jsonobject.unterThemen );
            break;
        }
}
changeFach = function( el ) {
     data.command = "changeFach";
    let Id = getIdAndName( el.id ).Id;
    data.tn = nj("#df3_tn_id_" + Id ).v();
    data.ueId = nj( "#df3_ue_id_" + Id ).v();
    data.schulform = nj( "#df3_schulform_id_" + Id ).v();
    nj( "#df3_std_lernthema_id_" + Id ).v( "" );
    let fach = el.value;
    data.fach_id = fach;
    console.log( data );
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
}
setFieds = function(el) {
    data.id = getIdAndName( el.id ).Id;
    data.tn = nj( "#df3_teilnehmer_id_" + data.id ).v();
    data.fach = nj( "#df3_fach_id_" + data.id ).v();
    data.ueId = nj( "#df3_ue_unterrichtseinheit_id_" + data.id ).v();
    nj( "#df3_teilnehmer_id_new" ).v( data.tn );
    nj( "#df3_fach_id_new" ).v( data.fach );
    nj( "#df3_ue_unterrichtseinheit_id_new" ).v( data.ueId );
}
changeLernthema = function( el ) {
    console.log( el );
    data.command = "getThemenFromLernfeld";
    data.Id = getIdAndName( el.id ).Id;
    data.ueId = nj("#df3_ue_id_" + data.Id ).v();
    data.thema = el.value;
    console.log( data );
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
}
setTnInNew = function ( el ) {
    let tmp, d = {};
    tmp = el.filter.split( "=" );
    d.ueId = tmp[1];
    d.command = "correctTnInZuwTh";
    d.id = nj( "#df3_id_" + tmp[1] ).v();
    console.log( d );
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
    data.newUeId = args.jsonobject.newId;
    data.command = "setGroup";
    nj().fetchPostNew("library/php/ajax_didak.php", data, this.evaluateDidak);
}
nj("#df1_search_id").on("change", function(){ console.log(nj("#df1_search_id").v());    nj("#df2_teilnehmer_id_new").v( nj( "#df1_search_id").v())})

//setTooltips()