function base64ToBlob(base64, mimetype, slicesize) {
    if (!window.atob || !window.Uint8Array) {
        // The current browser doesn't have the atob function. Cannot continue
        return null;
    }
    mimetype = mimetype || '';
    slicesize = slicesize || 512;
    var bytechars = atob(base64);
    var bytearrays = [];
    for (var offset = 0; offset < bytechars.length; offset += slicesize) {
        var slice = bytechars.slice(offset, offset + slicesize);
        var bytenums = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            bytenums[i] = slice.charCodeAt(i);
        }
        var bytearray = new Uint8Array(bytenums);
        bytearrays[bytearrays.length] = bytearray;
    }
    return new Blob(bytearrays, {type: mimetype});
};

function gather(argList)
{
	var args = '';
	var s = argList.split(",");
	var i;
	for (i=0; i<s.length; i++)
	{			
		if (s[i].length<=0)
		{
			continue;
		}
		
		if (args.length>0)
		{
			args = args +'&';
		}
		
		args = args + s[i]+'=';
		var val;
		
		var element = $("#"+s[i]);
		
		/*if (element.attr('type') == 'file')
		{
			alert('detectef file '+s[i]);
			var file = document.getElementById('fileBox').files[0];
			
			//btoa()
		}
		*/
		try {
			val = element.autoNumeric("get");
			val = val.replace('.00', '');
		}
		catch(err) 
		{
			val = element.val();
		}

		if (val)
		{
			args = args + encodeURIComponent(val);
		}
		
	}
		
	return args;
}

function getProgress(module, fn)
{		
	$.ajax({
	url: 'index.php?module='+module+'&action=progress',
	type:'GET',
	success: function(data){		
		fn(data);
	}
	});	
}

function synkNav()
{	
	var nav = {
		module : 'current',
		action : 'render',
		target : undefined,
		otherArgs : undefined,
		skipHistory : undefined,
		isNavigation: true,
		isDownload: undefined,
		progressBar: undefined,
		finishCallback : undefined,
		failCallback : undefined
	};
  
	nav.setModule = function (module) { this.module = module; return this; };	
	nav.setAction = function (action) { this.action = action; return this; };	
	nav.setTarget = function (target) { this.target = target; return this; };	
	nav.setProgressBar = function (bar) { this.progressBar = bar; return this; };	
	nav.setArgs = function (args) { this.otherArgs = args; return this; };	
	nav.ignoreHistory = function () { this.skipHistory = true; return this; };	
	nav.onFinish = function (fn) { this.finishCallback = fn; return this; };	
	nav.onFail = function (fn) { this.failCallback = fn; return this; };	
	nav.complete = function()
	{
		if (this.finishCallback)
		{
			this.finishCallback();
		}
		
		var data = this.data; 
			
		if (this.isDownload)
		{
			var blob = base64ToBlob(data.content, data.mimetype);
			var url = window.URL.createObjectURL(blob);
			var a = this.anchor;
			a.href = url;
			a.download = data.filename;
			a.click();
			window.URL.revokeObjectURL(url);			
			return;			
		}
		else
		if (this.isNavigation)
		{
			$('#'+data.target).html(data.content);
			$('#title').html(data.title);

			if (!this.skipHistory && this.mainNavigation)
			{
				//alert('pushed sate');
				window.history.pushState({"module": data.module, "action":this.action, "args": this.otherArgs}, "", data.module);	
			}									
		}	
	};
	
	nav.download = function() {
		this.isDownload = true;
		return this.go();
	}
	
	nav.call = function() {
		this.isNavigation = false;
		return this.go();
	}

	nav.go = function() {
		var that = this;

		this.mainNavigation = false;
	
		if (!this.target)
		{
			this.mainNavigation = true;
			this.target = 'main';
		}
	
		var args = 'module='+this.module+'&action='+this.action+'&target='+this.target;
		
		if (this.otherArgs)
		{
			args = args + '&'+this.otherArgs;
		}
	
		var targetURL = 'index.php?'+args;
		
		if (this.isDownload)
		{
			var a = document.createElement('a');
			
			if (window.URL && window.Blob && ('download' in a) && window.atob)
			{
				this.anchor = a;
			}
			else
			{
				window.location = targetURL;
				return;
			}
		}
		
		targetURL = targetURL + '&json=true';		

		if (this.progressBar)
		{
			var progressBar = $('#'+this.progressBar);
			var percent = 0;
			progressBar.css('width', '0');	
		
			var timerID = setInterval(function()
			{
				getProgress('current', function(val)
					{
						percent = val;	
						
						if (percent>=100)
						{
							percent = 100;
							clearInterval(timerID);				

							if (that.data)
							{
								that.complete();
							}					
							else
							{
								that.progressBar = undefined;
							}
						}				
						
						var p = percent+'%';
						console.log(p);
						progressBar.css('width', p);	
					});

			} , 1000);			
		}
		
		var requestType;
		
		if (this.isDownload)
		{
			requestType = 'GET';
		}
		else
		{
			requestType = 'POST';
		}
			
		$.ajax({
			url: targetURL,
			type: requestType,
			error: function(xhr, textStatus, errorThrown){
				if (that.failCallback)
				{
					that.failCallback();
				}
				alert('request failed '+targetURL);
			},
			success: function(data)								
			{	
				that.data = $.parseJSON(data);
				
				if (!that.progressBar)
				{
					that.complete();	
				}				
			}
		});		
		
		return false;		
	};
	
	return nav;
}
  

window.onpopstate = function(e){	
    if(e.state){		
		//alert(e.state.module);
		navigate(e.state.module, e.state.action, e.state.args, true);        
    }
};