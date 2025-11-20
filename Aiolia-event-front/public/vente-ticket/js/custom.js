jQuery(document).ready(function($) {



    /*** wow animation ***/
    new WOW({
        mobile: false,
    }).init()

     $(".menuMobile").click(function() {
        $(this).toggleClass('active');
        $("body").toggleClass('overflow');
        $(".blcMenu").toggleClass('active');

    });




	
var highlightedDates = {};
if (Array.isArray(window.AIOLIA_EVENT_DATES)) {
    window.AIOLIA_EVENT_DATES.forEach(function(item) {
        if (!item || !item.date) {
            return;
        }
        if (!highlightedDates[item.date]) {
            highlightedDates[item.date] = [];
        }
        highlightedDates[item.date].push(item.title || 'Événement');
    });
}

function formatDateToIso(dateObj) {
    var month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
    var day = dateObj.getDate().toString().padStart(2, '0');
    return dateObj.getFullYear() + '-' + month + '-' + day;
}

$("#datepicker").datepicker({
    dayNamesMin: [ "D", "L", "M", "M", "J", "V", "S" ],
    monthNames: [ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
            "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre" ],
    beforeShowDay: function(date) {
        var iso = formatDateToIso(date);
        if (highlightedDates[iso]) {
            var tooltip = highlightedDates[iso].join(', ');
            return [true, 'has-event', tooltip];
        }
        return [true, '', ''];
    }
});

$( function() {
    var dateFormat = "dd/mm/yy",
      from = $( "#from" )
        .datepicker({
          defaultDate: "+1w",
          changeMonth: true,
          numberOfMonths: 1,
          dayNamesMin: [ "D", "L", "M", "M", "J", "V", "S" ],
        monthNamesShort: [ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
            "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre" ],
        })
        .on( "change", function() {
          to.datepicker( "option", "minDate", getDate( this ) );
        }),
      to = $( "#to" ).datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        numberOfMonths: 1,
        dayNamesMin: [ "D", "L", "M", "M", "J", "V", "S" ],
        monthNamesShort: [ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
            "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre" ],
      })
      .on( "change", function() {
        from.datepicker( "option", "maxDate", getDate( this ) );
      });
 
    function getDate( element ) {
      var date;
      try {
        date = $.datepicker.parseDate( dateFormat, element.value );
      } catch( error ) {
        date = null;
      }
 
      return date;
    }
  } );

$( function() {
    var $slider = $( "#slider-range" );
    if ($slider.length) {
      var min = parseFloat($slider.data('min')) || 0;
      var max = parseFloat($slider.data('max')) || 0;
      if (max <= min) {
        max = min + 1;
      }
      var startValues = [min, max];
      $slider.slider({
        range: true,
        min: min,
        max: max,
        values: startValues,
        slide: function( event, ui ) {
          $( "#amount" ).val( ui.values[ 0 ].toLocaleString('fr-FR') + " MGA - " + ui.values[ 1 ].toLocaleString('fr-FR') + " MGA"  );
        }
      });
      $( "#amount" ).val(
        $slider.slider( "values", 0 ).toLocaleString('fr-FR') + " MGA - " +
        $slider.slider( "values", 1 ).toLocaleString('fr-FR') + " MGA"
      );
    }
  } );


 $(".numbers-row .button").on("click", function() {
        // Ignorer les boutons avec data-type (gérés par le code spécifique dans details.html.twig)
        if ($(this).attr('data-type')) {
            return;
        }
        var $button = $(this);
        var oldValue = $button.parent().find("input").val();
        if ($button.text() == "+") {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            if (oldValue > 1) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 1;
            }
        }
        $button.parent().find("input").val(newVal);
    });
    $(".toggle-password").click(function() {
        $(this).toggleClass("showcharacters");
        input = $(this).parent().find("input");
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });
 /* Video */

  $('.btn_play').click(function() {
        $(this).toggleClass('hide')
        var vid = $('#video');
        vid[0].paused ? vid[0].play() : vid[0].pause();
    });
    $('.play-btn').click(function() {
        var videoId = $(this).data('video');
        var video = document.getElementById(videoId);

        // Pause all videos first
        $('video').each(function() {
            this.pause();
            this.currentTime = 0;
        });
        // Hide all buttons
        $('.play-btn').show();

        // Play selected video
        video.play();

        // Hide the button
        $(this).hide();
    });

    // Optional: Show button again when video ends
    $('video').on('ended', function() {
        $('button[data-video="' + this.id + '"]').show();
    });

    /* end video*/

    const inputFile = document.querySelector(".picture__input");
    const pictureImage = document.querySelector(".picture__image");
    const pictureImageTxt = "";
    
    if (pictureImage) {
        pictureImage.innerHTML = pictureImageTxt;
    }

    if (inputFile) {
        inputFile.addEventListener("change", function (e) {
      const inputTarget = e.target;
      const file = inputTarget.files[0];

      if (file) {
        const reader = new FileReader();

        reader.addEventListener("load", function (e) {
          const readerTarget = e.target;

          if (pictureImage) {
            const img = document.createElement("img");
            img.src = readerTarget.result;
            img.classList.add("picture__img");

            pictureImage.innerHTML = "";
            pictureImage.appendChild(img);
          }
        });

        reader.readAsDataURL(file);
      } else {
        if (pictureImage) {
          pictureImage.innerHTML = pictureImageTxt;
        }
      }
    });
    }


  

      //check local storage for the lang
      var sessionLang = localStorage.getItem('lang');
      if (sessionLang){
        //find an item with value of sessionLang
        var langIndex = langArray.indexOf(sessionLang);
        $('.btn-select').html(langArray[langIndex]);
        $('.btn-select').attr('value', sessionLang);
      } else {
         var langIndex = langArray.indexOf('ch');
        console.log(langIndex);
        $('.btn-select').html(langArray[langIndex]);
        //$('.btn-select').attr('value', 'en');
      }


   





var aioliaEventStart = null;
if (window.AIOLIA_EVENT_START) {
    var parsed = window.AIOLIA_EVENT_START.replace(' ', 'T');
    var startDate = new Date(parsed);
    if (!isNaN(startDate.getTime())) {
        aioliaEventStart = startDate;
    }
}

function makeTimer() {
        var endTime = aioliaEventStart ? new Date(aioliaEventStart) : new Date("10 August 2025 9:56:00 GMT+01:00");
            endTime = (Date.parse(endTime) / 1000);

            var now = new Date();
            now = (Date.parse(now) / 1000);

            var timeLeft = endTime - now;

            var days = Math.floor(timeLeft / 86400); 
            var hours = Math.floor((timeLeft - (days * 86400)) / 3600);
            var minutes = Math.floor((timeLeft - (days * 86400) - (hours * 3600 )) / 60);
            var seconds = Math.floor((timeLeft - (days * 86400) - (hours * 3600) - (minutes * 60)));
  
            if (hours < "10") { hours = "0" + hours; }
            if (minutes < "10") { minutes = "0" + minutes; }
            if (seconds < "10") { seconds = "0" + seconds; }

            $("#days").html(days + "<em></em>" + "<span>Jours</span>");
            $("#hours").html(hours + "<em></em>" + "<span>Heures</span>");
            $("#minutes").html(minutes + "<em></em>"  + "<span>Minutes</span>");
            $("#seconds").html(seconds + "<em></em>"  + "<span>Secondes</span>");       

}
    setInterval(function() { makeTimer(); }, 1000);
})

