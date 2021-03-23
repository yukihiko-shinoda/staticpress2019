jQuery(function ($) {
  function Loader() {
    this.path_ajax_loader_gif = $("#path-ajax-loader-gif").text();
    this.dom = $(
      '<div id="loader" style="line-height: 115px; text-align: center;"><img alt="activity indicator" src="' +
        path_ajax_loader_gif +
        '"></div>'
    );
    this.append_after = function (dom) {
      dom.after(this.dom);
    };
    this.remove = function () {
      $("#loader").remove();
    };
  }

  function RebuildResult() {
    this.file_count = 0;
    this.dom = $("#rebuild-result");
    this.text_error = $("#text-error").text();
    this.text_initialize = $("#text-initialize").text();
    this.text_fetch_start = $("#text-fetch-start").text();
    this.text_end = $("#text-end").text();
    this.text_urls = $("#text-urls").text();
    this.loader = new Loader();
    this.display_message = function (text) {
      this.dom.append('<p id="message"><strong>' + text + "</strong></p>");
      $("html,body").animate({ scrollTop: $("#message").offset().top }, "slow");
    };
    this.display_error = function () {
      this.display_message(this.text_error);
    };
    this.initialize = function () {
      this.loader.append_after(
        this.dom.html("<p><strong>" + this.text_initialize + "</strong></p>")
      );
    };
    this.display_fetch_start = function () {
      this.dom.append("<p><strong>" + this.text_fetch_start + "</strong></p>");
      this.dom.append(
        '<p class="result-list-wrap"><ul class="result-list"></ul></p>'
      );
    };
    this.display_file = function (files) {
      var ul = $("#rebuild-result ul.result-list");
      var file_count = this.file_count;
      $.each(files, function () {
        if (this.static) {
          file_count++;
          ul.append("<li>" + file_count + " : " + this.static + "</li>");
        }
      });
      this.file_count = file_count;
      if (!($("li:last-child", ul).offset() === void 0)) {
        $("html,body").animate(
          { scrollTop: $("li:last-child", ul).offset().top },
          "slow"
        );
      }
    };
    this.display_end = function () {
      this.display_message(this.text_end);
    };
    this.append_empty_list = function (urls_count) {
      this.dom.append("<p><strong>" + this.text_urls + "</strong></p>");
      var ul = $("<ul></ul>");
      $.each(urls_count, function () {
        ul.append("<li>" + this.type + " (" + this.count + ")</li>");
      });
      this.dom.append("<p></p>").append(ul);
    };
    this.scroll_to_message = function () {
      $("html,body").animate({ scrollTop: $("#message").offset().top }, "slow");
    };
    this.remove_loader = function () {
      this.loader.remove();
    };
    this.reset_file_count = function () {
      this.file_count = 0;
    };
  }

  var rebuild_result = new RebuildResult();
  var admin_ajax_php = $("#admin-ajax-php").text();

  function DebugLogger() {
    const DEBUG_MODE_TRUE = "1";
    this.debug_mode = $("#debug-mode").text();
    this.log = function (response) {
      if (this.debug_mode == DEBUG_MODE_TRUE) {
        console.log(response);
      }
    };
  }

  var debug_logger = new DebugLogger();

  function error() {
    $("#rebuild").show();
    rebuild_result.remove_loader();
    rebuild_result.display_error();
  }

  function static_press_init() {
    rebuild_result.reset_file_count();
    $("#rebuild").hide();
    rebuild_result.initialize();
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_init" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        debug_logger.log(response);
        if (response.result) {
          rebuild_result.append_empty_list(response.urls_count);
        }
        rebuild_result.display_fetch_start();
        static_press_fetch();
      },
      error: error,
    });
  }

  function static_press_fetch() {
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_fetch" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        debug_logger.log(response);
        if (!response.result) {
          static_press_finalyze();
          return;
        }
        rebuild_result.display_file(response.files);
        if (response.final) {
          static_press_finalyze();
          return;
        }
        static_press_fetch();
      },
      error: error,
    });
  }

  function static_press_finalyze() {
    $.ajax(admin_ajax_php, {
      data: { action: "static_press_finalyze" },
      cache: false,
      dataType: "json",
      type: "POST",
      success: function (response) {
        debug_logger.log(response);
        $("#rebuild").show();
        rebuild_result.remove_loader();
        rebuild_result.display_end();
      },
      error: error,
    });
  }

  $("#rebuild").click(static_press_init);
});
