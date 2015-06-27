(function($) {
    $.fn.extend({
        bindToggleAllCountries: function() {
            // No countries in use, hide them
            if ($('#countries tr.in_use').length == 0) {
                $('#countries').hide();
                $('#empty-countries').show();
            }
            else {
                $('#countries').updateZebraStriping($('#countries tr.country-row.in_use'));
            }
            
            // Show all countries
            $('#all-countries').on('click', function() {
                $(this).hide();
                $('#in_use-countries').show();
                $('#empty-countries').hide();
                
                $('#countries').show();
                $('#countries tr.country-row').show();
                $(this).updateZebraStriping($('#countries tr.country-row'));
                
                return false;
            });
            
            // Show all countries in use
            $('#in_use-countries').on('click', function() {
                $(this).hide();
                $('#all-countries').show();
                $('#countries tr.country-row').hide();
                $('#countries tr.country-row').next('tr.expand_details').hide();
                
                if ($('#countries tr.in_use').length == 0) {
                    $('#empty-countries').show();
                    $('#countries').hide();
                }
                else {
                    $('#countries tr.in_use').show();
                    $('#empty-countries').hide();
                    
                    $(this).updateZebraStriping($('#countries tr.country-row.in_use'));
                }
                
                return false;
            });
        },
        
        bindToggleAllStates: function(section) {
            // No states in use, hide them
            if ($(section).closest('.state-list').find('.states table tr.state-row.in_use').length == 0) {
                $(section).closest('.state-list').find('.states').hide();
                $(section).closest('.state-list').find('.empty-states').show();
            }
            else {
                $(section).updateZebraStriping($(section).closest('.state-list').find('.states table tr.state-row.in_use'));
            }
            
            // Show all states
            $(section).find('.all-states').on('click', function() {
                var states = $(this).closest('.state-list');
                $(this).hide();
                $(states).find('.in_use-states').show();
                $(states).find('.empty-states').hide();
                
                $(states).find('.states').show();
                $(states).find('.states table tr.state-row').show();
                $(states).updateZebraStriping($('.states table tr.state-row'));
                
                return false;
            });
            
            // Show all states in use
            $(section).find('.in_use-states').on('click', function() {
                var states = $(this).closest('.state-list');
                $(this).hide();
                $(states).find('.all-states').show();
                $(states).find('.states table tr.state-row').hide();
                
                if ($(states).find('.states table tr.state-row.in_use').length == 0) {
                    $(states).find('.empty-states').show();
                    $(states).find('.states').hide();
                }
                else {
                    $(states).find('.states').show();
                    $(states).find('.states table tr.state-row.in_use').show();
                    $(states).find('.empty-states').hide();
                    
                    $(states).updateZebraStriping($(states).find('.states table tr.state-row.in_use'));
                }
                
                return false;
            });
        },
        
        updateZebraStriping: function(row) {
            var i = 0;
            $(row).each(function() {
                if ($(row).is(':visible')) {
                    if (i%2 == 1)
                        $(this).addClass('odd_row');
                    else
                        $(this).removeClass('odd_row');
                    i++;
                }
            });
        }
    });
    
    $(document).ready(function() {
        $(this).bindToggleAllCountries();
    });
})(jQuery);
