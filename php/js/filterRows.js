(function(document) {
	'use strict';

	var LightTableFilter = (function(Arr) {

		var _input;

		function _onInputEvent(e) {
			_input = e.target;
      var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
      var columns = (_input.getAttribute('data-table-columns') || '').split(',');
			Arr.forEach.call(tables, function(table) {
				Arr.forEach.call(table.tBodies, function(tbody) {
					Arr.forEach.call(tbody.rows, function(row) {
            _filter(row, columns);
          });
				});
			});
		}

		function _filter(row, columns) {
      var text, val = _input.value.toLowerCase();
      if (columns.length) {
        columns.forEach(function(index) {
          text += ' ' + row.cells[index].textContent.toLowerCase();
        });
      }
      else {
        text = row.textContent.toLowerCase();
      }
			row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
		}

		return {
			init: function() {
				var inputs = document.getElementsByClassName('table-filter');
				Arr.forEach.call(inputs, function(input) {
					input.oninput = _onInputEvent;
				});
			}
		};
	})(Array.prototype);

	document.addEventListener('readystatechange', function() {
		if (document.readyState === 'complete') {
			LightTableFilter.init();
		}
	});

})(document);
