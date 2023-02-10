$.profiles = {
    rebuild_states: function(section, elm)
    {
        var country_id = $('.cm-country.cm-location-' + section).attr('for');
        elm = elm || $('#' + $('.cm-state.cm-location-' + section).attr('for')).attr('id');
        var sbox = $('#' + elm).is('select') ? $('#' + elm) : $('#' + elm + '_d');
        var inp = $('#' + elm).is('input') ? $('#' + elm) : $('#' + elm + '_d');
        var default_state = $('#' + elm + '_default');
        var cntr = $('#' + country_id);
        if (cntr.length) {
            var cntr_disabled = cntr.is(':disabled');
        } else {
            var cntr_disabled = sbox.is(':disabled');
        }
        var country_code = (cntr.length) ? cntr.val() : default_country;
        var tag_switched = false;
        var pkey = '';

        if (!sbox.length && !inp.length) {
            return false;
        }

        if (states && states[country_code]) { // Populate selectbox with states
            sbox.attr('length', 1);
            for (var k in states[country_code]) {
                pkey = k.str_replace('__', '');
                sbox.append('<option value="' + pkey + '"' + (pkey == default_state.val() ? ' selected' : '') + '>' + states[country_code][k] + '</option>');
            }

            sbox.attr('id', elm).attr('disabled', '').removeClass('hidden cm-skip-avail-switch');
            inp.attr('id', elm + '_d').attr('disabled', 'disabled').addClass('hidden cm-skip-avail-switch');

            if (!inp.hasClass('disabled')) {
                sbox.removeClass('disabled');
            }

        } else { // Disable states

            sbox.attr('id', elm + '_d').attr('disabled', 'disabled').addClass('hidden cm-skip-avail-switch');
            inp.attr('id', elm).attr('disabled', '').removeClass('hidden cm-skip-avail-switch');

            if (!sbox.hasClass('disabled')) {
                inp.removeClass('disabled');
            }
        }

        if (cntr_disabled == true) {
            sbox.attr('disabled', 'disabled');
            inp.attr('disabled', 'disabled');
        }

        default_state.val((sbox.attr('disabled')) ? inp.val() : sbox.val());
    }
}