jQuery(function ($) {
  const DEBUG_MODE_TRUE = "1";
  var file_count = 0;
  var path_ajax_loader_gif = $("#path-ajax-loader-gif").text();
  var loader = $(
    '<div id="loader" style="line-height: 115px; text-align: center;"><img alt="activity indicator" src="' +
      path_ajax_loader_gif +
      '"></div>'
  );
  var admin_ajax_php = $("#admin-ajax-php").text();

  function static_press_init() {
    file_count = 0;
    $("#rebuild").hide();
    var text_initialize = $("#text-initialize").text();
    $("#rebuild-result")
      .html("<p><strong>" + text_initialize + "</strong></p>")
      .after(loader);
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_init" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        var debug_mode = $("#debug-mode").text();
        if (debug_mode == DEBUG_MODE_TRUE) {
          console.log(response);
        }
        if (response.result) {
          var text_urls = $("#text-urls").text();
          $("#rebuild-result").append(
            "<p><strong>" + text_urls + "</strong></p>"
          );
          var ul = $("<ul></ul>");
          $.each(response.urls_count, function () {
            ul.append("<li>" + this.type + " (" + this.count + ")</li>");
          });
          $("#rebuild-result").append("<p></p>").append(ul);
        }
        var text_fetch_start = $("#text-fetch-start").text();
        $("#rebuild-result").append(
          "<p><strong>" + text_fetch_start + "</strong></p>"
        );
        static_press_fetch();
      },
      error: function () {
        $("#rebuild").show();
        $("#loader").remove();
        var text_error = $("#text-error").text();
        $("#rebuild-result").append(
          '<p id="message"><strong>' + text_error + "</strong></p>"
        );
        $("html,body").animate(
          { scrollTop: $("#message").offset().top },
          "slow"
        );
        file_count = 0;
      },
    });
  }

  function static_press_fetch() {
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_fetch" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        if ($("#rebuild-result ul.result-list").size() == 0)
          $("#rebuild-result").append(
            '<p class="result-list-wrap"><ul class="result-list"></ul></p>'
          );
        if (response.result) {
          var debug_mode = $("#debug-mode").text();
          if (debug_mode == DEBUG_MODE_TRUE) {
            console.log(response);
          }
          var ul = $("#rebuild-result ul.result-list");
          $.each(response.files, function () {
            if (this.static) {
              file_count++;
              ul.append("<li>" + file_count + " : " + this.static + "</li>");
            }
          });
          if (!($("li:last-child", ul).offset() === void 0)) {
            $("html,body").animate(
              { scrollTop: $("li:last-child", ul).offset().top },
              "slow"
            );
          }
          if (response.final) static_press_finalyze();
          else static_press_fetch();
        } else {
          static_press_finalyze();
        }
      },
      error: function () {
        $("#rebuild").show();
        $("#loader").remove();
        var text_error = $("#text-error").text();
        $("#rebuild-result").append(
          '<p id="message"><strong>' + text_error + "</strong></p>"
        );
        $("html,body").animate(
          { scrollTop: $("#message").offset().top },
          "slow"
        );
        file_count = 0;
      },
    });
  }

  function static_press_finalyze() {
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_finalyze" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        var debug_mode = $("#debug-mode").text();
        if (debug_mode == DEBUG_MODE_TRUE) {
          console.log(response);
        }
        $("#rebuild").show();
        $("#loader").remove();
        var text_end = $("#text-end").text();
        $("#rebuild-result").append(
          '<p id="message"><strong>' + text_end + "</strong></p>"
        );
        $("html,body").animate(
          { scrollTop: $("#message").offset().top },
          "slow"
        );
        file_count = 0;
      },
      error: function () {
        $("#rebuild").show();
        $("#loader").remove();
        var text_error = $("#text-error").text();
        $("#rebuild-result").append(
          '<p id="message"><strong>' + text_error + "</strong></p>"
        );
        $("html,body").animate(
          { scrollTop: $("#message").offset().top },
          "slow"
        );
        file_count = 0;
      },
    });
  }

  $("#rebuild").click(static_press_init);
});
