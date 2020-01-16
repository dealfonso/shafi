$(document).ready(function($) {
    $('table.ddn-table-smart').each(function() {
        // Make the table smart

        var table = $(this);

        table._make_sortable = function() {
            var table = this;

            this.find('thead th').each(function(i) {
                // Add the span for the arrow (only in case that there is a title)
                var arrow = null;
                var th = $(this);
                var html = th.html();

                if (html != "") {
                    // If there is a title... let's add the arrow
                    arrow = $('<span><i class="fas fa-sort"></i></span>').addClass('arrow');
                    th.append(arrow);
                }

                // This column will not be sortable
                if (arrow === null) return;

                th.on('click', function(ev) {
                    ev.preventDefault();
                    let th = $(this);
                    // Detect the current column
                    var col = th.index();
                    // Detect the current sorting order
                    var asc = th.attr('order') === 'asc';
                    asc = !asc;

                    table.find('thead th').each(function(i) {
                        // Remove the order attribute of any th
                        $(this).removeAttr('order');

                        var arrow = $(this).find('span.arrow');
                        // Put the proper arrow to indicate the order
                        if (col == i)
                        if (!asc) 
                            arrow.html('<i class="fas fa-sort-down"></i>');
                        else
                            arrow.html('<i class="fas fa-sort-up"></i>');
                        else
                        // Remove the arrow of any other column
                        arrow.html('<i class="fas fa-sort"></i>');
                    });

                    // Mark the current sorting order in the header using a custom attribute
                    $(this).attr('order', asc?'asc':'desc');

                    // Sort the table
                    table.sort(i, asc);

                    // Re-filter the table
                    table.paginate();
                });
            });      
        }

        table.sort = function(col, asc) {
            var rows = this.find('tbody tr').toArray();
            rows = rows.sort(function(a, b) {
                    /*
                    let valA = $(a).children('td').eq(col).text();
                    let valB = $(b).children('td').eq(col).text();
                    return valA.toString().localeCompare(valB);
                    */
                    let A = $(a).children('td').eq(col);
                    let valA = A.attr('value');
                    valA = valA===undefined?A.text():valA;
                    let numA = parseFloat(valA);
                    let B = $(b).children('td').eq(col);
                    let valB = B.attr('value');
                    valB = valB===undefined?B.text():valB;
                    let numB = parseFloat(valB);
                    if (!isNaN(numB) && !isNaN(numA))
                        return numA - numB;
                    return valA.toString().localeCompare(valB);
                });

            if (!asc) rows = rows.reverse();
            for (var i = 0; i < rows.length; i++)
                this.append(rows[i]);
        }

        table._create_filter = function() {
            this.before(
                $('<input class="ddn-table-filter" type="search" placeholder="Filter..."></input>').on('keyup', function() {
                    table.filter($(this).val());
                    table.paginate();
                })
            );
        }

        table._create_pagination = function() {
            // Build the pagination select and placeholder
            let select = $('<select></select>');
            let pagesizes = this.attr('pagesizes');

            if (pagesizes === undefined)
                pagesizes = [5, 10, 20, 30, 50, 100];
            else
                pagesizes = pagesizes.split(',').map((v) => parseInt(v)).filter((v) => !isNaN(v));

            pagesizes.forEach(function(psize) {
                select.append($('<option></option>').val(psize).text(psize));
            });

            let table = this;
            // Append the structure of the paginator to the table
            table.after(
                $('<div></div>').addClass('ddn-table-pagination').append(
                    $('<label></label>').text('Page size').append(
                        select
                    )
                ).append(
                    $('<div></div>').addClass('numbers')
                )    
            );

            // Get the user's page size
            let pagesize = this.attr('pagesize');
            if (pagesize === undefined) pagesize = pagesizes[0];
            select.val(pagesize);

            // Bind to the on-change event for the select
            select.on('change', function(ev) {
                table.pagesize = $(this).val();
                table.paginate();
            });

            // Set the values at start
            this.pagesize = pagesize;
            this.current = 0;
            this.pagecount = 1;
            this.currentfilter = '';
        }

        table.filter = function(filter) {
            if (filter === undefined)
                filter = this.currentfilter;
            this.currentfilter = filter;
            
            this.find('tbody tr').each(function(trid, tr) {
                var text = $(tr).children('td').map(function(tdid, td) {
                    return $(td).children().attr('title') || td.innerText;
                }).get().join(' ').toLowerCase();

                if ((filter == '') || (text.search(filter)>=0))
                    $(tr).show();
                else 
                    $(tr).hide();
            });
        }

        table.paginate = function(text) {
            // Show the rows that should be shown
            this.filter();

            // Get the visible and calculate the amount of pages needed
            let pagesize = this.pagesize;
            let visible_rows = this.find('tbody tr:visible');
            this.pagecount = Math.ceil(visible_rows.length / pagesize);

            this.showpage();
        }

        table._updatepagenumbers = function() {
            // Now prepare the numbers
            var divpag = this.parent().find("div.ddn-table-pagination div.numbers");
            if (divpag !== undefined) {
                divpag.empty();
                if (this.pagecount > 1) {
                var table = this;

                // Add the first and prev
                divpag.append(
                    $('<a href="#"></a>').addClass((this.current>0?'':'disabled ') + 'page-numbers').on('click', function(ev) {
                        ev.preventDefault();
                        table.showpage('first');
                    }).append(
                        $('<i></i>').addClass('fas fa-angle-double-left')
                    )
                );
                divpag.append(
                    $('<a href="#"></a>').addClass((this.current>0?'':'disabled ') + 'page-numbers').on('click', function(ev) {
                        ev.preventDefault();
                        table.showpage('prev');
                    }).append(
                        $('<i></i>').addClass('fas fa-angle-left')
                    )
                );          

                // Now add the page numbers
                let prefix = null;
                for (var i = 0; i < this.pagecount; i++) {
                    if (prefix !== null)
                        divpag.append(prefix);

                    let element;
                    if (i == this.current)
                        element = $('<span></span>').addClass('current').text(i+1);
                    else
                        element = $('<a href="#"></a>').text(i+1).on('click', function(ev) {
                            ev.preventDefault();
                            table.showpage(parseInt($(this).text()) - 1);
                        }); 

                    divpag.append(element.addClass('page-numbers'));
                    prefix = " | ";
                }

                // Finally, the next and last
                divpag.append(
                    $('<a href="#"></a>').addClass((this.current>=(this.pagecount-1)?'disabled ':'') + 'page-numbers').on('click', function(ev) {
                        ev.preventDefault();
                        table.showpage('next');
                    }).append(
                        $('<i></i>').addClass('fas fa-angle-right')
                    )
                );          

                divpag.append(
                    $('<a href="#"></a>').addClass((this.current>=(this.pagecount-1)?'disabled ':'') + 'page-numbers').on('click', function(ev) {
                        ev.preventDefault();
                        table.showpage('last');
                    }).append(
                        $('<i></i>').addClass('fas fa-angle-double-right')
                    )
                );
                }
            }      
        }

        table.showpage = function(pageid) {
            // Special values
            if (pageid === 'first') pageid = 0;
            if (pageid === 'prev') pageid = this.current - 1;
            if (pageid === 'next') pageid = this.current + 1;
            if (pageid === 'last') pageid = this.pagecount - 1;

            // Get the current page id
            if (pageid === undefined)
                pageid = this.current;

            if (pageid < 0) pageid = 0;
            if (pageid >= this.pagecount) pageid = this.pagecount - 1;

            this.current = pageid;
            
            // Calculate which elements are to be hidden from the visible elements
            pagesize = this.pagesize;
            var first_item = pageid * pagesize;
            var last_item = first_item + pagesize;
            
            // Show all the rows
            this.filter();
            
            var visible_rows = this.find('tbody tr:visible');
            visible_rows.each(function(id, tr) {
                if ((id < first_item)||(id >= last_item))
                    $(tr).hide();
            });           
            
            this._updatepagenumbers();
        }

        // Create the pagination structure
        if (table.hasClass('paginable')) {
            table._create_pagination();
            table.paginate();
        }

        if (table.hasClass('filtrable')) {
            table._create_filter();
        }

        if (table.hasClass('sortable')) {
            table._make_sortable();
        }

    })
});