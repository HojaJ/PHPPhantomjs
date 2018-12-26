

var page = require('webpage').create();
var fs = require('fs');

// var url='';

page.open(url, function (status) {
    var categories_links_json = page.evaluate(function () {
        var all_links = [];
        var links = document.querySelectorAll('#main_section > div > a');
        [].forEach.call(links, function(link){
            all_links.push(link.getAttribute('href'))
        });

        var imgs_links = [];
        var imgs = document.querySelectorAll('#main_section > div > a > div > img');
        [].forEach.call(imgs, function(link){
            imgs_links.push(link.getAttribute('src'))
        });

        var returnArrays = [];
        returnArrays.push(all_links,imgs_links);

        var all_links_json = JSON.stringify(returnArrays);

        return all_links_json;
    });

    fs.write('linksFromCat/categories_links.json', categories_links_json);

    phantom.exit();
});

