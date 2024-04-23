import $ from 'jquery';
import {call as fetchMany} from 'core/ajax';

export const init = () => {
    filterBadges();
};
export const filterBadges = () => {
    $('#id_badge_filter').keyup(async function() {
        // Get the user input value.
        let userinput = $('#id_badge_filter').val();
        await listFilteredBadges(userinput);
    });
};
const listFilteredBadges = (userinput) => fetchMany([{
    methodname: 'enrol_ibobenrol_filter_badge_function',
    args: {
        userinput,
    },
}])[0].done((response) => {
    if (response.length > 0) {
        $('#id_badges').html(JSON.parse(response));
    }
});
