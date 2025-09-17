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
    try {

  let myTooltip_1 = new Opentip("#df6_offenheit_erfahrungen_new", "Wie aktiv beteiligst du dich am Unterricht (Fragen stellen, Antworten geben, mitdenken)?", "Mitarbeit");

} catch (err) {

  return;

}
/*
    let myTooltip_1 = new Opentip("#df2_val_mitarbeit_new", "Wie aktiv beteiligst du dich am Unterricht (Fragen stellen, Antworten geben, mitdenken)?", "Mitarbeit");
    let myTooltip_2 = new Opentip("#df2_val_absprachen_new", "Hältst du dich an Absprachen mit dem Tutor (z. B. Formfragen, vereinbarte Ziele, Termine)?", "Absprachen");
    let myTooltip_3 = new Opentip("#df2_val_selbststaendigkeit_new", "Wie gut kannst du Aufgaben alleine bearbeiten, ohne ständig Hilfe zu brauchen?", "Selbständigkeit");
    let myTooltip_4 = new Opentip("#df2_val_konzentration_new", "Wie aufmerksam und fokussiert arbeitest du während der Stunde?", "Konzentration");
    let myTooltip_5 = new Opentip("#df2_val_fleiss_new", "Wie viel Mühe und Einsatz bringst du in die Bearbeitung der Aufgaben ein?", "Fleiss");
    let myTooltip_6 = new Opentip("#df2_val_lernfortschritt_new", "Hast du das Gefühl, dass du dich in den behandelten Themen verbesserst?", "Lernfortschritt");
    let myTooltip_7 = new Opentip("#df2_val_beherrscht_thema_new", "Kannst du das aktuelle Thema am Ende der Einheit sicher anwenden und erklären?", "beherrsche Thema");
    let myTooltip_8 = new Opentip("#df2_val_transferdenken_new", "Schaffst du es, das Gelernte auch in neuen Aufgaben oder Situationen zu nutzen?", "Transferdenken");
    let myTooltip_9 = new Opentip("#df2_val_basiswissen_new", "Verfügst du über das nötige Grundwissen, um neue Inhalte zu verstehen und darauf aufzubauen?", "Basiswissen");
    let myTooltip_10 = new Opentip("#df2_val_vorbereitet_new", "Kommst du vorbereitet in die Stunde (Hausaufgaben, Materialien, Vorwissen)?", "Vorbereitung");
    let myTooltip_11 = new Opentip("#df2_val_themenauswahl_new", "Empfindest du die behandelten Themen als sinnvoll und für dich passend?", "Themenauswahl");
    let myTooltip_12 = new Opentip("#df2_val_materialien_new", "Helfen dir die eingesetzten Materialien (Arbeitsblätter, Darstellungen, Beispiele) beim Lernen?", "Materialien");
    let myTooltip_13 = new Opentip("#df2_val_methodenvielfalt_new", "Empfindest du die eingesetzten Methoden (Erklärungen, Übungen, Visualisierungen) als abwechslungsreich und hilfreich?", "Methodenvielfalt");
    let myTooltip_14 = new Opentip("#df2_val_individualisierung_new", "Hast du das Gefühl, dass der Unterricht auf deine persönlichen Stärken und Schwächen eingeht?    ", "Individualisierung");
    let myTooltip_15 = new Opentip("#df2_val_aufforderung_new", "Wirst du vom Tutor ausreichend ermutigt und aufgefordert, dich aktiv einzubringen?", "Aufforderung");
    let myTooltip_16 = new Opentip("#df2_val_emotions_new", "Gib bitte ein oder mehrere Gefühle an, die du während der Nachhilfe hattest.", "Emotionen");
    let myTooltip_17 = new Opentip("#df2_bemerkungen_new", "Du kannst hier eine Textnachricht hinterlassen.", "Bemerkungen");
*/
}
setTooltipsBewUe = function() {
    tippy('input[id^=df2_mitarbeit_]', {
      content: `Mitarbeit
wie war die Mitarbeit`,
    });
    tippy('input[id^=df2_absprachen_]', {
      content: "Absprachen",
    });
}
setMtrLeistung = function( el ) {

    let v = nj("#df4_teinehmer_id_" + Df_4.opt.currentRecord).v();
    nj("#df8_teilnehmer_id_new" ).v( v );
    nj("input[id^=df8_note_").atr("step", "0.1");
    console.log( Df_4 );
    
    data.tn = nj("#df4_teinehmer_id_" + Df_4.opt.currentRecord).v();
    console.log( data.tn );
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
init = function() {
    nj( "#std_teilnehmer" ).m( "#myDia")
    nj( "#tln_bewertung" ).m( "#myDia")
    nj("#showTN").on("click", function(){
        myDia.show();
    })
//setTooltipsBewertung();
}