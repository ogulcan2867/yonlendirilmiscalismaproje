var Arasbil = function () {
	var name_field_change = function () {
		var $this = $(this);
		var $perm_div = $this.data("perm_div");
		var $perm_input = $perm_div.find("input:text");
		var is_new = parseInt($perm_input.data("new")) === 1;
		var val = $.trim($this.val());
		var pval = $.trim($perm_input.val());
		if (pval === "" && val !== "") {
			//$perm_input.val(val);
			$perm_input.trigger("cenc");
		}
	};

	var permant_field_change = function () {
		var $this = $(this);
		var $div = $this.parents(".permant_main:eq(0)");
		var $span = $div.find(".permant_span");
		if ($span.hasClass("loading")) {
			return false;
		}
		$span.addClass("loading");
		$.post("ajax.php", {
			action: "permant",
			dil_id: $this.data("lang_id"),
			modul_id: $this.data("modul_id"),
			record_id: $this.data("record_id"),
			name: $this.data("name_input").val(),
			permant: $this.val()
		}, function (callback) {
			var $j = $.parseJSON(callback);
			$span.removeClass("loading");
			$this.val($j.perm);
			$span.find(".text").html($j.perm);
		});
	};

	var permant_field_keydown = function (e) {
		if (e.keyCode === 13) {
			//$(this).trigger("cenc");
			$(this).parent().find("input:button.dbutton").click();
			return false;
		}
	};

	var permant_btn_ = function (element, vx) {
		element = $(element);
		//var this_is_edit = element.hasClass("text");
		//console.log(this_is_edit);
		if (vx === 0) {
			element.parent().removeClass("text_").addClass("input_");
		} else {
			var $span = element.parent();
			var $inp = $span.find("input:text");
			var $txt = $span.find(".text");
			if (element.is("a")) {
				$inp.val($txt.html());
			} else {
				$inp.trigger("cenc");
			}
			element.parent().removeClass("input_").addClass("text_");
		}
	};

	var reRenderSelectForNest = function (el) {
		var $n = $(".select2-dropdown .select2-results");
		$n.find('ul:not([nest_level])').each(function () {
			var $this = $(this);
			$this.attr('nest_level', $this.parentsUntil('.select2-results', 'ul').size());
		});
		var selectable_roots = el.data('selectable_roots');
		if (selectable_roots !== undefined) {
			$n.find('ul[nest_level] li').attr('aria-selected', 'true');
		}

	};

	function set_custom_select_events() {

		$(".custom_select:not(.events_setted)").each(function (csi) {
			var $this = $(this);
			$this.find("div.pa div:has(> div)").addClass("root");
			$this.find(".root > input").remove();
			if (!$this.hasClass("pre_setted")) {
				$this.find("div.pa div").each(function (di) {
					var $el = $(this);
					var ix = "cs" + csi + "_" + di;
					$el.find("> input").attr("id", ix);
					$el.find("> label").attr("for", ix);
				});
			}

			var all_sub_items_count = $this.find("div.pa div").size();
			if (all_sub_items_count > 6) {
				$this.find("div.pa").css({
					"max-height": 23 * 6,
					"overflow": "auto"
				});
			}
			/*var data = $this.attr("data");
			 data = data == "" ? "{}" : data;
			 //console.log(data);
			 var $data = $.parseJSON(data);
			 //console.log($data);
			 var it = $this.hasClass("multi") ? "checkbox" : "radio";
			 var text = custom_select_elem($data, it, $this.attr("name"));*/
		}).bind("update", function () {
			var $this = $(this);
			var multi = $this.hasClass("multi");
			var $checked = $this.find("input:checked");
			var label = "";
			$this.find("> div.pa div").removeClass("selected");
			if (multi) {
				if ($checked.size() !== 0) {
					var cm = $checked.size();
					for (ci = 0; ci < cm; ci++) {
						if (ci > 0) {
							label += ", ";
						}
						label += $.trim($checked.eq(ci).next().html());
					}
				} else {
					label = "&nbsp;";
				}
			} else {
				if ($checked.length == 0) {
					$checked = $this.find("input:eq(0)");
					$checked.attr("checked", "checked");
				}
				label = $.trim($checked.next().html());
			}
			$checked.parent().addClass("selected")
			$this.find("> .text:eq(0)").html(label);
		}).bind("mouseleave leave", function () {
			$(this).removeClass("kapat");
		}).trigger("update");
		$(".custom_select:not(.events_setted) div.pa input").bind("change click", function () {
			var $this = $(this);
			var $element = $this.parents(".custom_select:eq(0)");
			if (!$this.parents(".custom_select").hasClass("multi")) {
				$element.addClass("kapat");
			}
			$element.trigger("update");
		});

		$(".custom_select:not(.events_setted):not(:has( > .icon)) > .text").after($("<span />").addClass("icon"));

		$(".custom_select:not(.events_setted)").addClass("events_setted");

	}

	function parents_renderer_(items) {
		if (items.length > 0) {
			var delimiter = ' &raquo; ';
			var _parents = '';
			_parents = '<span class="text-gray">' + items.join(delimiter) + delimiter + '</span>';
			return _parents;
		}
		return null;
	}

	return  {
		init: function () {
			this.initPermant();
			this.initDatePicker();
			this.initSelects();

		},
		initPermant: function () {
			$(".permant_main").each(function () {
				var $this = $(this);
				var $this_tr = $this.parents(".form-group:eq(0)");
				var $name_field = $this_tr.prev().find("input:eq(0)");
				var $perm_input = $this.find("input:text");
				$name_field.data("perm_div", $this);
				$name_field.bind("change", name_field_change);
				$perm_input.data("name_input", $name_field);
				$perm_input.bind("cenc", permant_field_change).bind("keydown", permant_field_keydown);
			});
		},
		toastr_options: function (rex) {
			switch (rex) {
				case "block_top":
					return {
						"closeButton": true,
						"debug": false,
						"positionClass": "toast-top-full-width",
						"onclick": null,
						"showDuration": "1000",
						"hideDuration": "1000",
						"timeOut": "5000",
						"extendedTimeOut": "1000",
						"showEasing": "swing",
						"hideEasing": "linear",
						"showMethod": "fadeIn",
						"hideMethod": "fadeOut"
					}
			}
		},
		initDatePicker: function () {
			$('.date-picker').datepicker({
				orientation: "left",
				autoclose: true,
				format: "dd.mm.yyyy",
				pickerPosition: "top-left",
				todayBtn: false
			});
		},
		initSelects: function () {
			$("select:not(.no_select2, .no_js)").select2().addClass("ArasbilinitSelects");
		},
		initCustomSelect: function () {
			if (window.console) {
				console.log('argenova.initCustomSelect');
			}
			return false;
			set_custom_select_events();
		},
		permant_btn: function (element, vx) {
			return permant_btn_(element, vx);
		},
		reRenderSelectForNest: function (el) {
			el.on("select2:open", function (e) {
				reRenderSelectForNest($(this));
			});
		},
		parents_renderer: function (items) {
			return parents_renderer_(items);
		},
		select2modulItemTemplate: function (item) {
			if (!item.id) {
				return item.text;
			}
			var _parents = parents_renderer_(item.parents);
			_parents = _parents === null ? '' : _parents;
			return $('<span>' + _parents + item.text + '</span>');
		}
	};
}();

function table_update_field_value(args, ob) {
	if (args == undefined || ob == undefined) {
		return;
	}
	ob = $(ob);
	var x = {};
	x.action = "table_update_field_value";
	var i = 0;
	var rel = "";
	for (var key in args) {
		if (i > 0) {
			rel += "|";
		}
		rel += key + "::" + args[key];
		i++;
	}
	x.rel = rel;
	x.deger = ob.is(":checkbox") ? ob.is(":checked") ? 1 : 0 : ob.val();
	$.post("ajax.php", x, function () {

	});
}