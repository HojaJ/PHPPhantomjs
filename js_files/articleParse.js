

var filename = 'folder/' + url.slice(25,-1) + '.json';
var fs = require('fs');
var page = require('webpage').create();
page.open(url, function (status) { // success
            
    var article = page.evaluate(function () {
        var returnObj = [];
        var h1 = document.querySelector('#article > div > h1').innerText;
        var thumbnail = document.querySelector('article > div.diy-article-image > img').getAttribute('src');
        var returnedArticleObj = document.querySelector('article > div.article-text').childNodes;
        var arr = Array.prototype.slice.call(returnedArticleObj);
        var articlearr = [];
        var obj = [];
    
        for(var i=0;i<arr.length;i++){
            if(arr[i].nodeName !=='#text' && arr[i].tagName !== 'DIV' && arr[i].nodeName !== 'undefined') {
                
                articlearr.push(arr[i]);
                
            }
        }

        for(var i=0;i<articlearr.length;i++){
            if(articlearr[i].tagName == 'P'){
                if(articlearr[i].firstElementChild){
                    if (articlearr[i].firstElementChild.tagName == "SPAN") {
                        obj.push({'p': articlearr[i].firstElementChild.innerText})
                    }else if (articlearr[i].firstElementChild.tagName == "IMG") {
                        obj.push({'img': articlearr[i].firstElementChild.getAttribute('src')})
                    }else if (articlearr[i].firstElementChild.tagName == "B") {
                        if(articlearr[i].firstElementChild.innerText == ""){
                            obj.push({'p': articlearr[i].firstElementChild.nextElementSibling.innerHTML})
                        }else{
                            obj.push({'strong': articlearr[i].firstElementChild.innerText})
                        }
                    }else if (articlearr[i].firstElementChild.tagName == "IFRAME") {
                        obj.push({'youtube': articlearr[i].firstElementChild.getAttribute('src')})
                    }
                }else{
                    if(articlearr[i].innerText.trim() !== ""){
                        obj.push({'p': articlearr[i].innerText})
                    }
                }
            }

            if(articlearr[i].tagName == 'UL'){
                var lis = [];
                var liElements = articlearr[i].children;
                liElements = Array.prototype.slice.call(liElements);
                liElements.forEach(function(item){
                    lis.push(item.innerText);
                });
                obj.push({'ul': lis});
            }
        }

        returnObj.push({
            'h1': h1,
            'thumbnail': thumbnail,
            'article': obj
        });

        var articleObj = JSON.stringify(returnObj,null, 2);
        return articleObj;
    });
                
    fs.write(filename, article);
    phantom.exit();        
});    