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

function navigate(module, action, otherArgs, targetDiv, ignoreState)
{	
	if (!action)
	{
		action = 'render';
	}
	
	var mainNavigation = false;
	
	if (!targetDiv)
	{
		mainNavigation = true;
		targetDiv = 'main';
	}
	
	var args = 'module='+module+'&action='+action+'&target='+targetDiv;
		
	if (otherArgs)
	{
		args = args + '&'+otherArgs;
	}
	
		
	var targetURL = 'index.php?json=true&'+args;
		
	$.ajax({
			url: targetURL,
			type:'POST',
			success: function(data)
			{	
				data =  $.parseJSON(data);				
				$('#'+data.target).html(data.content);
				$('#title').html(data.title);

				if (!ignoreState && mainNavigation)
				{
					//alert('pushed sate');
					window.history.pushState({"module": data.module, "action":action, "args": otherArgs}, "", data.module);	
				}				
			}
		});		
	return false;
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

function waitForOperation(progressBar, fn)
{
	var progressBar = $('#'+progressBar);
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
					if (fn)
					{
						fn();
					}					
				}				
				
				var p = percent+'%';
				console.log(p);
				progressBar.css('width', p);	
			});

	} , 1000);
}

function waitThenNavigate(progressBar, module, action, otherArgs)
{
	navigate(module, action, otherArgs);
	waitForOperation(progressBar);
}

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

function downloadFile(progressBar, module, action, otherArgs)
{	
	var args = 'module='+module+'&action='+action;
		
	if (otherArgs)
	{
		args = args + '&'+otherArgs;
	}

	var a = document.createElement('a');
	if (window.URL && window.Blob && ('download' in a) && window.atob) {
		// Do it the HTML5 compliant way		
		args = args + '&ajax';
		$.ajax({
			url: 'index.php?'+args,
			type:'GET',
			success: function(data){		
				var result = $.parseJSON(data); 
				var blob = base64ToBlob(result.data, result.mimetype);
				var url = window.URL.createObjectURL(blob);
				a.href = url;
				a.download = result.filename;
				a.click();
				window.URL.revokeObjectURL(url);
			}
		});	
		waitForOperation(progressBar);
	}
	else
	{
		window.location = 'index.php?'+args;
	}

}

window.onpopstate = function(e){	
    if(e.state){		
		//alert(e.state.module);
		navigate(e.state.module, e.state.action, e.state.args, true);        
    }
};