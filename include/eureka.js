Number.prototype.pad = function(size)
{
	return String("00000"+this).substr(-size);
}
String.prototype.htmlEncode=function()
{
	return this.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
};
var Time = function(timefloat)
{
	this.value = timefloat;
	this.hours = parseInt(this.value);
	this.minutes = (this.value - this.hours) * 60; 
	this.toLocaleString = function()
	{
		return this.hours.pad(2)+":"+this.minutes.pad(2);
	}
};
var perform = function(url, action, data, editfn, endfn)
{
	var actionlist = {};
	actionlist[action] = data;

	var message = {};
	message.envelope = {};
	message.envelope.body = actionlist;
	var method = "post";
	$.ajax(
	{
		url: url,
		method:  method,
		data: message,
		dataType: "json",
		//dataType: "text",
		timeout:10000,
	}).done (function(data, textStatus, jqXHR)
	{
		var actionlist = data.body;
		for(var key in actionlist)
		{
			if (actionlist[key].result == 1)
			{
				editfn.call(actionlist[key]);
			}
			else if (actionlist[key].error !== undefined)
			{
				alert(actionlist[key].error);
			}
		}
		if (typeof(endfn) === "function")
			endfn.call(actionlist);
	});
};
$.require = {

	js: function(src, call)
	{
		var script = document.createElement('script');
		script.setAttribute('type', 'text/javascript');
		script.setAttribute('src', src);
		document.head.appendChild(script);
		if (typeof(call) == "function") script.onload = call;
	},

	css: function(src, call)
	{
		var style = document.createElement('link');
		style.setAttribute('rel', 'stylesheet');
		style.setAttribute('href', src);
		document.head.appendChild(style);
		if (typeof(call) == "function") style.onload = call;
	},

	object: function(id)
	{
		var object = document.createElement('object');
		object.setAttribute('style', 'width: 0px; height: 0px; padding: 0px; margin: 0px; position: absolute;');
		object.setAttribute('id', id);
		document.head.appendChild(object);
		return $(object);
	}

};
