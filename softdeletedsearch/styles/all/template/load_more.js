/*
If the user has a total of 50 posts and 5 of it are deleted, we will get 1 on each page potentially with empty pages. 
This script works by following pagination links and adding them to the page, and if there are more results show a load more button. 
If you have a better method for this, please create a pr 
*/



var maxPagesToLoad = 5; // Set the maximum number of pages to load

var lastPage = parseInt($('.pagination li:not(.next):last').text(), 10);
var on_page = parseInt($('.pagination li.active:first').text(), 10);
var baseUrl = document.URL.replace(/&start=\d*/, '');
var pagesLoaded = on_page;
console.log('on page ' + on_page); //for debugging
console.log('Last page ' + lastPage); //for debugging
var totalRows = $('.postprofile').length; // Initialize total rows count

var resultsPerPage = NaN;
// Again, I don't know how much posts your board is set to display per page, we cannot search for start=10 or 25 because it is not static..
// We need to do some funky things to get the correct value
// Find the link with the text '2' which indicates the second page, i.e., only execute when on first page of results
var link = $('a.button[role="button"]').filter(function () {
    return $(this).text().trim() === '2';
});

// If such a link is found
if (link.length > 0) {
    var href = link.attr('href'); // Get the href attribute
    var startParam = new URLSearchParams(href).get('start');
    if (startParam && !isNaN(parseInt(startParam, 10))) {
        resultsPerPage = parseInt(startParam, 10); // Parse the 'start' parameter to an integer
    }
}

function loadPage(page) {
    var pageNum = parseInt(page, 10); // Parse the current page number to an integer
        if (pageNum < lastPage) {
        if (pagesLoaded < maxPagesToLoad) {
            $('#loading-page').text(page);
            pagesLoaded++;
            console.log('Loaded currently ' + pagesLoaded); //for debugging
            loadNextPage(pageNum);

        } else {
            createLoadMoreButton()
        }
        console.log('Loaded ' + pagesLoaded + ' pages.');
        $('.pagination').hide(); // Hide pagination controls -- it is worthless at this point!
    }
}

function loadNextPage(page) {
    $.ajax({
        url: baseUrl + '&start=' + (page * resultsPerPage), // Increment page after using it
        success: function (data) {
            var content = $('.post', data);
            if ($('.post').length > 0) {
                $('.post:last').after(content); // If .post elements exist, append after the last one
            } else {
                $('.panel').html(content);
            }
            totalRows += $('.postprofile', data).length;
            loadPage(page + 1); // Increment the page here for the next call
        }
    });
}

function createLoadMoreButton() {
    var loadMoreBtn = $('<button/>', {
        text: 'Load More',
        id: 'load-more-btn',
        class: 'button button-secondary dropdown-trigger dropdown-select dropdown-toggle',
        click: function () {
            $(this).remove();
            if (pagesLoaded < lastPage) {
                maxPagesToLoad += 5; //same as inital number, probably.
                loadPage(pagesLoaded + 1);
            } else {
                console.log('No more pages to load.');
            }
        }
    });

    loadMoreBtn.insertAfter('.searchresults-title');

}

loadPage(on_page);

