(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
      define(['tui-editor'], factory);
    } else if (typeof exports === 'object') {
      factory(require('tui-editor'));
    } else {
      factory(root['tui']['Editor']);
    }
  })(this, function(Editor) {

    // const className = 'tui-image tui-toolbar-icons';
    const className = 'si si-cloud-upload';

    // define youtube extension
    Editor.defineExtension('video', function( editor ) {
        
        // runs while markdown-it transforms code block to HTML
        Editor.codeBlockManager.setReplacer('video', function(url) {
            // Indentify multiple code blocks
            var wrapperId = 'vd' + Math.random().toString(36).substr(2, 10);
            
            // avoid sanitizing iframe tag
            setTimeout(renderVideo.bind(null, wrapperId, url), 0);
            return '<div id="' + wrapperId + '"></div>';

        });

        Editor.codeBlockManager.setReplacer('qqvideo', function(url) {
            var loc = new URL(url);
            var path = loc.pathname;
            var file = path.split("/").pop();
            var vid = file.split(".html")[0];            
            var wrapperId = 'qv' + Math.random().toString(36).substr(2, 10);
            setTimeout(renderQQPlayer.bind(null, wrapperId, vid), 0);
            return '<div id="' + wrapperId + '"></div>';
        });


        // 工具条
        var toolbar = editor.getUI().getToolbar();
        toolbar.addItem({
            type: 'button',
            options: {
              className,
              command: 'addFile',
              $el: $('<button class="tui-image tui-toolbar-icons"></button>')
            }
        });

        // Commands
        editor.addCommand('markdown', {
            name: 'addFile',
            exec() {
                var top = $(document).scrollTop();
                var id =  $(editor.options.el).attr("id");
                $('#tuiEditorFiles').css("top", top);
                $("#tuiEditorFiles .action input[name=tuiEidtorUploadedfile]").attr("data-id", id);
                $("#tuiEditorFiles").removeClass("hidden");
            }
        });
        
    });

    function renderQQPlayer(wrapperId, vid) {
      var el = document.querySelector('#' + wrapperId);
      el.innerHTML = '<iframe width="420" height="315" src="https://v.qq.com/txp/iframe/player.html?vid=' + vid + '"></iframe>';
    }

    function renderVideo(wrapperId, url) {
        var el = document.querySelector('#' + wrapperId);
        el.innerHTML = ' <video width="100%" id="course-video" preload="auto" controls><source src="'+ url +'"></source></video>';
    }
    
});
