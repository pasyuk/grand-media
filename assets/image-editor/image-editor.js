/*
 * jQuery plugin adapter for CamanJS
 */
if (window.jQuery) {
	window.jQuery.fn.caman = function (callback) {
		return this.each(function () {
			Caman(this, callback);
		});
	};
}
// gmedit_init($("#for_edit").data("src") + "?" + g, "#for_edit", {save: h})
var gmedit;
var gmedit_init = function(src, el, callback_args){
	gmedit = new gmedit_core;
	"undefined" != typeof callback_args && jQuery.each(callback_args, function(event, func){
		gmedit.setCallback(event, func)
	});
	/*
	Caman.remoteProxy =
		Caman.IO.useProxy("php");
	Caman.remoteProxy = d + "lib/camanjs/proxies/caman_proxy.php";
	*/
	gmedit.setCallback("afterRender", function(){});
	gmedit.setCallback("beforeImageLoad", function(){
		jQuery("#gmedit-overlay").show()
	});
	gmedit.setCallback("afterImageLoad", function(){
		jQuery("#gmedit-overlay").fadeOut()
	});
	gmedit.init("#gmedit-canvas", src);
	var tools = gmedit.getTools();
	jQuery.each(tools, function(tool, c){
		"filter" === c.type && jQuery("#" + tool + "_slider").noUiSlider({range: {'min': c.from, 'max': c.to}, handles: 1, step: 1, start: 0}).on({slide: function(){
			gmedit.setFilter(tool, parseInt(jQuery(this).val()));
		}})
	});
	jQuery("#gmedit").on("click", "#gmedit-reset", function(){
		gmedit.resetFilters()
	});
	jQuery("#gmedit").on("click", ".gmedit-tool", function(){
		var tool = jQuery(this).data("tool"), val = 1 === jQuery(this).data("value")? 0 : 1;
		jQuery(this).data("value", val);
		gmedit.setFilter(tool, val)
	});
	jQuery("#gmedit").on("click", ".gmedit-tool.greyscale, .gmedit-tool.invert", function(){
		jQuery(this).toggleClass("switched")
	});
	jQuery("#gmedit").on("click", ".gmedit-rotate", function(){
		var degree = gmedit.getToolValue("rotate") + (jQuery(this).hasClass("right")? 90 : -90);
		270 < degree? degree = 0 : 0 > degree && (degree = 270);
		gmedit.setFilter("rotate", degree)
	});
	jQuery("#gmedit").on("click", ".gmedit-filter-pm", function(){
		var tool = jQuery(this).data("tool"),
			val = parseInt(jQuery("#" + tool + "_slider").val()),
			d = jQuery(this).data("direction"),
			action = gmedit.getTools();
		val = val + 5 * ("minus" === d? -1 : 1);
		val = val > action[tool].to? action[tool].to : val;
		val	= val < action[tool].from? action[tool].from : val;
		jQuery("#" + tool + "_slider").val(val);
		gmedit.setFilter(tool, val);
		return !1
	});
	jQuery("#gmedit").on("click", "#gmedit-save", function(){
		gmedit.save();
		return !1
	});
	jQuery("#gmedit").on("click", ".gmedit-filter-edit", function(){
		jQuery(this).parent().find(".gmedit-slider-noui").slideToggle("fast")
	});
	/*
	jQuery("#gmedit").on("mouseleave", ".gmedit-filter", function(){
		jQuery(this).find(".gmedit-slider-noui").slideUp("fast")
	})
	*/
};

var gmedit_core = function(){
	var canvas = document.getElementById("gmedit-canvas"),
		action = {
			rotate: {type: "switch", value: 0},
			flip_hor: {type: "switch", value: 0},
			flip_ver: {type: "switch", value: 0},
			greyscale: {type: "switch", value: 0},
			invert: {type: "switch", value: 0},
			brightness: {type: "filter", value: 0, from: -100, to: 100},
			contrast: {type: "filter", value: 0, from: -100, to: 100},
			saturation: {type: "filter", value: 0, from: -100, to: 100},
			vibrance: {type: "filter", value: 0, from: -100, to: 100},
			exposure: {type: "filter", value: 0, from: -100, to: 100},
			hue: {type: "filter", value: 0, from: 0, to: 100},
			sepia: {type: "filter", value: 0, from: 0, to: 100},
			noise: {type: "filter", value: 0, from: 0, to: 100},
			clip: {type: "filter", value: 0, from: 0, to: 100}
		},
		loading = !1,
		newcanvas = document.createElement("canvas"),
		$this = this,
		el_preview,
		preview,
		preview_w = jQuery("#gmedit-canvas-cont").width(),
		preview_h = jQuery("#gmedit-canvas-cont").height(),
		imgtype,
		events = {
			afterRender: function(){},
			beforeImageLoad: function(){},
			afterImageLoad: function(){},
			afterReset: function(){},
			save: function(a, b){}
		},
		mimetype = function(a){
			var m = "image/jpeg";
			switch(a.substring(a.lastIndexOf(".") + 1).toLowerCase().replace(/\?.*/, "")){
				case "gif":
					m = "image/gif";
					break;
				case "png":
					m = "image/png"
			}
			return m
		};
	gmedit_core.prototype.init = function(el, path){
		events.beforeImageLoad();
		canvas = Caman(el, path, function(){
			imgtype = mimetype(path);
			newcanvas.width = this.width;
			newcanvas.height = this.height;
			newcanvas.getContext("2d").drawImage(this.canvas, 0, 0);
			this.render(function(){
				jQuery(el).after('<canvas id="gmedit-canvas-preview"></canvas>');
				el_preview = document.getElementById("gmedit-canvas-preview");
				preview = el_preview.getContext("2d");
				el_preview.width = preview_w;
				el_preview.height = preview_h;
				draw_image();
				events.afterImageLoad()
			})
		})
	};
	gmedit_core.prototype.fit_size = function(cw, ch, fitw, fith){
		fitw /= cw;
		var dy = fith / ch;
		fith = 1;
		if(1 > fitw || 1 > dy){
			fith = Math.min(fitw, dy);
		}
		cw = Math.round(cw * fith);
		ch = Math.round(ch * fith);
		return{width: cw, height: ch}
	};
	var draw_image = function(){
			canvas = document.getElementById("gmedit-canvas");
			var fsize = $this.fit_size(canvas.width, canvas.height, preview_w, preview_h),
				dx = Math.round((preview_w - fsize.width) / 2),
				dy = Math.round((preview_h - fsize.height) / 2);
			el_preview.width = preview_w;
			preview.drawImage(canvas, 0, 0, canvas.width, canvas.height, dx, dy, fsize.width, fsize.height)
		},
		do_filter = function(){
			if(!loading){
				jQuery("#gmedit-busy").fadeIn("fast");
				loading = !0;
				var canvas_a = document.createElement("canvas"),
					canvas_b = document.createElement("canvas");
				canvas_a.width = newcanvas.width;
				canvas_a.height = newcanvas.height;
				canvas_a.getContext("2d").drawImage(newcanvas, 0, 0);
				var ctx = canvas_a.getContext("2d");
				if(0 !== action.flip_hor.value && 0 !== action.flip_ver.value){
					ctx.scale(-1, -1), ctx.drawImage(canvas_a, -canvas_a.width, -canvas_a.height);
				} else{
					if(0 !== action.flip_hor.value){
						ctx.translate(canvas_a.width, 0), ctx.scale(-1, 1), ctx.drawImage(canvas_a, 0, 0);
					} else{
						0 !== action.flip_ver.value && (ctx.scale(1, -1), ctx.drawImage(canvas_a, 0, -canvas_a.height));
					}
				}
				if(0 !== action.rotate.value){
					var w = canvas_a.width, h = canvas_a.height, dx = 0, dy = 0;
					switch(action.rotate.value){
						case 90:
							w = canvas_a.height;
							h = canvas_a.width;
							dy = -1 * canvas_a.height;
							break;
						case 180:
							dx = -1 * canvas_a.width;
							dy = -1 * canvas_a.height;
							break;
						case 270:
							w = canvas_a.height, h = canvas_a.width, dx = -1 * canvas_a.width
					}
					canvas_b.width = w;
					canvas_b.height = h;
					var ctx2 = canvas_b.getContext("2d");
					ctx2.rotate(parseInt(action.rotate.value) * Math.PI / 180);
					ctx2.drawImage(canvas_a, dx, dy);
					canvas_a = canvas_b;
				}
				Caman(canvas_a, function(){
					var t = this;
					jQuery.each(action, function(a, c){
						if("filter" === c.type && 0 != parseInt(c.value)){
							t[a](parseInt(c.value))
						}
					});
					0 !== parseInt(action.greyscale.value) && this.greyscale();
					0 !== parseInt(action.invert.value) && this.invert();
					this.render(function(){
						var e = document.getElementById("gmedit-canvas");
						//e.width = e.width;
						var c = e.getContext("2d");
						e.width = canvas_a.width;
						e.height = canvas_a.height;
						c.drawImage(canvas_a, 0, 0);
						loading = !1;
						draw_image();
						jQuery("#gmedit-busy").fadeOut("fast");
						events.afterRender()
					})
				})
			}
		};
	gmedit_core.prototype.setFilter = function(a, b){
		$this.setToolValue(a, b);
		jQuery("#" + a + "Value").html(b);
		do_filter()
	};
	gmedit_core.prototype.save = function(){
		Caman("#gmedit-canvas", function(){
			var a = this.canvas.toDataURL(imgtype);
			events.save(a, action)
		})
	};
	gmedit_core.prototype.resetFilters = function(){
		jQuery.each(action, function(a, b){
			$this.setToolValue(a, 0)
		});
		jQuery.each(action, function(a, b){
			"filter" === action[a].type && ($this.setFilter(a, 0), jQuery("#" + a + "_slider").val(0))
		});
		jQuery(".gmedit-tool").data("value", 0).removeClass("switched");
		events.afterReset();
		do_filter()
	};
	gmedit_core.prototype.setCallback = function(a, b){
		events[a] = b
	};
	gmedit_core.prototype.getTools = function(){
		return action
	};
	gmedit_core.prototype.getToolValue = function(a){
		return action[a].value
	};
	gmedit_core.prototype.setToolValue = function(a, b){
		return action[a].value = b
	}
};

