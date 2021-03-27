jQuery(function ($) {
  function Loader() {
    this.path_ajax_loader_gif = $("#path-ajax-loader-gif").text();
    this.dom = $(
      '<div id="loader" style="line-height: 115px; text-align: center;"><img alt="activity indicator" src="' +
        this.path_ajax_loader_gif +
        '"></div>'
    );
    this.append_after = function (dom) {
      dom.after(this.dom);
    };
    this.remove = function () {
      this.dom.remove();
    };
  }

  function ResultList(dom) {
    this.file_count = 0;
    this.dom = $('<ul class="result-list"></ul>');
    var dom_p = $('<p class="result-list-wrap"></p>');
    dom_p.append(this.dom);
    dom.append(dom_p);
    this.display_file = function (files) {
      var ul = this.dom;
      var file_count = this.file_count;
      $.each(files, function () {
        if (this.static) {
          file_count++;
          ul.append("<li>" + file_count + " : " + this.static + "</li>");
        }
      });
      this.dom = ul;
      this.file_count = file_count;
      if (!($("li:last-child", this.dom).offset() === void 0)) {
        $("html,body").animate(
          { scrollTop: $("li:last-child", this.dom).offset().top },
          "slow"
        );
      }
    };
  }

  function RebuildResult() {
    this.dom = $("#rebuild-result");
    this.text_error = $("#text-error").text();
    this.text_initialize = $("#text-initialize").text();
    this.text_fetch_start = $("#text-fetch-start").text();
    this.text_end = $("#text-end").text();
    this.text_urls = $("#text-urls").text();
    this.loader = new Loader();
    this.result_list = null;
    this.display_message = function (text) {
      this.dom.append('<p id="message"><strong>' + text + "</strong></p>");
      $("html,body").animate({ scrollTop: $("#message").offset().top }, "slow");
    };
    this.display_error = function (jqxhr, textStatus) {
      this.loader.remove();
      this.display_message(this.text_error);
      if (textStatus !== null) {
        this.dom.append("<p><strong>" + textStatus + "</strong></p>");
      }
      if (
        jqxhr.hasOwnProperty("responseJSON") &&
        jqxhr.responseJSON.hasOwnProperty("representation")
      ) {
        this.dom.append(
          // see:
          //   - Answer: javascript - How do I replace all line breaks in a string with <br /> elements? - Stack Overflow
          //     https://stackoverflow.com/a/784547/12721873
          "<p><strong>" + jqxhr.responseJSON.representation.replace(/(?:\r\n|\r|\n)/g, '<br>') + "</strong></p>"
        );
      }
    };
    this.initialize = function () {
      this.loader.append_after(
        this.dom.html("<p><strong>" + this.text_initialize + "</strong></p>")
      );
    };
    this.display_fetch_start = function () {
      this.dom.append("<p><strong>" + this.text_fetch_start + "</strong></p>");
      this.result_list = new ResultList(this.dom);
    };
    this.display_file = function (files) {
      this.result_list.display_file(files);
    };
    this.display_end = function () {
      this.loader.remove();
      this.display_message(this.text_end);
    };
    this.append_number_url_per_type = function (urls_count) {
      this.dom.append("<p><strong>" + this.text_urls + "</strong></p>");
      var ul = $("<ul></ul>");
      $.each(urls_count, function () {
        ul.append("<li>" + this.type + " (" + this.count + ")</li>");
      });
      this.dom.append("<p></p>").append(ul);
    };
    this.remove_loader = function () {
      this.loader.remove();
    };
  }

  function DebugLogger() {
    const DEBUG_MODE_TRUE = "1";
    this.debug_mode = $("#debug-mode").text();
    this.log = function (response) {
      if (this.debug_mode == DEBUG_MODE_TRUE) {
        console.log(response);
      }
    };
  }

  var admin_ajax_php = $("#admin-ajax-php").text();
  var rebuild_result = new RebuildResult();
  var debug_logger = new DebugLogger();

  function error(jqxhr, textStatus) {
    debug_logger.log(jqxhr);
    $("#rebuild").show();
    rebuild_result.display_error(jqxhr, textStatus);
  }

  function static_press_init() {
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
          rebuild_result.append_number_url_per_type(response.urls_count);
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
        rebuild_result.display_end();
      },
      error: error,
    });
  }

  $("#rebuild").click(static_press_init);
});
