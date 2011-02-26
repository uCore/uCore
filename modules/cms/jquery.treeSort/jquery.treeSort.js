/*
* Sortable Tree View
*
* Copyright (c) 2010 Tom Kay - oridan82@gmail.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.*
*
*/

;(function($){
	var optionDefaults = {
		topPad: 5,
		placeholder: 'ui-state-highlight',
		tree:	'ul',
		branch:	'li'
	};
  var onselstart = null;
	
	$.fn.treeSort = function(settings) {
		var options = $.extend({}, optionDefaults, settings);

		// filter out child levels of tree.
		$(options.tree,this).not(options.tree+' '+options.tree).each(function () {
			var self = $(this);
			// clone first branch for placeholder
			var place = $(options.branch,self).first().clone(false).empty().attr('id','ui-treesort-placeholder').text('treeSort Placeholder').addClass(options.placeholder).hide().appendTo(this);
			var overElement = null;
			var dragging = false;
			
			$(options.branch,this).draggable({
			  	appendTo: 'body',
				helper: 'clone',
				opacity: 0.5,
				refreshPositions:true,
				distance:5,
				start: function(e, ui) {
					onselstart = document.onselectstart;
					document.onselectstart = function () { return false; }
					if (dragging) return false;
					dragging = true;
					place.html($(this).html()).show();
					$(this).hide();
				},
				stop: function(e, ui) {
					document.onselectstart = onselstart;
					dragging = false;
					if (!$(this).has(place)) return;
					$(this).css({top:null,left:null}).insertAfter(place);
					place.detach();
					$(this).show();
					UpdateClasses();
					if (options.change) options.change(options);
				},
				drag: function(e, ui) {
					if (!overElement || !overElement.offset()) return;

					cX = e.clientX + $("body").scrollLeft();
					cY = e.clientY + $("body").scrollTop();
					
					oX = cX - overElement.offset().left;
					oY = cY - overElement.offset().top;

					var after = 1;
					
					// insert before current item
					if (oY < options.topPad) after = 0;

					if (oX > 20) {
						// add as a child to the current item
						var c = overElement.children(options.tree+':first');
						if (c.length === 0) { c = $(self).andSelf().filter(options.tree).first().clone(false).empty().appendTo(overElement); }
						c.append(place);
						return;
					} else if (oX < 0) {
						// insert to parent of current item
						// allow moving an item to the end of a tree which has many extended branches but no trailing siblings.
						overElement = overElement.parents(options.branch).eq(0);
					}
					// insert after current item
					if (after)
						overElement.after(place);
					else
						overElement.before(place);
				}
			});
			$(options.branch,this).droppable({
				accept: options.branch,
				tolerance: 'pointer',
				over: function(e, ui) {
					if ($(this).hasClass(options.placeholder)) return;
					overElement = $(this);
				}
			});
			UpdateClasses();
			if (options.init) options.init(options);
			
			function UpdateClasses() {
				$(options.branch,self).addClass('ui-treesort-item').removeClass('ui-treesort-folder');
				var parents = $(options.tree,self).not('.ui-draggable-dragging').not(place).not(':empty').parent(options.branch);
				parents.addClass('ui-treesort-folder');
			}
		});
		
		return $(this);
	};
})(jQuery);
