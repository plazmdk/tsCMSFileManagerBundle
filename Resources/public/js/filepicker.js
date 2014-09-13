$('.filepicker-pickedfiles-sortable').sortable({
    stop: function( event, ui ) {
        var list = $(ui.item).closest(".filepicker-pickedfiles-sortable");
        list.find("li").each(function(i){
            var item = $(this);
            item.find(".position").val(i);
        });
    }
}).disableSelection();

$('.filepicker .modal').on('show.bs.modal', function (e) {
    var filepicker = $(this).closest(".filepicker");
    var content = filepicker.find(".filepicker-content");

    filepicker.find(".filepicker-upload").fileupload({
        start: function() {
            content.empty().append('<div class="spinner"><div class="dot1"></div><div class="dot2"></div></div>');
        },
        done: function() {
            loadFilepickerData(filepicker, filepicker.data("currentFolder"));
        }
    });

    filepicker.find(".filepicker-createfolder").click(function(){
        var name = prompt("Navn");
        if (name) {
            content.empty().append('<div class="spinner"><div class="dot1"></div><div class="dot2"></div></div>');
            $.post(Routing.generate("tscms_filemanager_filepicker_createfolder"),{directory: filepicker.data("currentFolder"), name: name},function() {
                loadFilepickerData(filepicker, filepicker.data("currentFolder"));
            });
        }
    });

    loadFilepickerData(filepicker, "/");
});

function loadFilepickerData(filepicker, folder) {
    filepicker.data("currentFolder", folder);
    filepicker.find(".filepicker-upload").fileupload(
        "option",
        "formData",
        {directory: folder}
    );
    var selectedFiles = filepicker.find(".path").map(function() {
        return $(this).val();
    }).get();

    var content = filepicker.find(".filepicker-content");
    content.empty().append('<div class="spinner"><div class="dot1"></div><div class="dot2"></div></div>');

    $.getJSON(Routing.generate("tscms_filemanager_filepicker_list",{folder: folder, selectedFiles: selectedFiles, imagesOnly: filepicker.hasClass("images")}),function(listing){
        content.empty();

        var pathwrap = $("<div/>").addClass("filepicker-path");

        pathwrap.append($("<a/>").text("Filarkiv").click(function(e) {
            loadFilepickerData(filepicker, "/");
            e.preventDefault();
        }));
        var currentPath = "/";

        $.each(folder.split("/"),function() {
            if (this != "") {
                currentPath = currentPath + this + "/";
                var partPath = currentPath;
                pathwrap.append($("<span/>").text("/"));
                pathwrap.append($("<a/>").text(this).click(function(e) {
                    loadFilepickerData(filepicker, partPath);
                    e.preventDefault();
                }));
            }
        });
        content.append(pathwrap);

        $.each(listing.directories, function() {
            var file = this;
            var wrapper = $("<a/>").addClass("directory");
            if (file.selected) {
                wrapper.addClass("selected");
            }

            wrapper.append($("<i class='fa fa-folder'/>"));
            wrapper.append(file.title);

            wrapper.click(function(e) {
                loadFilepickerData(filepicker, file.path);
                e.preventDefault();
            });

            appendContextMenu(wrapper, filepicker, file);

            content.append(wrapper);
        });


        $.each(listing.files, function() {
            var file = this;
            var wrapper = $("<a/>").addClass("file");
            if (file.selected) {
                wrapper.addClass("selected");
            }
            if (file.type == "image") {
                wrapper.append(
                    $("<span/>").append($("<img/>").attr("src", "/upload"+file.path))
                );
            } else {
                wrapper.append($("<i class='fa fa-file'/>"));
            }
            wrapper.append(file.title);

            wrapper.click(function() {
                var count = filepicker.data("count");
                filepicker.data("count", count+1);
                wrapper.toggleClass("selected");
                if (wrapper.hasClass("selected")) {
                    var newItemString = filepicker.data("file").replace(/__file__/g,count);
                    var newItem = $(newItemString);
                    var list = filepicker.find(".filepicker-pickedfiles");

                    newItem.find(".path").val(file.path);
                    newItem.find(".filename").text(file.title);
                    newItem.find(".position").val(list.children().length);
                    newItem.find(".image img").attr("src","/upload"+file.path);

                    list.append(newItem);
                } else {
                    filepicker.find(".path[value='"+file.path+"']").closest(".filepicker-pickedfile").remove();
                }
            });

            appendContextMenu(wrapper, filepicker, file);

            content.append(wrapper);
        });
    }).fail(function() {
        content.empty().append("No data");
    });

}
function appendContextMenu(wrapper, filepicker, file) {
    var content = filepicker.find(".filepicker-content");
    var contextMenuId = filepicker.find(".modal").attr("id") +'_context';
    wrapper.contextmenu({
        target:'#'+contextMenuId,
        onItem: function(context,e) {
            this.closemenu();
            var option = $(e.target);
            if (option.hasClass("rename")) {
                var currentName = file.title;
                var newName = prompt("Nyt navn",currentName);
                if (newName && newName.length > 0 && newName != currentName) {
                    content.empty().append('<div class="spinner"><div class="dot1"></div><div class="dot2"></div></div>');
                    $.post(Routing.generate("tscms_filemanager_filepicker_rename"),{path: file.path, name: newName},function() {
                        loadFilepickerData(filepicker, filepicker.data("currentFolder"));
                    });
                }
            } else if (option.hasClass("delete")) {
                if (confirm("Er du sikker?")) {
                    content.empty().append('<div class="spinner"><div class="dot1"></div><div class="dot2"></div></div>');
                    $.post(Routing.generate("tscms_filemanager_filepicker_delete"),{path: file.path},function() {
                        loadFilepickerData(filepicker, filepicker.data("currentFolder"));
                    });
                }
            }
            e.preventDefault();

        }
    });
}