$('.filepicker .modal').on('show.bs.modal', function (e) {
    var filepicker = $(this).closest(".filepicker");
    loadFilepickerData(filepicker, "/");
});

function loadFilepickerData(filepicker, folder) {
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
                    newItem.find(".image img").attr("src","/upload"+file.path);

                    list.append(newItem);
                } else {
                    filepicker.find(".path[value='"+file.path+"']").closest(".filepicker-pickedfile").remove();
                }
            });

            content.append(wrapper);
        });
    }).fail(function() {
        content.empty().append("No data");
    });

}