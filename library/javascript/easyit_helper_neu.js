// JavaScript Document
Date.isLeapYear = function (year) { 
    return (((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0)); 
};
Date.getDaysInMonth = function (year, month) {
    return [31, (Date.isLeapYear(year) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
};
Date.prototype.isLeapYear = function () { 
    return Date.isLeapYear(this.getFullYear()); 
};
Date.prototype.getDaysInMonth = function () { 
    return Date.getDaysInMonth(this.getFullYear(), this.getMonth());
};
Date.prototype.addMonths = function (value) {
    var n = this.getDate();
    this.setDate(1);
    this.setMonth(this.getMonth() + value);
    this.setDate(Math.min(n, this.getDaysInMonth()));
    return this;
};
Date.prototype.addDays = function(days) {
    var date = new Date(this.valueOf());
    date.setDate(date.getDate() + days);
    return date;
}
Date.prototype.addHours = function(h) {
  this.setTime(this.getTime() + (h*60*60*1000));
  return this;
}
Date.prototype.daysDiff = function( date ) {
    var dateFrom = new Date(this.valueOf());
    let tmp = date - dateFrom;
    return tmp / ( 24 * 3600 * 1000 );
}
Date.prototype.onlyDate = function() {
    return new Date(this.getFullYear(), this.getMonth(), this.getDate() );
}
Date.prototype.getGermanDateString = function( widthMinutes ) {
    if( !widthMinutes ) {
        return ( "0" + this.getDate() ).substr(-2) + "." + ( "0" + ( this.getMonth() + 1 ) ).substr(-2) + "." + this.getFullYear();
    } else {
        return ( "0" + this.getDate() ).substr(-2) + "." + ( "0" + ( this.getMonth() + 1 ) ).substr(-2) + "." + this.getFullYear() + " " + ("0" + this.getHours()).substr( - 2 ) + ":" + ("0" + this.getMinutes() ).substr( -2 );
    }        
}
Date.prototype.getMySQLDateString = function( widthMinutes = false ) {
    if( !widthMinutes ) {
        return this.getFullYear() + "-" + ( "0" + ( this.getMonth() + 1 ) ).substr(-2) + "-" + ( "0" + this.getDate() ).substr(-2);        
    } else {
        return this.getFullYear() + "-" + ( "0" + ( this.getMonth() + 1 ) ).substr(-2) + "-" + ( "0" + this.getDate() ).substr(-2) + " " + ( "0" + this.getHours() ).substr(-2) + ":" + ( "0" + this.getMinutes() ).substr(-2)
    }
}
Date.prototype.getFirstDayOfWeek = function() {
    const date = new Date(this);
    const day = date.getDay(); // 👉️ get day of week
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(date.setDate(diff));
}
Date.prototype.getDaysOfWeek = function( firstDayOfWeekIsSunday ) {
    let dates = {};
    if( !firstDayOfWeekIsSunday ) {
        const date = new Date( this );
        dates.firstDayOfWeek = date.getFirstDayOfWeek();
        dates.lastDayOfWeek = new Date( dates.firstDayOfWeek );
        dates.lastDayOfWeek.setDate(dates.lastDayOfWeek.getDate() + 6);
    } else {
        dates.firstDayOfWeek = new Date(this.setDate(this.getDate() - this.getDay()));
        dates.lastDayOfWeek = new Date(this.setDate(this.getDate() - this.getDay() + 6));
    }
    return dates;
}
Date.prototype.getDaysOfMonth = function(diff) {
    let dates = {};
    let monthDiff, yearDiff;
    if( !diff ) diff = 0;
    dates.firstDay = new Date( this.getFullYear(), this.getMonth() + diff, 1 );
    if( dates.firstDay.getMonth() > 12 ) {
        monthDiff = 0;
    } else {
        monthDiff = dates.firstDay.getMonth();
    }
    if( dates.firstDay.getMonth() > 12 ) {
        yearDiff = this.getFullYear() + 1;
    } else {
        yearDiff = this.getFullYear();
    }
    if( diff > 0 && dates.firstDay.getMonth() == 0 ) {
        yearDiff = this.getFullYear() + 1;
    }
    dates.lastDay = new Date( yearDiff, monthDiff, dates.firstDay.getDaysInMonth() );
    return dates;
}
Date.prototype.getMonthName = function() {
    switch( this.getMonth() ) {
        case 0:
                return "Januar";
        break;
        case 1:
                return "Februar";
        break;
        case 2:
                return "März";
        break;
        case 3:
                return "April";
        break;
        case 4:
                return "Mai";
        break;
        case 5:
                return "Juni";
        break;
        case 6:
                return "Juli";
        break;
        case 7:
                return "August";
        break;
        case 8:
                return "September";
        break;
        case 9:
                return "Oktober";
        break;
        case 10:
                return "November";
        break;
        case 11:
                return "Dezember";
        break;
    }
}
Date.prototype.getWeekDayName = function() {
    switch( this.getDay() ) {
        case 0:
                return "Sonntag";
        break;
        case 1:
                return "Montag";
        break;
        case 2:
                return "Dienstag";
        break;
        case 3:
                return "Mittwoch";
        break;
        case 4:
                return "Donnerstag";
        break;
        case 5:
                return "Freitag";
        break;
        case 6:
                return "Samstag";
        break;

    }    
}
Date.prototype.getTS = function() {
    return + new Date();
}
const getCurrentWeek = function( date = new Date() ) {
    let result = {};
    if( date.getDay() === 0 ) {
        result.from = getMysqlDate( addDaysToDate(date, -6 ) );
        result.to = getMysqlDate( addDaysToDate(date, 0 ) );
    } else {
        result.from = getMysqlDate( addDaysToDate(date, 1 - date.getDay() ) );
        result.to = getMysqlDate( addDaysToDate(date, ( 1 - date.getDay() ) + 6 ) );

    }
    return result;
}
const getNextWeek = function() {
    let result = {};
    let tmp = getCurrentWeek(); 
    result.from = getMysqlDate( addDaysToDate(new Date( tmp.from ), 7 ) );
    result.to = getMysqlDate( addDaysToDate(new Date( tmp.to ), 7 ) );
    return result;
}
const getLastWeek = function() {
    let result = {};
    let tmp = getCurrentWeek(); 
    result.from = getMysqlDate( addDaysToDate(new Date( tmp.from ), -7 ) );
    result.to = getMysqlDate( addDaysToDate(new Date( tmp.to ), -7 ) );
    return result;
}
const getLastMonth = function() {
    let result = {};
    let tmp = getCurrentMonth();
    result.from = getMysqlDate( new Date( tmp.from ).addMonths( -1 ) );
    result.to = getMysqlDate( new Date( tmp.to ).addMonths( -1 ) );
    return result;
}
const getCurrentMonth = function() {
    let result = {};
    result.from = new Date().getMonth();
    result.from = new Date().getFullYear() + "-" + (result.from + 1) + "-01";
    result.to = getMysqlDate( new Date( result.from ).addMonths(1).addDays( -1 ) );
    return result;
}
const getNextMonth = function() {
    let result = {};
    let tmp = getCurrentMonth();
    result.from = getMysqlDate( new Date( tmp.from ).addMonths( 1 ) );
    result.to = getMysqlDate( new Date( tmp.to ).addMonths( 1 ) );
    return result;
}
Array.prototype.sortNCSensitive = function() {
    //arr.sort((a,b) => (a.Value.toLocaleLowerCase() > b.Value.toLocaleLowerCase()) ? 1 : ((b.Value.toLocaleLowerCase() > a.Value.toLocaleLowerCase()) ? -1 : 0)); 
    this.sort(function(x,y){
      var a = String(x).toUpperCase();
      var b = String(y).toUpperCase();
      if (a > b)
         return 1
      if (a < b)
         return -1
      return 0;
    });
    return this;
}
// empty function
//     
const emptyFunc = function() {    
}
//
//get a random String with length 11
//
const randomString = () => Math.random().toString(36).slice(2);
//
//escape HTML strings
const escape = (str) => str.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
//
//uppercase the first character of each word in a string
const uppercaseWords = (str) => str.replace(/^(.)|\s+(.)/g, (c) => c.toUpperCase());
//
//convert a string to camelCase
const toCamelCase = (str) => str.trim().replace(/[-_\s]+(.)?/g, (_, c) => (c ? c.toUpperCase() : ''));
//
//remove duplicate values in an array
const removeDuplicates = (arr) => [...new Set(arr)];
//
//flatten an array
//e.g. flat(['cat', ['lion', 'tiger']]) // ['cat', 'lion', 'tiger']
const flat = (arr) => arr.reduce((a, b) => (Array.isArray(b) ? [...a, ...flat(b)] : [...a, b]), []);
//
//remove falsy values from array
const removeFalsy = (arr) => arr.filter(Boolean);
//
//check if a number is even or odd
const isEven = num => num % 2 === 0;
//
//get a random integer between two numbers
const random = (min, max) => Math.floor(Math.random() * (max - min + 1) + min)
//
//get the average value of arguments
const average = (...args) => args.reduce((a, b) => a + b) / args.length;
//
//truncate a number to a fixed decimal point
//e.g. round(1.005, 2) --> 1.01 // round(1.555, 2) --> 1.56
const round = (n, d) => Number(Math.round(n + "e" + d) + "e-" + d);
//
//calculate the number of different days between two dates
const diffDays = (date, otherDate) => Math.ceil(Math.abs(date - otherDate) / (1000 * 60 * 60 * 24));
//
//get the day of the year from a date
const dayOfYear = (date) => Math.floor((date - new Date(date.getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24));
//
//generate a random hex colour
const randomColor = () => `#${Math.random().toString(16).slice(2, 8).padEnd(6, '0')}`;
//
//convert RGB colour to hex
const rgbToHex = (r, g, b) => "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
//
//clear all cookies
const clearCookies = () => document.cookie.split(';').forEach((c) => (document.cookie = c.replace(/^ +/, '').replace(/=.*/, `=;expires=${new Date().toUTCString()};path=/`)));
//
//detect dark mode
const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
//
//get NodeList/HTML-collection as array
const asArray = function( arr ) {
    return Array.from( arr );
}
//
//create objec from array // const arr = [["value_0", 1], ["value_1", "firstname"] ]
const arrToObject = function( arr ) {
    try {
        return Object.fromEntries( arr );
    } catch (error) {
        console.log( error );
        return false;
    }
}
//
// replaces all "new line" characters contained in `someString` with the given `replacementString`
const replaceNewLineChars = ((someString, replacementString = ``) => { // defaults to just removing
    const LF = `\u{000a}`; // Line Feed (\n)
    const VT = `\u{000b}`; // Vertical Tab
    const FF = `\u{000c}`; // Form Feed
    const CR = `\u{000d}`; // Carriage Return (\r)
    const CRLF = `${CR}${LF}`; // (\r\n)
    const NEL = `\u{0085}`; // Next Line
    const LS = `\u{2028}`; // Line Separator
    const PS = `\u{2029}`; // Paragraph Separator
    const lineTerminators = [LF, VT, FF, CR, CRLF, NEL, LS, PS]; // all Unicode `lineTerminators`
    let finalString = someString.normalize(`NFD`); // better safe than sorry? Or is it?
    for (let lineTerminator of lineTerminators) {
        if (finalString.includes(lineTerminator)) { // check if the string contains the current `lineTerminator`
            let regex = new RegExp(lineTerminator.normalize(`NFD`), `gu`); // create the `regex` for the current `lineTerminator`
            finalString = finalString.replace(regex, replacementString); // perform the replacement
        };
    };
    return finalString.normalize(`NFC`); // return the `finalString` (without any Unicode `lineTerminators`)
});
//
// Promise
// e.g. wait(3000).then(() => console.log('Hello!')); or wait(3000).then(() => callback())
const wait = time => new Promise((resolve) => setTimeout(resolve, time));
// 
// copy to clipboard
const copyToClipboard = (text) => navigator.clipboard?.writeText && navigator.clipboard.writeText(text);
// 
// check if object is empty
var objIsEmpty = function( obj ) { return Object.keys( obj ).length === 0; }
//
//get unique array
const getUniqueArr = (arr) => [...new Set(arr)];
var setMessageStandardSize = function(){
    nj("#message").sty( "height", "110px" );
    nj("#mesBox").sty( "height", "90px" );
    nj("#message").sty( "width", "295px" );
    nj(".mesCon" ).sty( "height", "100px" );
}
var showNewMessage = function( title, type, content, buttons, height, width ) {
    let img;
    nj( "#message_headlineTitle" ).htm( title );
    let html = '';
    html += '<img id="message_img" class="mesImg">';
    html += '<div id="message_content" class="mesCon">' + content + '</div>';
    nj("#message").htm( html );
    switch (type) {
        case true:
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "okay.png" );
        break;
        case false:
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "error.png" );
        break;
        case "info":
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "info.png" );
        break;
        case "yesno":
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "question.png" );
        break;
        case "empty":
            nj( "#message_img" ).rEl();
        break;                   
        case "attention":
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "attention.png" );
        break;
        case "wait":
            nj( "#message_img" ).atr( "src", PATH_TO_ICONS + "wait.gif" );
        break;
        case "content":
            html = '<div id="message_content">' + content + '</div>';
            nj("#message").htm( html );
        break;
        default:    
        break;
    }
    if( typeof( buttons ) != "undefined" ) {
        nj("#message_footer").htm( "" );
        buttons.forEach( function( but ){
                let b = nj().cEl( "input" );
                b.value = but.title;
                b.type = "button";
                if( typeof but.action === "function" ) {
                    nj( b ).on( "click", but.action() );
                } else {
                    b.setAttribute( "onclick", but.action );
                }
                nj().els("#message_footer").appendChild( b );            
        });    
    } else {
        nj("#message_footer").htm( "" );
        let b = nj().cEl( "input" );
        b.value = "Okay";
        b.type = "button";
        nj( b ).on( "click", function() {
            dM.hide();    
        })
        //b.setAttribute( "onclick", "dM.hide()" );
        nj().els("#message_footer").appendChild( b );            
    }
    if( typeof( height ) == "undefined" ) {
        nj("#message").sty( "height", "110px" );
    } else {
        nj("#message").sty( "height", height );
        nj("#mesBox").sty( "height", ( parseInt( height ) - 20 ) + "px" );
    }
    let w;
    if( typeof( width ) == "undefined" ) {
        if( window.innerWidth < 319 ) {
            nj("#message_box").sty( "width", "270px" );
            w = "255px";

        } else if( window.innerWidth < 359) {
            nj("#message_box").sty( "width", "290px" );
            w = "275px";

        } else {
            nj("#message_box").sty( "width", "310px" );
            w = "295px";
        }
    } else {
        nj("#message_box").sty( "width", width );
        w = parseInt( width ) - 20 + "px";
        console.log( w );
    }
    nj("#message").sty( {"display": "flex", width: w, "align-items": "center" } );
    dM.show();
}
var temporarySwap = function(array) {
    var left = null;
    var right = null;
    var length = array.length;
    for (left = 0, right = length - 1; left < right; left += 1, right -= 1)
    {
        var temporary = array[left];
        array[left] = array[right];
        array[right] = temporary;
    }
    return array;
}
var splitUrl = function( url ) {
    let urlHack = document.createElement('a');
    let tmpUrl = {};
    if( !url ) {
        urlHack.href = location.href;
    } else {
        urlHack.href = url;        
    }
    tmpUrl.pathname = urlHack.pathname;
    tmpUrl.search = urlHack.search;
    tmpUrl.fName = window.location.pathname.substring(window.location.pathname.lastIndexOf('/')+1);
    if( tmpUrl.fName !== "" ) {
        tmpUrl.pathname = tmpUrl.pathname.replaceAll( tmpUrl.fName, "" );    
        tmpUrl.fNameWithoutExt = tmpUrl.fName.substr(0, tmpUrl.fName.lastIndexOf("."))
    }
    return tmpUrl;
}

var getIdAndName = function( id, praefix ) {
    if( !id ) return false;
    let tmp = id.split("_");
    let ret = { name:"", Id: "" };
    if( tmp.length == 1 ) {
        ret.name = tmp[0];
    } else {
        ret.Id = tmp[ tmp.length - 1 ];
        let l = tmp.length - 1;
        let i = 0;
        while( i < l ) {
            ret.name += tmp[i] + "_";
            i += 1;    
        }
        ret.name = ret.name.substr( 0, ret.name.length - 1 );
    }
    if( praefix == undefined ) {
        ret.widthoutPraefix = ret.name; 
    } else {
        ret.widthoutPraefix = ret.name.replace( praefix, "" ); 
    }
    return ret;
}
const getFileExtAndName = function( fName ) {
    let tmp = fName.split( "." );
    let res = {};
    res.Ext = tmp[ tmp.length - 1 ];
    let l = tmp.length - 1;
    let i = 0;
    res.Name = "";
    while ( i < l ) {
        res.Name += tmp[ i ] + "." 
        i += 1;
    }
    res.Name = res.Name.substring( 0, res.Name.length - 1 );
    return res;
}
var getObjectValue = function( obj, key ) {
    for (let tmpKey of Object.getOwnPropertyNames(obj)) {
        if( tmpKey == key ) return obj[tmpKey];
    }
    return false;
}
var setObjectValue = function( obj, key, value ) {
    for (let tmpKey of Object.getOwnPropertyNames(obj)) {
        if( tmpKey == key ) {
            obj[tmpKey] = value;
            return obj;
        }
    }
    return false;
}

var replaceAll = function( string, search, replace ) {
    return string.split(search).join(replace);
}
function maxZIndex() {
    let mI = 0;
    let els = document.querySelectorAll('body *');
    let l = els.length;
    let i = 0;
    let arr = [];
    while( i < l ) {
        //console.log( nj().els( els[i] ) );
        //console.log( nj().els( els[i] ), nj().els( els[i] ).hasAttribute('id') );
        if( nj().els( els[i] ) !== null && parseInt( window.getComputedStyle( els[i] ).zIndex ) < 10000 ) {
            if( parseInt( window.getComputedStyle( els[i] ).zIndex ) > 0 && mI < parseInt( window.getComputedStyle( els[i] ).zIndex ) && !nj( els[i] ).hCl( "heurio-overlay") && nj( els[i] ).atr( "id" ) != "shadow-root-scribe-elem" ) {
                
                mI = parseInt( window.getComputedStyle( els[i] ).zIndex );
                arr.push( mI );
            }
        }
        i += 1;    
    }
    return mI;
}
function getScrollbarWidth() {
  return window.innerWidth - document.documentElement.clientWidth;
}
function getScrollbarHeight() {
  return window.innerHeight - document.documentElement.clientHeight;
}
var replaceUnwantedChars = function( value ) {
    return value.replace(/(['"])/g, "\\$1");
}
const removeCSS = function( fName ) {
    var linkNode = document.querySelector('link[href*="' + fName + '"]');
    linkNode.parentNode.removeChild(linkNode);
}
function loadCSS(url) {
    let els = nj().els( 'head link[href="' + url + '"]');
    if( els.length > 0 ) return;
    let link = nj().cEl( "link" );
    link.type = "text/css";
    link.rel = "stylesheet";
    link.href = url;
    document.getElementsByTagName("head")[0].appendChild(link);
}
async function  loadJavaScript( src ) {
    let els = nj().els( 'body script[src="' + src + '"]');
    if( els.length > 0 ) return;
    let scr = nj().cEl( "script" );
    scr.src = src;
    scr.onload=(function(){})();
    document.getElementsByTagName("body")[0].appendChild(scr);    
}

var getDateFromGerman = function( date ) {
    let tmp = date.split(".");
    return new Date( tmp[2], parseInt( tmp[1] ) - 1, tmp[0] );
}
var getDatumFromDate = function( d ) {
    let m = d.getMonth();
    let y = d.getFullYear();
    let day = d.getDate();
    return new Date( y, m, day );        
}

var getDateFromMysql = function( date, withMinutes ) {
    //console.log( date );
    if( withMinutes == "undefined" ) {
        let tmp = date.split("-");
        return tmp[2] + "." + tmp[1] + "." + tmp[0];
    } else {
        let datetime = date.split(" ");
        time = datetime[1].substring( 5, 0 );
        let tmp = datetime[0].split("-");
        return tmp[2] + "." + tmp[1] + "." + tmp[0] + " " + time;
    } 
}
var getGermanDateFromMysql = function( date ) {
    var tmp = date.split( "-" );
    return tmp[2] + "." + tmp[1] + "." + tmp[0];
}
var getShortGermanDateFromMysql = function( date ) {
    var tmp = date.split( "-" );
    return tmp[2] + "." + tmp[1] + "." + tmp[0].substr( 2 );
}
var getMysqlDateFromGerman = function( date ) {
    if( typeof date == "undefined" ) return;
    var tmp = date.split( "." );
    return tmp[2] + "-" + tmp[1] + "-" + tmp[0];
}
var getGermanDate = function( date ) {
    return ( "0" + date.getDate() ).substr(-2) + "." + ( "0" + ( date.getMonth() + 1 ) ).substr(-2) + "." + date.getFullYear();
}
var getMysqlDate = function( date ) {
        return date.getFullYear() + "-" + ( "0" + ( date.getMonth() + 1 ) ).substr(-2) + "-" + ( "0" + date.getDate() ).substr(-2);
}
var getChatTime = function( date ) {
    date = date.split(" ")[1];
    return date.substr( 0, 8 );
}
var addDaysToDate = function(date, days){
    var res = new Date( date );
    res.setDate(res.getDate() + days);
    return res;
}
var rewriteDateWeekend = function( date ) {
    var res = new Date(date);
    if( res.getDay() == 6 ) {
        return addDaysToDate( res, 2 );
    }   
    if( res.getDay() == 0 ) {
        return addDaysToDate( res, 1 );
    } else {
        return res;
    }  
}
var validatePassword = function( password, password_repeat ) {
    if( password == "" ) {
        showNewMessage( "Fehler", false, "Das Passwort darf nicht leer sein!");
        return false;       
    }
    if( password != password_repeat ) {
        showNewMessage( "Fehler", false, "Die Passworte stimmen nicht übereinander!");
        return false;
    } else {
        return true;
    }
}
var validateAlphaNum = function( eCode, addKeyCodes, addChars ) {
    if( ( eCode > 63 && eCode < 91 ) || ( eCode > 96 && eCode < 123 ) || ( eCode > 47 && eCode < 58 ) ) return true;
    if( addKeyCodes != undefined && addKeyCodes.length > 0 ) {
        let l = addKeyCodes.length;
        let i = 0;
        while( i < l ) {
            if( addKeyCodes[i] == eCode ) return true;
            i += 1;    
        }
    }
    if( addChars != undefined && addChars.length > 0 ) {
        let l = addChars.length;
        let i = 0;
        while( i < l ) {
            if( addChars[i] == String.fromCharCode( eCode ) ) return true;
            i += 1;    
        }
    }
    return false;    
}

var validateEmail = function( email ) {
    var mailformat = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
    //var mailformat = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
    if( email.match(mailformat) ) {
        return true;
    } else {
        return false;
    }
}
var validatePhone = function( phone ) {
    let phoneformat = /^-?\d+$/;
    return phone.match( phoneformat );
}
function validateTime( inputField ) {
    let timeFormat = /^([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])?$/;
    if( inputField.match( timeFormat ) === null ) {
        return false;
    } else {
        return true;
    }
}
function validateDate( date ) {
    let tmp = date.split( "." );
    if( tmp.length > 1 ) {
        date = tmp[2] + "-" + tmp[1] + "-" + tmp[0];
    }
    console.log( date );
    if( new Date( date ) == "Invalid Date" ) {
        return false;
    } else {
        return true;
    }
}
function validatePostalCode( plc ) {
    var plcformat = /^[0-9]{5}?$/;
    if( plc.match( plcformat ) === null ) {
        return false;
    } else {
        return true;
    }
}
function validateURL( url ) {
    try {
        const fccUrl = new URL( url );
        return true;        
    } catch {
        return false;
    }
}
var getFileName = function( fn ) {
    let tmp = fn.split( "/" );
    return tmp[ tmp.length - 1 ];
}
var getFileExtension = function( fn ) {
    let tmp = fn.split( "." );
    return tmp[ tmp.length - 1 ];    
}
var replaceDoubleQuota = function() {
    nj( "input" ).on( "blur", function() {
        console.log( this );
        nj( this ).replaceAll( '"', "“" );
    })
}
/*
function setEndOfContenteditable(elem) {
    let sel = window.getSelection();
    //sel.selectAllChildren(elem);
    sel.collapseToEnd();
}
*/
const removeTagFromElTxt = function( txt, tag ) {
    let s = txt.indexOf( "<" + tag );
    let sEnd = txt.indexOf( ">", s ) + 1;
    let tEnd = "</" + tag + ">";
    let eEnd
    console.log( s, sEnd, e, tEnd, txt.substring( s, sEnd ) );
    
    let res = txt.replace( txt.substring( s, sEnd ), "" ).replace( tEnd, "" );
    console.log( res );
}
const getChildIndex = function( el ) {
    var child = document.getElementById('my_element');
    let parent = el.parentNode;
    if( nj( parent ).hCl( "noValue") ) {
        el = parent;
        parent = parent.parentNode;
    }
    // The equivalent of parent.children.indexOf(child)
    let index = Array.prototype.indexOf.call(parent.children, el);
    return index;
}
const removeFontStyles = function( el, fStyles = [ "font-size", "font-weight", "font-style", "text-decoration", "color" ] ) {
    let sty = nj( el ).atr( "style" );
    let stArr = sty.split( ";" );
    let tSty, stStr = "";
    let l = stArr.length;
    let i = 0;
    while ( i < l ) {
        tSty = stArr[ i ].split( ":" );
        if( fStyles.indexOf( tSty[0].trim() ) === -1 && tSty[0].trim() !== "" ) stStr += tSty[0] + ":" + tSty[1] + ";";
        i += 1;
    }
    return stStr;
}
function replaceElement (source, tagname) { 
    var range = document.createRange(); 
    var element = document.createElement(tagname);
    range.selectNodeContents(source); 
    element.appendChild(range.extractContents()); 
    source.parentNode.replaceChild(element, source); 
} 
function executeCode(code) {
    // A safer approach would be using Function constructor
    const myFunction = new Function(code);
    myFunction();
}
/**
 * @param {String} HTML representing a single element
 * @return {Element}
 */
function htmlToElement(html) {
    var template = document.createElement('template');
    html = html.trim(); // Never return a text node of whitespace as the result
    template.innerHTML = html;
    return template.content.firstChild;
}
