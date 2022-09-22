var execBtn = document.getElementById("execute");
var outputElm = document.getElementById('output');
var errorElm = document.getElementById('error');
var commandsElm = document.getElementById('commands');
var dbFileElm = document.getElementById('dbfile');
var savedbElm = document.getElementById('savedb');

// Start the worker in which sql.js will run
var worker = new Worker("./dist/worker.sql-wasm.js");
worker.onerror = error;

// Open a database
worker.postMessage({ action: 'open' });

// Connect to the HTML element we 'print' to
function print(text) {
	outputElm.innerHTML = text.replace(/\n/g, '<br>');
}
function error(e) {
	console.log(e);
	errorElm.style.height = '2em';
	errorElm.textContent = e.message;
}

function noerror() {
	errorElm.style.height = '0';
}

var results; //global variable
// Run a command in the database
function execute(commands) {
	tic();
	worker.onmessage = function (event) {
		results = event.data.results;
		toc("Executing SQL");
		if (!results) {
			error({message: event.data.error});
			return;
		}

		tic();
        outputElm.innerHTML = "";
        option.series.splice(0, option.series.length)
		option.legend.data = [];
		//if the first column can be converted to a date, use time on x-Axis. Otherwise, category
		if(isNaN(Date.parse(results[0].values[0][0])))
		{
            option.xAxis.type='category';
            seriesType='bar';
		}
		else
		{
            option.xAxis.type='time';
            seriesType='line';
		}
		for (var i = 0; i < results.length; i++) {
			outputElm.appendChild(tableCreate(results[i].columns, results[i].values));
            //every series contains the left most column (date or category) and then a numerical column
            colStart=1;
            if(isNaN(results[i].values[0][1]) == true) //if the 2nd column is non-numeric, treat it like a grouping category
            {
                colStart = 2; //if 2nd column is categories, start with 3rd column for series
            }
            
			for(c = colStart; c < results[i].columns.length; c++)
			{
                seriesName=results[i].columns[c];
                postString = ' ' + seriesName;
                if(colStart==1) // if no grouping category is defined
                {
                    uniqueCategories= [seriesName] //series is the column name
                }
                else
                {
                    uniqueCategories = Array.from(new Set(results[i].values.map(function(value,index) {return value[1] + postString}))); //unique values in 2nd column, each will be a series            
                }
                columnResults = results[i].values.map(function(value,index) {return [value[0], value[1], value[c]];});
                for(s=0; s<uniqueCategories.length; s++)
                {
                    nameVar=uniqueCategories[s];
                    if(colStart==1) //if no grouping category is defined
                    {
                        dataVar=columnResults.map(function(value,index) {return [value[0], value[2]];});
                    }
                    else
                    {
                        dataVar=columnResults.filter(r => r[1] + postString == nameVar).map(function(value,index) {return [value[0], value[2]];});
                    }
                    //console.log(c);
                    option.series.push({
                        name: nameVar,
                        type: seriesType,
                        showSymbol: false,
                        hoverAnimation: false,
                        data: dataVar
                        });
                    option.legend.data.push(uniqueCategories[s]);
                }
			}
        }
		myChart.clear();
		myChart.setOption(option);
		toc("Displaying results");
	}
	worker.postMessage({ action: 'exec', sql: commands });
	outputElm.textContent = "Fetching results...";
}

function isNumeric(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
  }

// Create an HTML table
var tableCreate = function () {
	function valconcat(vals, tagName) {
		if (vals.length === 0) return '';
		var open = '<' + tagName + '>', close = '</' + tagName + '>';
		return open + vals.join(close + open) + close;
	}
	return function (columns, values) {
		var tbl = document.createElement('table');
		var html = '<thead>' + valconcat(columns, 'th') + '</thead>';
        var rows = values.map(function (v) { return valconcat(v, 'td'); });
		html += '<tbody>' + valconcat(rows, 'tr') + '</tbody>';
		tbl.innerHTML = html;
		return tbl;
	}
}();

// Execute the commands when the button is clicked
function execEditorContents() {
	noerror()
	execute(editor.getValue() + ';');
}
execBtn.addEventListener("click", execEditorContents, true);

// Performance measurement functions
var tictime;
if (!window.performance || !performance.now) { window.performance = { now: Date.now } }
function tic() { tictime = performance.now() }
function toc(msg) {
	var dt = performance.now() - tictime;
	console.log((msg || 'toc') + ": " + dt + "ms");
}

// Add syntax highlihjting to the textarea
var editor = CodeMirror.fromTextArea(commandsElm, {
	mode: 'text/x-mysql',
	viewportMargin: Infinity,
	indentWithTabs: true,
	smartIndent: true,
	lineNumbers: true,
	matchBrackets: true,
	autofocus: true,
	extraKeys: {
		"Ctrl-Enter": execEditorContents,
		"Ctrl-S": savequery,
	}
});

function savequery(){
	$("#myModal").modal();
}
/*
// Load a db from a file
dbFileElm.onchange = function () {
	var f = dbFileElm.files[0];
	var r = new FileReader();
	r.onload = function () {
		worker.onmessage = function () {
			toc("Loading database from file");
			// Show the schema of the loaded database
            if(editor.getValue() == "")
            {
                editor.setValue("SELECT `name`, `sql`\n  FROM `sqlite_master`\n  WHERE type='table';");
            }
			execEditorContents();
		};
		tic();
		try {
			worker.postMessage({ action: 'open', buffer: r.result }, [r.result]);
		}
		catch (exception) {
			worker.postMessage({ action: 'open', buffer: r.result });
		}
	}
	r.readAsArrayBuffer(f);
}

// Save the db to a file
function savedb() {
	worker.onmessage = function (event) {
		toc("Exporting the database");
		var arraybuff = event.data.buffer;
		var blob = new Blob([arraybuff]);
		var a = document.createElement("a");
		document.body.appendChild(a);
		a.href = window.URL.createObjectURL(blob);
		a.download = "sql.db";
		a.onclick = function () {
			setTimeout(function () {
				window.URL.revokeObjectURL(a.href);
			}, 1500);
		};
		a.click();
	};
	tic();
	worker.postMessage({ action: 'export' });
}
savedbElm.addEventListener("click", savedb, true);
*/

function defaultdb(){
    tic();
    errorElm.textContent = "Importing the data..."
    //execBtn.disabled=true;
    var xhr = new XMLHttpRequest();
	// For example: https://github.com/lerocha/chinook-database/raw/master/ChinookDatabase/DataSources/Chinook_Sqlite.sqlite
	xhr.open('GET', '/covid/covid.db', true);
	xhr.responseType = 'arraybuffer';

	xhr.onload = e => {
	  var uInt8Array = new Uint8Array(xhr.response);
	  try {
			worker.postMessage({ action: 'open', buffer: uInt8Array }, [uInt8Array]);
		}
		catch (exception) {
			worker.postMessage({ action: 'open', buffer: uInt8Array });
        }
        errorElm.textContent = "Importing the data... done!";
        execBtn.click();
	  // contents is now [{columns:['col1','col2',...], values:[[first row], [second row], ...]}]
	};
    xhr.send();
    toc("Importing the data");
}

window.onresize = function() {
    myChart.resize();
  };

defaultdb();
