function getURL()
{
    queryLink = "http://surrette.net/covid?sql=" + encodeURIComponent(editor.getValue()).replace(/[!'()*]/g, escape);
    var linkElement = document.getElementById("link")
    linkElement.innerHTML = "<h2><a target='_blank' href='" + queryLink + "'>Covid SQL</a></h2>";
    window.scrollBy(0, 100);
    //console.log(queryLink);
}

function setQuery(val)
{
    document.getElementById("queryName").value = $("#queryList option:selected").text();
    editor.setValue(val);
    execBtn.click();
}

function loadModal()
{
    sel = document.getElementById("queryList");
    document.getElementById("queryName").value = sel.options[sel.selectedIndex].innerHTML;
}

$('#commentForm').submit(function() {
    console.log(editor.getValue());
    document.getElementById("sqlCommand").value = editor.getValue();
    return true; // return false to cancel form action
});