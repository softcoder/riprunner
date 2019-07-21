// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

function synchTables(tableArray) {
    var cellWidths = new Array();

    // get widest
    //alert('tableArray.length = ' + tableArray.length);
    for(i = 0; i < tableArray.length; i++) {
        for(j = 0; j < tableArray[i].rows[0].cells.length; j++) {
           var cell = tableArray[i].rows[0].cells[j];
           if(!cellWidths[j] || cellWidths[j] < cell.clientWidth)
                cellWidths[j] = cell.clientWidth;
        }
    }

    // set all columns to the widest width found
    for(i = 0; i < tableArray.length; i++) {
        for(j = 0; j < tableArray[i].rows[0].cells.length; j++) {
        	tableArray[i].rows[0].cells[j].style.width = cellWidths[j]+'px';
        	//tableArray[i].rows[0].cells[j].width = cellWidths[j]+'px';
        	//tableArray[i].rows[0].cells[j].clientWidth = cellWidths[j];
        }
    }
    //alert('cellWidths.length = ' + cellWidths.length);
}

function synchTable(table1, table2) {
    var tables = new Array();
    tables.push(table1,table2);

    synchTables(tables);
}