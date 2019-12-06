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
        Editor.codeBlockManager.setReplacer('video', function(youtubeId) {
            // Indentify multiple code blocks
            var wrapperId = 'yt' + Math.random().toString(36).substr(2, 10);
            // avoid sanitizing iframe tag
            setTimeout(renderYoutube.bind(null, wrapperId, youtubeId), 0);

            return '<div id="' + wrapperId + '"></div>';
        });

        // 工具条
        var toolbar = editor.getUI().getToolbar();
        toolbar.addItem({
            type: 'button',
            options: {
              className,
              command: 'addVideo',
              $el: $('<button class="tui-image tui-toolbar-icons"></button>')
            }
        });

        // Commands
        editor.addCommand('markdown', {
            name: 'addVideo',
            exec() {
                var top = $(document).scrollTop();
                var id =  $(editor.options.el).attr("id");
                $('#tuiEditorFiles').css("top", top);
                $("#tuiEditorFiles .action input[name=tuiEidtorUploadedfile]").attr("data-id", id);
                $("#tuiEditorFiles").removeClass("hidden");
                // editor.insertText("```video\nxxxx\n```");
            }
        });
        
    });

    function renderYoutube(wrapperId, youtubeId) {
      var el = document.querySelector('#' + wrapperId);
      el.innerHTML = '<iframe width="420" height="315" src="https://www.youtube.com/embed/' + youtubeId + '"></iframe>';
    }

});
