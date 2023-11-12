//function DeletedSearchLoader(){


//I dont have a better way to run this, so we have to rely on js to show results

    var lastPage = parseInt($('.pagination li:not(.next):last').text(), 10);
    var on_page = parseInt($('.pagination li.active:first').text(), 10);
    var baseUrl = document.URL.replace(/&start=\d*/, '');
    var pagesLoaded = on_page;
    console.log('on page ' + on_page); //for debugging
    console.log('Last page ' + lastPage); //for debugging
    var totalRows = $('.postprofile').length; // Initialize total rows count
    
    var resultsPerPage = NaN;
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
    
    var maxPagesToLoad = 5; // Set the maximum number of pages to load
    
    function loadPage(page) {
        var pageNum = parseInt(page, 10); // Parse the current page number to an integer
        //if (page <= lastPage && pagesLoaded < maxPagesToLoad) {
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
            $('.pagination').hide(); // Hide pagination controls
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
                updateSearchResultsTitle(totalRows);
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
                    maxPagesToLoad += 5; //same as inital number
                    loadPage(pagesLoaded + 1);
                } else {
                    console.log('No more pages to load.');
                }
            }
        });
    
        loadMoreBtn.insertAfter('.searchresults-title');
    
    }
    
    function updateSearchResultsTitle(totalRows) {
        var resultsTitle = $('.searchresults-title');
        if (resultsTitle.length > 0) {
            var text = resultsTitle.text();
            var updatedText = text.replace(/\d+/, totalRows.toLocaleString('en-US'));
            resultsTitle.text(updatedText);
        }
    }
    
    
    loadPage(on_page);
    //}
    
    
    