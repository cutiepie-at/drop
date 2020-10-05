$(document).ready(function()
{
  $('#optionnew').click(function()
  {
    $(location).attr('href','/');
  });
  
  //key events
  $(window).keydown(function(e)
  {
    if((e.ctrlKey && e.keyCode == 78) || e.keyCode == 113)
    {
      e.preventDefault();
      var win = window.open('/' , '_blank');
      win.focus();
    }
  });
  
  var doUpload = function(file) {
    var Upload = function (file) {
      this.file = file;
    };

    Upload.prototype.getType = function() {
      return this.file.type;
    };
    Upload.prototype.getSize = function() {
      return this.file.size;
    };
    Upload.prototype.getName = function() {
      return this.file.name;
    };
    Upload.prototype.doUpload = function () {
      $("#text").html("Uploading");
      $("#progress-wrp").css("display", "block");
      
      var that = this;
      var formData = new FormData();

      // add assoc key values, this will be posts values
      formData.append("file", this.file, this.getName());
      formData.append("action", "upload");

      $.ajax({
        type: "POST",
        url: "/",
        xhr: function ()
        {
          var myXhr = $.ajaxSettings.xhr();
          if (myXhr.upload) 
            myXhr.upload.addEventListener('progress', that.progressHandling, false);
          return myXhr;
        },
        success: function (data) 
        {
          console.log(data);
          var obj = JSON.parse(data);
          if(obj.type == "Success")
            //alert(data);
            $(location).attr('href', '/' + obj.msg);
          else
            alert(obj.type+": " + obj.msg);
        },
        error: function (error) {
          // handle error
          alert('Error: ' + error);
        },
        async: true,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        timeout: 60000
      });
    };

    Upload.prototype.progressHandling = function (event) 
    {
      var percent = 0;
      var position = event.loaded || event.position;
      var total = event.total;
      var progress_bar_id = "#progress-wrp";
      if (event.lengthComputable) 
        percent = Math.ceil(position / total * 100);
        
      // update progressbars classes so it fits your code
      $(progress_bar_id + " .progress-bar").css("width", +percent + "%");
      $(progress_bar_id + " .status").text(percent + "%");
    };
    var u = new Upload(file);
    u.doUpload();
  };

  
  $('#drop_zone').on("dragover", false);
  $('#drop_zone').on("drop", function(ev)
  {
    // Prevent default behavior (Prevent file from being opened)
    ev.preventDefault();
    ev.stopPropagation();
    if (ev.originalEvent.dataTransfer.items) 
    {
      if (ev.originalEvent.dataTransfer.items.length > 1) {
        alert ("Single files only");
        return;
      }
      if (ev.originalEvent.dataTransfer.items.length == 0) 
        return;

      // Use DataTransferItemList interface to access the file(s)
      if (ev.originalEvent.dataTransfer.items[0].kind === 'file')
      {
        var file = ev.originalEvent.dataTransfer.items[0].getAsFile();
        doUpload(file);
      }
    } 
    else
    {
      if (ev.originalEvent.dataTransfer.items.length > 1) {
        alert ("Single files only");
        return;
      }
      if (ev.originalEvent.dataTransfer.items.length == 0) {
        return;
      }
      // Use DataTransfer interface to access the file(s)
      var file = ev.originalEvent.dataTransfer.items[0];
      doUpload(file);
    } 
  });
  
  $("#drop_zone").on("click", function() {
    $("#upload_input").trigger("click");
  });
  $("#upload_input").on("change", function()
  {
    var that = $(this);
    var files = that.prop("files");
    if (files.length > 1) {
      alert ("Single files only");
      return;
    }
    if (files.length == 0) 
      return;

    var file = files[0];
    doUpload(file);
  });

  //string startwith impl
  if ( typeof String.prototype.startsWith != 'function' ) 
  {
    String.prototype.startsWith = function( str ) 
    {
      return this.substring( 0, str.length ) === str;
    }
  };
  
  //img size
  var imgresize = function()
  {
    var img = $('#img');
    if(img !== undefined)
    {
      var fullscreen = $('#fullscreen');
      var caption = $('#imgcaption');
      
      var maxwidth = fullscreen.width();
      var maxheight = fullscreen.height();
      maxheight -= caption.outerHeight(true);
      
      var orgwidth = img.prop("naturalWidth");
      var orgheight = img.prop("naturalHeight");

      if(orgwidth == undefined)
      {
        orgwidth = img.prop("videoWidth");
        orgheight = img.prop("videoHeight");
      }
      
      if(orgwidth == 0 || orgwidth == undefined || orgheight == 0 || orgheight == undefined)
        return;
      
      console.log("orgw: "+orgwidth);
      console.log("orgh: "+orgheight);
      console.log("maxw: "+maxwidth);
      console.log("maxh: "+maxheight);
      
      var width = orgwidth;
      var height = orgheight;
      
      if(width > maxwidth)
      {
        height = height * maxwidth / width;
        width = maxwidth;
      }
      if(height > maxheight)
      {
        width = width * maxheight / height;
        height = maxheight;
      }
      
      width = Math.floor(width);
      
      console.log("w: "+width);
      console.log("h: "+height);
      
      img.attr("width", width);
      //img.attr("height", height);
    }
  };
  imgresize();
  
  $(window).resize(function() 
  {
    console.log("resize");
    imgresize();
  });
  var resizing = false;
  $('#img').resize(function() 
  {
    if(resizing)
      return;
    resizing = true;
    console.log("resize");
    imgresize();
    resizing = false;
  });
  
  $("#img").one("load", function() {
    console.log('img loaded -> resize');
    imgresize();
  }).each(function() {
    console.log('completed?');
    if(this.complete) {
      console.log('completed! -> trigger');
      $(this).trigger('load');
    }
  });
  
  //pdf size
  var pdfresize = function()
  {
    var pdf = $('#pdf');
    if(pdf !== undefined)
    {
      var fullscreen = $('#fullscreen');
      var caption = $('#pdfcaption');
      
      var width = fullscreen.width();
      var height = fullscreen.height();
      height -= caption.outerHeight(true);
      
      console.log("w: "+width);
      console.log("h: "+height);
      
      pdf.attr("width", width*0.9);
      pdf.attr("height", height);
    }
  };
  pdfresize();
  
  $(window).resize(function() 
  {
    console.log("pdfresize");
    pdfresize();
  });
  var resizingpdf = false;
  $('#pdf').resize(function() 
  {
    if(resizingpdf)
      return;
    resizingpdf = true;
    console.log("pdfresize");
    pdfresize();
    resizingpdf = false;
  });
  
  $("#pdf").one("load", function() {
    console.log('pdf loaded -> resize');
    pdfresize();
  }).each(function() {
    console.log('completed?');
    if(this.complete) {
      console.log('completed! -> trigger');
      $(this).trigger('load');
    }
  });
});

var dl = function()
{
  $("#dlform").submit();
  //$("#dlform>img").click(function () {$("#dlform").submit();});
};
