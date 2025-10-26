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
            case "setGroup":
                break;
        }
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